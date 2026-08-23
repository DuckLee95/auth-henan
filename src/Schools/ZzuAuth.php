<?php

namespace Blessing\HAuth\Schools;

use Blessing\HAuth\Utils\JSEncrypt;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\Http;

class ZzuAuth implements SchoolAuth
{
    private const LOGIN_URL = 'https://cas.s.zzu.edu.cn/cas/a/login';

    public function login(string $username, string $password): bool
    {
        $cookieJar = new CookieJar();
        $headers = $this->headers();

        $response = Http::withHeaders($headers)
            ->withOptions(['cookies' => $cookieJar, 'timeout' => 30, 'connect_timeout' => 10])
            ->get(self::LOGIN_URL);
        if (!preg_match('/name="execution" value="([^"]*)"/', $response->body(), $execution)) {
            throw new \RuntimeException('郑大统一认证页面格式已变化：缺少 execution');
        }

        $publicKey = Http::withHeaders($headers)
            ->withOptions(['cookies' => $cookieJar, 'timeout' => 30, 'connect_timeout' => 10])
            ->get('https://cas.s.zzu.edu.cn/cas/jwt/publicKey')
            ->body();
        $encrypt = new JSEncrypt();
        $encrypt->setPublicKey($publicKey);

        Http::withHeaders($headers)
            ->withoutRedirecting()
            ->withOptions(['cookies' => $cookieJar, 'timeout' => 30, 'connect_timeout' => 10])
            ->asForm()
            ->post(self::LOGIN_URL, [
                'username' => $username,
                'password' => '__RSA__' . $encrypt->encrypt($password),
                'captcha' => '',
                'currentMenu' => '1',
                'failN' => '0',
                'mfaState' => '',
                'execution' => $execution[1],
                '_eventId' => 'submit',
                'geolocation' => '',
                'fpVistorId' => (string) request()->input('fpVistorId', ''),
                'submit1' => 'Login1',
            ]);

        return $cookieJar->getCookieByName('CAS_TRACE_ID') !== null;
    }

    private function headers(): array
    {
        return [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/100 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'zh-CN,zh;q=0.9,en;q=0.8',
        ];
    }
}
