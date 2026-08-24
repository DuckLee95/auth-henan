<?php

namespace Blessing\HAuth\Schools;

use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\Http;

class ZzuAuth implements SchoolAuth
{
    private const BASE_URL = 'https://cas.s.zzu.edu.cn';
    private const LOGIN_URL = self::BASE_URL . '/cas/a/login';
    private const PUBLIC_KEY_URL = self::BASE_URL . '/cas/jwt/publicKey';
    private const MFA_DETECT_URL = self::BASE_URL . '/cas/mfa/detect';

    public function login(string $username, string $password): bool
    {
        $cookieJar = new CookieJar();
        $response = Http::withHeaders($this->headers())
            ->withOptions([
                'cookies' => $cookieJar,
                'timeout' => 30,
                'connect_timeout' => 10,
            ])
            ->get(self::LOGIN_URL);

        if (!$response->successful()) {
            throw new \RuntimeException('无法访问郑州大学统一身份认证登录页');
        }

        $execution = $this->inputValue($response->body(), 'execution');
        if ($execution === null || $execution === '') {
            throw new \RuntimeException('郑州大学统一身份认证登录页缺少会话令牌');
        }

        $encryptedPassword = $this->encryptPassword($password, $cookieJar);
        $mfaState = $this->detectMfa($username, $encryptedPassword, $cookieJar);

        $response = Http::withHeaders(array_merge($this->headers(), [
            'Origin' => self::BASE_URL,
            'Referer' => self::LOGIN_URL,
        ]))
            ->withOptions([
                'cookies' => $cookieJar,
                'allow_redirects' => false,
                'timeout' => 30,
                'connect_timeout' => 10,
            ])
            ->asForm()
            ->post(self::LOGIN_URL, [
                'username' => $username,
                'password' => $encryptedPassword,
                'captcha' => '',
                'currentMenu' => '1',
                'failN' => '0',
                'mfaState' => $mfaState,
                'execution' => $execution,
                '_eventId' => 'submit',
                'geolocation' => '',
                'fpVisitorId' => '',
                'trustAgent' => '',
                'submit1' => 'Login1',
            ]);

        $body = $response->body();
        $errors = $this->loginErrors($body);
        $errorText = implode("\n", $errors);
        if ($response->status() === 401
            && preg_match('/账号或密码错误|用户名或密码错误|账号不存在|用户不存在|密码错误/u', $errorText)) {
            return false;
        }

        if ($this->hasTicketGrantingCookie($cookieJar)) {
            return true;
        }

        $location = (string) $response->header('Location');
        if ($response->status() >= 300
            && $response->status() < 400
            && strpos($location, 'ticket=ST-') !== false) {
            return true;
        }

        if ($response->successful()
            && strpos($body, 'id="fm1"') === false
            && strpos($body, '登录成功') !== false) {
            return true;
        }

        if (strpos($errorText, '验证码') !== false) {
            throw new \RuntimeException('郑州大学统一身份认证当前要求验证码，暂时无法自动认证');
        }

        throw new \RuntimeException(
            '郑州大学统一身份认证返回了无法识别的登录结果（状态码 ' . $response->status() . '）'
        );
    }

    private function encryptPassword(string $password, CookieJar $cookieJar): string
    {
        if (!function_exists('openssl_pkey_get_public')
            || !function_exists('openssl_public_encrypt')) {
            throw new \RuntimeException('PHP OpenSSL 扩展不可用，无法认证郑州大学账号');
        }

        $response = Http::withHeaders(array_merge($this->headers(), [
            'Referer' => self::LOGIN_URL,
        ]))
            ->withOptions([
                'cookies' => $cookieJar,
                'timeout' => 30,
                'connect_timeout' => 10,
            ])
            ->get(self::PUBLIC_KEY_URL);

        $publicKey = trim($response->body());
        if (!$response->successful() || $publicKey === '') {
            throw new \RuntimeException('郑州大学统一身份认证未返回密码加密公钥');
        }

        $key = openssl_pkey_get_public($publicKey);
        $encrypted = '';
        if ($key === false
            || !openssl_public_encrypt($password, $encrypted, $key, OPENSSL_PKCS1_PADDING)) {
            throw new \RuntimeException('无法加密郑州大学统一身份认证密码');
        }

        return '__RSA__' . base64_encode($encrypted);
    }

    private function hasTicketGrantingCookie(CookieJar $cookieJar): bool
    {
        return $cookieJar->getCookieByName('TGC') !== null
            || $cookieJar->getCookieByName('CASTGC') !== null;
    }

    private function loginErrors(string $html): array
    {
        if (!preg_match('/\bvar\s+errors\s*=\s*(\[[^;]*\])\s*;/i', $html, $match)) {
            return [];
        }

        $errors = json_decode($match[1], true);
        if (!is_array($errors)) {
            return [];
        }

        return array_map('strval', $errors);
    }

    private function detectMfa(string $username, string $password, CookieJar $cookieJar): string
    {
        $response = Http::withHeaders(array_merge($this->headers(), [
            'Accept' => 'application/json, text/javascript, */*; q=0.01',
            'Origin' => self::BASE_URL,
            'Referer' => self::LOGIN_URL,
            'X-Requested-With' => 'XMLHttpRequest',
        ]))
            ->withOptions([
                'cookies' => $cookieJar,
                'timeout' => 30,
                'connect_timeout' => 10,
            ])
            ->asForm()
            ->post(self::MFA_DETECT_URL, [
                'username' => $username,
                'password' => $password,
                'fpVisitorId' => '',
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('郑州大学统一身份认证安全检测接口暂时不可用');
        }

        $result = json_decode($response->body(), true);
        if (!is_array($result)
            || (string) ($result['code'] ?? '') !== '0'
            || !isset($result['data'])
            || !is_array($result['data'])) {
            throw new \RuntimeException('郑州大学统一身份认证返回了无法识别的安全检测结果');
        }

        if (($result['data']['need'] ?? false) === true) {
            throw new \RuntimeException('郑州大学统一身份认证要求额外安全验证，暂时无法自动认证');
        }

        $state = (string) ($result['data']['state'] ?? '');
        if ($state === '') {
            throw new \RuntimeException('郑州大学统一身份认证安全检测未返回状态令牌');
        }

        return $state;
    }

    private function inputValue(string $html, string $name): ?string
    {
        if (!preg_match(
            '/<input\b(?=[^>]*\bname=["\']' . preg_quote($name, '/') . '["\'])[^>]*>/i',
            $html,
            $input
        )) {
            return null;
        }

        if (!preg_match('/\bvalue=["\']([^"\']*)["\']/i', $input[0], $value)) {
            return '';
        }

        return html_entity_decode($value[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function headers(): array
    {
        return [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/126 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'zh-CN,zh;q=0.9,en;q=0.8',
        ];
    }
}
