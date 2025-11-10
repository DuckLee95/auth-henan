<?php
namespace Blessing\HAuth\Services;
use Blessing\HAuth\Services\BaseAuth;

use Blessing\HAuth\Consts\Schools;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\Http;

use Symfony\Component\Process\Process;
use Blessing\HAuth\Utils\HtuAESUtil;
class AuthHtu extends BaseAuth{
    public function login():array{
        function log(string $msg)
        {
            echo '<div style="backgroud-color: #3fe7a1ff">' . $msg . '</div>';
        }
        $login_url = Schools::LOGIN_URL['htu'];
        $cookieJar = new CookieJar();
        $browserHeaders = Schools::BROWSER_HEADERS;
        // 001
        $get_online_members='https://jwc.htu.edu.cn/new/login/getOnlineMembers';
        $response=Http::withHeaders($browserHeaders)
        ->withOptions(['cookies'=>$cookieJar])
        ->post($get_online_members);
        $response = Http::withHeaders($browserHeaders)
            ->withOptions(['cookies' => $cookieJar])
            ->get($login_url);
        // captcha
        $gen_captcha_url='https://jwc.htu.edu.cn/yzm';
        $response=Http::withHeaders($browserHeaders)
        ->withOptions(['cookies'=>$cookieJar])
        ->get($gen_captcha_url,['d'=>round(microtime(true)*1000)]);
        $response=Http::withHeaders($browserHeaders)
        ->withOptions(['cookies'=>$cookieJar])
        ->get($gen_captcha_url,['d'=>round(microtime(true)*1000)]);
        $image_base64_string=base64_encode($response->body());
        // 外部调用opencv-python
        $python_script = 'resources/py/match_htu.py';
        $python_script = dirname(__DIR__) . DIRECTORY_SEPARATOR . $python_script;
        $python_executable = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . "assets/py_venv/bin/python3";
        $args = [
            $python_executable,
            $python_script,
            $image_base64_string,
        ];
        $process = new Process($args);
        $process->run();
        if (!$process->isSuccessful()) {
            log("python进程失败!");
            // 打印标准错误输出
            log("STDERR: " . $process->getErrorOutput());
            log("STDOUT: " . $process->getOutput());
        }
        $verify_code = $process->getOutput();
        // encode password
        $password=$this->form_request->input('password');
        $encrypted_password=HtuAESUtil::encryptPassword($password,$verify_code);
        // login
        $post_data=[
            'account'=>$this->form_request->input('identification'),
            'pwd'=>$encrypted_password,
            'verifycode'=>$verify_code,
        ];
        $login_url='https://jwc.htu.edu.cn/new/login';
        $response=Http::withHeaders($browserHeaders)
        ->withOptions(['cookies'=>$cookieJar])
        ->asForm()
        ->post($login_url, $post_data);
        $login_info=json_decode($response->body(),true);

        if(!$login_info["code"]==0){
            throw new \Exception("认证失败,用户名或密码错误(也可能验证码识别失败)");
        }
        return $cookieJar->toArray();
    }
}