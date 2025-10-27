<?php

namespace Blessing\HAuth;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

use Blessing\HAuth\RSAUtils;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;

/**
 * 统一身份认证服务类
 */
class AuthLoginService
{
    private $username;
    private $password;
    private $cookies = [];
    private $client;
    private $jar;
    private $domain = 'authserver.ncwu.edu.cn';

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
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ]);

        $this->jar = new CookieJar;
    }

    /**
     * 获取session,登录所需参数 (execution)
     */
    public function getLoginParams(): ?array
    {
        //获取session
        $response = $this->client->get('https://authserver.ncwu.edu.cn/authserver/login');
        $this->saveCookies($response->cookies());

        $url = 'https://authserver.ncwu.edu.cn/authserver/login?service=https%3A%2F%2Fsec.ncwu.edu.cn%2Frump_frontend%2FloginFromCas%2F';
        $response = $this->client
            ->withCookies($this->cookies, $this->domain)
            ->get($url);
        $html = $response->body();

        // 正则匹配execution
        preg_match('/name="execution" value="([^"]*)"/', $html, $executionMatch);

        if (isset($executionMatch[1])) {
            return [
                'execution' => $executionMatch[1],
            ];
        }
        return null;
    }
    /**
     * 执行登录
     */
    public function login(): array
    {
        $params = $this->getLoginParams();
        if (!$params) {
            throw new \Exception('获取登录参数失败');
        }

        $url_login_js = "https://authserver.ncwu.edu.cn/authserver/themes/sudy_default/js/login.js";
        $js_login = $this->client->get($url_login_js)->body();

        // 密码加密
        preg_match('/RSAUtils\.getKeyPair\(\s*"([^"]+)"\s*,\s*\'([^\']*)\'\s*,\s*"([^"]+)"\s*\)/', $js_login, $keyMatch);
        $key = RSAUtils::getKeyPair($keyMatch[1], $keyMatch[2], $keyMatch[3]);
        $encrypted_password = RSAUtils::encryptedString($key, $this->password);

        // 构建登录数据
        $postData = [
            'username' => $this->username,
            'password' => $encrypted_password,
            'execution' => $params['execution'],
            'encrypted' => true,
            '_eventId' => 'submit',
            'loginType' => '1'
        ];

        $login_url = 'https://authserver.ncwu.edu.cn/authserver/login';
        // 发送登录请求
        $response = $this->client
            ->withHeaders($this->getCookieHeader())
            ->asForm()
            ->post($login_url, $postData);
        // 更新cookies
        $this->saveCookies($response->cookies());

        // 携带cookies GET loign_url
        $response = $this->client
            ->withHeaders($this->getCookieHeader())
            ->asForm()
            ->get($login_url);


        // 检查登录是否成功
        if ($response->status() !== 200) {
            throw new \Exception('登录请求失败，状态码: ' . $response->status());
        }

        // 可以根据响应内容进一步判断登录是否成功
        if (
            Str::contains($response->body(), '登录成功') ||
            Str::contains($response->body(), 'success')
        ) {
            return $this->cookies;
        } else {
            throw new \Exception('登录失败，请检查用户名和密码');
        }
    }
    /**
     * 请求头中的Cookie参数
     * @return void
     */
    private function getCookieHeader(): array
    {
        return ['Cookie' => $this->buildCookieString($this->cookies)];
    }
    /**
     * 拼接cookie字符串
     * @param array $cookies
     * @return string
     */
    private function buildCookieString(array $cookies): string
    {
        if (empty($cookies)) {
            return '';
        }
        $cookieParts = [];
        foreach ($this->cookies as $key => $value) {
            $cookieParts[] = "{$key}={$value}";
        }
        return implode('; ', $cookieParts);
    }
    /**
     * 保存cookie到成员变量
     * @param \GuzzleHttp\Cookie\CookieJar $jar
     * @return void
     */
    private function saveCookies(CookieJar $jar)
    {
        $this->cookies = array_merge($this->cookies, AuthLoginService::getCookies($jar));
    }
    /**
     * 解析request中cookies
     */
    private static function getCookies(CookieJar $cookieJar): array
    {
        $cookies = [];
        foreach ($cookieJar->toArray() as $i => $cookie) {
            $cookies[$cookie['Name']] = $cookie['Value'];
        }
        return $cookies;
    }
}