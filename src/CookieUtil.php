<?php
namespace Blessing\HAuth;

use GuzzleHttp\Cookie\CookieJar;
class CookieUtil
{
    /**
     * 请求头中的Cookie参数
     * @return void
     */
    public static function getCookieHeader($cookies): array
    {
        return ['Cookie' => CookieUtil::buildCookieString($cookies)];
    }
    /**
     * 拼接cookie字符串
     * @param array $cookies
     * @return string
     */
    public static function buildCookieString(array $cookies): string
    {
        if (empty($cookies)) {
            return '';
        }
        $cookieParts = [];
        foreach ($cookies as $key => $value) {
            $cookieParts[] = "{$key}={$value}";
        }
        return implode('; ', $cookieParts);
    }
    /**
     * 解析request中cookies
     */
    public static function getCookies(CookieJar $cookieJar): array
    {
        $cookies = [];
        foreach ($cookieJar->toArray() as $i => $cookie) {
            $cookies[$cookie['Name']] = $cookie['Value'];
        }
        return $cookies;
    }
}