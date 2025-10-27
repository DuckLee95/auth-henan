<?php

namespace Blessing\XAuth;

use App\Events;
use App\Models\Player;
use App\Models\User;
use App\Rules;
use Auth;
use AuthAdapter;
use Blessing\Filter;
use Blessing\Rejection;
use Cache;
use Carbon\Carbon;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use Session;
use Vectorface\Whip\Whip;

use Illuminate\Http\JsonResponse;

class XAuthController
{
    //登录页
    public function login(Filter $filter, string $msg = '')
    {
        $whip = new Whip();
        $ip = $whip->getValidIpAddress();
        $ip = $filter->apply('client_ip', $ip);

        $rows = [
            'Blessing\XAuth::auth.rows.login.notice',
            'Blessing\XAuth::auth.rows.login.form',
            'Blessing\XAuth::auth.rows.login.message',
            'Blessing\XAuth::auth.rows.login.registration-link',
        ];
        $rows = $filter->apply('auth_page_rows:login', $rows);

        return view('Blessing\XAuth::auth.login', [
            'rows' => $rows,
            'extra' => [
                'tooManyFails' => cache(sha1('login_fails_' . $ip)) > 3,
                'recaptcha' => option('recaptcha_sitekey'),
                'invisible' => (bool) option('recaptcha_invisible'),
            ],
            'msg' => $msg
        ]);
    }
    /**
     * 1.确认统一认证信息,错误返回登陆页
     * 2.用户存在,登录
     * 3.用户不存在,按校园邮箱注册
     */
    public function handleLogin(
        Request $request,
        Rules\Captcha $captcha,
        Dispatcher $dispatcher,
        Filter $filter
    ) {
        // ncwu统一认证
        $json = json_decode(XAuthController::authserver($request)->getContent(), true);
        //认证过滤
        if (!$json['success']) {
            return $this->login($filter, 'ncwu统一认证失败,检查账号和密码');
        }

        $data = $request->validate([
            'identification' => 'required',
            'password' => 'required|min:6|max:32',
        ]);
        $identification = $data['identification'];
        $password = $data['password'];

        $can = $filter->apply('can_login', null, [$identification, $password]);
        if ($can instanceof Rejection) {
            return json($can->getReason(), 1);
        }

        // TODO:自定义邮箱

        // 默认校园邮箱
        $identification .= '@stu.ncwu.edu.cn';

        // Guess type of identification
        $authType = filter_var($identification, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $dispatcher->dispatch('auth.login.attempt', [$identification, $password, $authType]);
        event(new Events\UserTryToLogin($identification, $authType));

        if ($authType == 'email') {
            $user = User::where('email', $identification)->first();
        } else {
            $player = Player::where('name', $identification)->first();
            $user = optional($player)->user;
        }

        // Require CAPTCHA if user fails to login more than 3 times
        $whip = new Whip();
        $ip = $whip->getValidIpAddress();
        $ip = $filter->apply('client_ip', $ip);
        $loginFailsCacheKey = sha1('login_fails_' . $ip);
        $loginFails = (int) Cache::get($loginFailsCacheKey, 0);

        // 没有则注册
        if (!$user) {
            $request->merge(['email' => $identification]);
            // return json(trans('auth.validation.user'), 2);
            return $this->handleRegister($request, $captcha, $dispatcher, $filter);
        }

        $dispatcher->dispatch('auth.login.ready', [$user]);

        if ($user->verifyPassword($request->input('password'))) {
            Session::forget('login_fails');
            Cache::forget($loginFailsCacheKey);

            Auth::login($user, $request->input('keep'));

            $dispatcher->dispatch('auth.login.succeeded', [$user]);
            event(new Events\UserLoggedIn($user));

            // return json(trans('auth.login.success'), 0, [
            //     'redirectTo' => $request->session()->pull('last_requested_path', url('/user')),
            // ]);
            return redirect(url('/user'));

        } else {
            //无需验证码
            // $loginFails++;
            Cache::put($loginFailsCacheKey, $loginFails, 3600);
            $dispatcher->dispatch('auth.login.failed', [$user, $loginFails]);

            // return json(trans('auth.validation.password'), 1, [
            //     'login_fails' => $loginFails,
            // ]);
            return $this->login($filter, trans('auth.validation.password'));
        }
    }

    public function handleRegister(
        Request $request,
        Rules\Captcha $captcha,
        Dispatcher $dispatcher,
        Filter $filter
    ) {
        $can = $filter->apply('can_register', null);
        if ($can instanceof Rejection) {
            return json($can->getReason(), 1);
        }

        $rule = option('register_with_player_name') ?
            [
                'player_name' => [
                    'required',
                    new Rules\PlayerName(),
                    'min:' . option('player_name_length_min'),
                    'max:' . option('player_name_length_max'),
                ]
            ] :
            ['nickname' => 'required|max:255'];
        $data = $request->validate(array_merge([
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|max:32',
        ], $rule));
        $playerName = $request->input('player_name');

        $dispatcher->dispatch('auth.registration.attempt', [$data]);

        if (
            option('register_with_player_name') &&
            Player::where('name', $playerName)->count() > 0
        ) {
            return $this->login($filter, trans('user.player.add.repeated'));
        }

        // If amount of registered accounts of IP is more than allowed amount,
        // reject this registration.
        $whip = new Whip();
        $ip = $whip->getValidIpAddress();
        $ip = $filter->apply('client_ip', $ip);
        if (User::where('ip', $ip)->count() >= option('regs_per_ip')) {
            return $this->login($filter, trans('auth.register.max', ['regs' => option('regs_per_ip')]));
        }

        $dispatcher->dispatch('auth.registration.ready', [$data]);

        $user = new User();
        $user->email = $data['email'];
        $user->nickname = $data[option('register_with_player_name') ? 'player_name' : 'nickname'];
        $user->score = option('user_initial_score');
        $user->avatar = 0;
        $password = app('cipher')->hash($data['password'], config('secure.salt'));
        $password = $filter->apply('user_password', $password);
        $user->password = $password;
        $user->ip = $ip;
        $user->permission = User::NORMAL;
        $user->register_at = Carbon::now();
        $user->last_sign_at = Carbon::now()->subDay();
        $user->save();

        $dispatcher->dispatch('auth.registration.completed', [$user]);
        event(new Events\UserRegistered($user));

        if (option('register_with_player_name')) {
            $dispatcher->dispatch('player.adding', [$playerName, $user]);

            $player = new Player();
            $player->uid = $user->uid;
            $player->name = $playerName;
            $player->tid_skin = 0;
            $player->save();

            $dispatcher->dispatch('player.added', [$player, $user]);
            event(new Events\PlayerWasAdded($player));
        }

        $dispatcher->dispatch('auth.login.ready', [$user]);
        Auth::login($user);
        $dispatcher->dispatch('auth.login.succeeded', [$user]);

        // return json(trans('auth.register.success'), 0);
        return redirect(url('/user'));

    }
    /**
     * ncwu统一认证
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     */
    public static function authserver(Request $request): JsonResponse
    {
        $request->validate([
            'identification' => 'required|string',
            'password' => 'required|string',
        ]);

        try {
            $authService = new AuthLoginService(
                $request->input('identification'),
                $request->input('password')
            );

            $cookies = $authService->login();

            return json([
                'success' => true,
                'message' => '登录成功',
                'cookies' => $cookies
            ]);

        } catch (\Exception $e) {
            return json([
                'success' => false,
                'message' => '登录失败: ' . $e->getMessage()
            ]);
        }
    }
}
