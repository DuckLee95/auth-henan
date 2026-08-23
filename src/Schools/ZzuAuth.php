<?php

namespace Blessing\HAuth\Schools;

use Illuminate\Support\Facades\Http;

class ZzuAuth implements SchoolAuth
{
    private const LOGIN_URL = 'https://jksb.v.zzu.edu.cn/vls6sss/zzujksb.dll/login';

    public function login(string $username, string $password): bool
    {
        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0',
        ])
            ->withoutRedirecting()
            ->withOptions(['timeout' => 30, 'connect_timeout' => 10])
            ->asForm()
            ->post(self::LOGIN_URL, [
                'uid' => $username,
                'upw' => $password,
            ]);

        $body = $response->body();

        return strpos($body, 'first6') !== false
            && strpos($body, 'vls6sss') !== false
            && strpos($body, '失败') === false;
    }
}
