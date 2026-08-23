<?php

namespace Blessing\HAuth\Schools;

use Blessing\HAuth\Utils\RSAUtils;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\Http;

class NcwuAuth implements SchoolAuth
{
    private const LOGIN_URL = 'https://authserver.ncwu.edu.cn/authserver/login';
    private const RSA_EXPONENT = '010001';
    private const RSA_MODULUS = '008aed7e057fe8f14c73550b0e6467b023616ddc8fa91846d2613cdb7f7621e3cada4cd5d812d627af6b87727ade4e26d26208b7326815941492b2204c3167ab2d53df1e3a2c9153bdb7c8c2e968df97a5e7e01cc410f92c4c2c2fba529b3ee988ebc1fca99ff5119e036d732c368acf8beba01aa2fdafa45b21e4de4928d0d403';

    public function login(string $username, string $password): bool
    {
        $cookieJar = new CookieJar();
        $response = Http::withHeaders($this->headers())
            ->withOptions(['cookies' => $cookieJar, 'timeout' => 30, 'connect_timeout' => 10])
            ->get(self::LOGIN_URL);

        if (!preg_match('/name=["\']execution["\'][^>]*value=["\']([^"\']+)/i', $response->body(), $execution)) {
            throw new \RuntimeException('华水统一认证页面格式已变化：缺少 execution');
        }

        [$exponent, $modulus] = $this->readPublicKey($response->body(), $cookieJar);
        $key = RSAUtils::getKeyPair($exponent, '', $modulus);
        $encryptedPassword = str_replace(' ', '', RSAUtils::encryptedString($key, $password));

        $response = Http::withHeaders($this->headers())
            ->withOptions([
                'cookies' => $cookieJar,
                'allow_redirects' => false,
                'timeout' => 30,
                'connect_timeout' => 10,
            ])
            ->asForm()
            ->post(self::LOGIN_URL, [
                'username' => $username,
                'password' => strtolower($encryptedPassword),
                'execution' => $execution[1],
                'encrypted' => 'true',
                '_eventId' => 'submit',
                'loginType' => '1',
            ]);

        return $response->status() === 302 || $cookieJar->getCookieByName('CASTGC') !== null;
    }

    private function readPublicKey(string $html, CookieJar $cookieJar): array
    {
        if (preg_match_all('/<script[^>]+src=["\']([^"\']+)["\']/i', $html, $scripts)) {
            foreach ($scripts[1] as $script) {
                if (stripos($script, 'login') === false) {
                    continue;
                }
                $scriptUrl = $this->resolveUrl($script);
                $javascript = Http::withHeaders($this->headers())
                    ->withOptions(['cookies' => $cookieJar, 'timeout' => 30, 'connect_timeout' => 10])
                    ->get($scriptUrl)
                    ->body();
                if (preg_match('/RSAUtils\.getKeyPair\(["\']([^"\']+)["\']\s*,\s*["\'][^"\']*["\']\s*,\s*["\']([^"\']+)/', $javascript, $key)) {
                    return [$key[1], $key[2]];
                }
            }
        }

        return [self::RSA_EXPONENT, self::RSA_MODULUS];
    }

    private function resolveUrl(string $url): string
    {
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }
        if (strpos($url, '/') === 0) {
            return 'https://authserver.ncwu.edu.cn' . $url;
        }

        return 'https://authserver.ncwu.edu.cn/authserver/' . $url;
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
