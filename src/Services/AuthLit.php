<?php
namespace Blessing\HAuth\Services;
use Blessing\HAuth\Services\BaseAuth;

use Blessing\HAuth\Consts\Schools;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\Http;

use Symfony\Component\Process\Process;
use Blessing\HAuth\Utils\LitAESUtil;
class AuthLit extends BaseAuth
{
    public function login(): array
    {

        $cookieJar = $this->auth_login();
        if (!$cookieJar->getCookieByName('TGC')) {
            throw new \Exception("认证失败,用户名或密码错误");
        }
        return $cookieJar->toArray();
    }
    private function auth_login()
    {
        function log(string $msg)
        {
            echo '<div style="backgroud-color: #3fe7a1ff">' . $msg . '</div>';
        }

        $login_url = Schools::LOGIN_URL['lit'];
        $cookieJar = new CookieJar;
        $browserHeaders = Schools::BROWSER_HEADERS;

        // 001 GET
        $response = Http::withHeaders($browserHeaders)
            ->withOptions(['cookies' => $cookieJar])
            ->get($login_url);
        // execution
        preg_match('/name="execution" value="([^"]*)"/', $response->body(), $executionMatch);
        // salt
        preg_match('/id="salt" value="([^"]*)"/', $response->body(), $saltMatch);
        // captcha
        $verify_info=json_decode("{\"code\":400}",true);
        // 限制次数
        $verify_times=7;
        while ($verify_info['code']!=200 && $verify_times>0) {
            $verify_times--;
            $gen_captcha_url = 'http://ids.lit.edu.cn/authserver/captcha/gen?type=SLIDER';
            $response = Http::withHeaders($browserHeaders)
                ->withOptions(['cookies' => $cookieJar])
                ->post($gen_captcha_url);
            $captcha_json_string = $response->body();
            $captcha_info = json_decode($captcha_json_string, true);

            // 外部调用opencv-python
            $python_script = 'resources/py/match_lit.py';
            $python_script = dirname(__DIR__) . DIRECTORY_SEPARATOR . $python_script;
            $python_executable = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . "assets/py_venv/bin/python3";
            $args = [
                $python_executable,
                $python_script,
                $captcha_json_string,
            ];
            $process = new Process($args);
            $process->run();
            if (!$process->isSuccessful()) {
                log("python进程失败!");
                // 打印标准错误输出
                log("STDERR: " . $process->getErrorOutput());
                log("STDOUT: " . $process->getOutput());
            }
            $slider_info = json_decode($process->getOutput(), true);
            // check captcha
            $check_captcha_url = 'http://ids.lit.edu.cn/authserver/captcha/check';
            $response = Http::withHeaders($browserHeaders)
                ->withOptions(['cookies' => $cookieJar])
                ->post($check_captcha_url, $slider_info);
            $verify_info = json_decode($response->body(), true);
        }

        // encrypt password
        $password = $this->form_request->input('password');
        $encrypted_password = LitAESUtil::encrypt($password, $saltMatch[1]);

        // login
        $login_post_data = [
            'username' => $this->form_request->input('identification'),
            'password' => $encrypted_password,
            'execution' => $executionMatch[1],
            '_eventId' => 'submit',
            'captcha' => $verify_info['data']['id'],
        ];
        $response = Http::withHeaders($browserHeaders)
            ->withOptions(['cookies' => $cookieJar])
            ->asForm()
            ->post($login_url, $login_post_data);

        return $cookieJar;

        // // get info
        // $auth_info_url='http://ids.lit.edu.cn/authserver/v1/personal/user/info';
        // $response=Http::withHeaders($browserHeaders)
        // ->withOptions(['cookies'=>$cookieJar])
        // ->get($auth_info_url);
        // $auth_info=json_decode($response->body(),true);

        // return $auth_info;
    }
}