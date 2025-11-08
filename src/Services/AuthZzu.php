<?php
namespace Blessing\HAuth\Services;
use Blessing\HAuth\Services\BaseAuth;

use Blessing\HAuth\Consts\Schools;

use Illuminate\Support\Facades\Http;
use GuzzleHttp\Cookie\CookieJar;

use Blessing\HAuth\Utils\JSEncrypt;
use Illuminate\Support\Str;
class AuthZzu extends BaseAuth{
    public function login():array{

        $login_url=Schools::LOGIN_URL['zzu'];
        $cookieJar = new CookieJar();
        $browserHeaders = [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                'Accept-Language' => 'zh-CN,zh;q=0.9,en;q=0.8',
                'Upgrade-Insecure-Requests' => '1',
                'Sec-Fetch-Dest' => 'document', // ⬅️ 额外添加更多浏览器头
                'Sec-Fetch-Mode' => 'navigate',
                'Sec-Fetch-Site' => 'none',
                'Sec-Fetch-User' => '?1',
            ];
        $domain='cas.s.zzu.edu.cn';
        try{
            $response=Http::withHeaders($browserHeaders)
            ->withOptions(['cookies'=>$cookieJar])
            ->get($login_url);
            // execution
            preg_match('/name="execution" value="([^"]*)"/', $response->body(), $executionMatch);
            // encrypt password
            $encrypt = new JSEncrypt();
            $publicKey = Http::withHeaders($browserHeaders)
                ->withOptions(['cookies' => $cookieJar])
                ->get('https://cas.s.zzu.edu.cn/cas/jwt/publicKey')->body();
            $encrypt->setPublicKey($publicKey);
            $encodedPassword = '__RSA__' . $encrypt->encrypt($this->form_request->input('password'));
            // fingerprint visitor id
            $fpVistorId = $this->form_request->input('fpVistorId');

            $formData = [
                'username' => $this->form_request->input('identification'),
                'password' => $encodedPassword,
                'captcha' => '',
                'currentMenu' => '1',
                'failN' => '0',
                'mfaState' => '',
                'execution' => $executionMatch[1] ?? '',
                '_eventId' => 'submit',
                'geolocation' => '',
                'fpVistorId' => $fpVistorId,
                'submit1' => 'Login1',
            ];
            $response = Http::withHeaders($browserHeaders)
                ->withoutRedirecting()
                ->withOptions(['cookies' => $cookieJar])
                ->asForm()
                ->post($login_url, $formData);
            // dd($formData,$response->body(),$cookieJar);
            // dd($cookieJar->getCookieByName("CAS_TRACE_ID"));
        }catch(\Exception $e){
            throw new \Exception('认证失败:'.$e->getMessage());
        }
        
        if (
            $cookieJar->getCookieByName("CAS_TRACE_ID")
        ) {
            return $cookieJar->toArray();
        } else {
            throw new \Exception('登录失败，请检查用户名和密码');
        }
    }
}