<?php

namespace Blessing\HAuth;

use App\Events;
use App\Models\Player;
use App\Models\User;
use App\Rules;
use Auth;
use Blessing\Filter;
use Blessing\Rejection;
use Carbon\Carbon;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use Vectorface\Whip\Whip;

class HAuthController
{
    public function login(Filter $filter, string $msg = '', array $userData = [])
    {
        return view('Blessing\HAuth::auth.login', [
            'rows' => [
                'Blessing\HAuth::auth.rows.login.notice',
                'Blessing\HAuth::auth.rows.login.form',
                'Blessing\HAuth::auth.rows.login.message',
            ],
            'schools' => SchoolRegistry::names(),
            'msg' => $msg,
            'user_data' => $userData,
        ]);
    }

    public function register(Filter $filter, string $msg = '', array $userData = [])
    {
        return view('Blessing\HAuth::auth.register', [
            'rows' => [
                'Blessing\HAuth::auth.rows.register.notice',
                'Blessing\HAuth::auth.rows.register.form',
            ],
            'schools' => SchoolRegistry::names(),
            'extra' => [
                'recaptcha' => option('recaptcha_sitekey'),
                'invisible' => (bool) option('recaptcha_invisible'),
            ],
            'msg' => $msg,
            'user_data' => $userData,
        ]);
    }

    public function handleLogin(
        Request $request,
        Dispatcher $dispatcher,
        Filter $filter
    ) {
        $data = $request->validate([
            'school' => 'required|in:' . implode(',', array_keys(SchoolRegistry::SCHOOLS)),
            'identification' => 'required|string|max:255',
            'password' => 'required|string',
        ]);
        $userData = $request->only(['school', 'identification']);
        $email = $data['identification'] . SchoolRegistry::emailDomain($data['school']);

        $can = $filter->apply('can_login', null, [$email, $data['password']]);
        if ($can instanceof Rejection) {
            return $this->login($filter, $can->getReason(), $userData);
        }

        $dispatcher->dispatch('auth.login.attempt', [$email, $data['password'], 'email']);
        event(new Events\UserTryToLogin($email, 'email'));

        if ($message = $this->authenticate($data['school'], $data['identification'], $data['password'])) {
            return $this->login($filter, $message, $userData);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return $this->login(
                $filter,
                trans('Blessing\HAuth::auth.validation.unregistered'),
                $userData
            );
        }

        $dispatcher->dispatch('auth.login.ready', [$user]);

        if (!$user->verified) {
            $user->verified = true;
            $user->save();
        }

        Auth::login($user);

        $dispatcher->dispatch('auth.login.succeeded', [$user]);
        event(new Events\UserLoggedIn($user));

        return redirect($request->session()->pull('last_requested_path', url('/user')));
    }

    public function handleRegister(
        Request $request,
        Rules\Captcha $captcha,
        Dispatcher $dispatcher,
        Filter $filter
    ) {
        $can = $filter->apply('can_register', null);
        if ($can instanceof Rejection) {
            return $this->register($filter, $can->getReason());
        }

        $data = $request->validate([
            'school' => 'required|in:' . implode(',', array_keys(SchoolRegistry::SCHOOLS)),
            'identification' => 'required|string|max:255',
            'password' => 'required|string',
            'player_name' => [
                'required',
                new Rules\PlayerName(),
                'min:' . option('player_name_length_min'),
                'max:' . option('player_name_length_max'),
            ],
            'captcha' => ['required', $captcha],
        ]);
        $userData = $request->only(['school', 'identification', 'player_name']);

        if ($message = $this->authenticate($data['school'], $data['identification'], $data['password'])) {
            return $this->register($filter, $message, $userData);
        }

        $email = $data['identification'] . SchoolRegistry::emailDomain($data['school']);
        if (User::where('email', $email)->exists()) {
            return $this->register(
                $filter,
                trans('Blessing\HAuth::auth.validation.registered'),
                $userData
            );
        }

        if (Player::where('name', $data['player_name'])->exists()) {
            return $this->register($filter, trans('user.player.add.repeated'), $userData);
        }

        $whip = new Whip();
        $ip = $filter->apply('client_ip', $whip->getValidIpAddress());
        if (User::where('ip', $ip)->count() >= option('regs_per_ip')) {
            return $this->register(
                $filter,
                trans('auth.register.max', ['regs' => option('regs_per_ip')]),
                $userData
            );
        }

        $registrationData = [
            'email' => $email,
            'nickname' => $data['player_name'],
            'player_name' => $data['player_name'],
        ];
        $dispatcher->dispatch('auth.registration.attempt', [$registrationData]);
        $dispatcher->dispatch('auth.registration.ready', [$registrationData]);

        $user = new User();
        $user->email = $email;
        $user->nickname = $data['player_name'];
        $user->score = option('user_initial_score');
        $user->avatar = 0;
        $localPasswordHash = app('cipher')->hash(bin2hex(random_bytes(32)), config('secure.salt'));
        $user->password = $filter->apply('user_password', $localPasswordHash);
        $user->ip = $ip;
        $user->permission = User::NORMAL;
        $user->verified = true;
        $user->register_at = Carbon::now();
        $user->last_sign_at = Carbon::now()->subDay();
        $user->save();

        $dispatcher->dispatch('auth.registration.completed', [$user]);
        event(new Events\UserRegistered($user));

        $dispatcher->dispatch('player.adding', [$data['player_name'], $user]);

        $player = new Player();
        $player->uid = $user->uid;
        $player->name = $data['player_name'];
        $player->tid_skin = 0;
        $player->save();

        $dispatcher->dispatch('player.added', [$player, $user]);
        event(new Events\PlayerWasAdded($player));

        $dispatcher->dispatch('auth.login.ready', [$user]);
        Auth::login($user);
        $dispatcher->dispatch('auth.login.succeeded', [$user]);
        event(new Events\UserLoggedIn($user));

        return redirect(url('/user'));
    }

    private function authenticate(string $school, string $identification, string $password): ?string
    {
        try {
            if (!SchoolRegistry::login($school, $identification, $password)) {
                return trans('Blessing\HAuth::auth.validation.credentials');
            }
        } catch (\Throwable $e) {
            return trans('Blessing\HAuth::auth.validation.unavailable');
        }

        return null;
    }
}
