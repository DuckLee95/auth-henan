<?php

namespace Blessing\HAuth\Services;

use Illuminate\Support\Facades\Http;

use Blessing\HAuth\Utils\CookieUtil;

use Illuminate\Http\Request;

/**
 * 统一身份认证服务类
 */
class BaseAuth
{
    protected $username;
    protected $password;
    protected $cookies = [];
    protected $client;
    protected $domain;
    protected $form_request;

    public function __construct(string $username, string $password)
    {
        $this->username = $username;
        $this->password = $password;
        $this->client = Http::withOptions([
            'timeout' => 30,
            'connect_timeout' => 10
        ])
            ->withHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36'
            ]);
    }
    public function setFromRequest(Request $request){
        $this->form_request=$request;
    }
    /**
     * 执行登录
     */
    public function login(): array
    {
        throw new \Exception('登录失败，未实现');
    }
    protected function saveCookies($jar){
        $this->cookies=array_merge($this->cookies, CookieUtil::getCookies($jar));
    }
}