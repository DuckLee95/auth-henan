<?php

namespace Blessing\HAuth\Services;

use Blessing\HAuth\Consts\Schools;
use Blessing\HAuth\Utils\RSAUtils;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\Http;

class AuthNcwu extends BaseAuth
{
    private const RSA_EXPONENT = '010001';
    private const RSA_MODULUS = '008aed7e057fe8f14c73550b0e6467b023616ddc8fa91846d2613cdb7f7621e3cada4cd5d812d627af6b87727ade4e26d26208b7326815941492b2204c3167ab2d53df1e3a2c9153bdb7c8c2e968df97a5e7e01cc410f92c4c2c2fba529b3ee988ebc1fca99ff5119e036d732c368acf8beba01aa2fdafa45b21e4de4928d0d403';

    public function login(): array
    {
        $cookieJar = new CookieJar();
        $loginUrl = Schools::LOGIN_URL['ncwu'];
        $response = Http::withHeaders(Schools::BROWSER_HEADERS)
            ->withOptions(['cookies' => $cookieJar])
            ->get($loginUrl);

        if (!preg_match('/name=["\']execution["\'][^>]*value=["\']([^"\']+)/i', $response->body(), $execution)) {
            throw new \RuntimeException('华水统一认证页面格式已变化：缺少 execution');
        }

        [$exponent, $modulus] = $this->readPublicKey($response->body(), $loginUrl, $cookieJar);
        $key = RSAUtils::getKeyPair($exponent, '', $modulus);
        $encryptedPassword = str_replace(' ', '', RSAUtils::encryptedString($key, $this->password));

        $response = Http::withHeaders(Schools::BROWSER_HEADERS)
            ->withOptions(['cookies' => $cookieJar, 'allow_redirects' => false])
            ->asForm()
            ->post($loginUrl, [
                'username' => $this->username,
                'password' => strtolower($encryptedPassword),
                'execution' => $execution[1],
                'encrypted' => 'true',
                '_eventId' => 'submit',
                'loginType' => '1',
            ]);

        if ($response->status() !== 302 && !$cookieJar->getCookieByName('CASTGC')) {
            throw new \RuntimeException('登录失败，请检查用户名和密码');
        }
        return ['auth_type' => 'nodriver_managed'];
    }

    private function readPublicKey(string $html, string $loginUrl, CookieJar $cookieJar): array
    {
        if (preg_match_all('/<script[^>]+src=["\']([^"\']+)["\']/i', $html, $scripts)) {
            foreach ($scripts[1] as $script) {
                if (stripos($script, 'login') === false) {
                    continue;
                }
                if (preg_match('#^https?://#i', $script)) {
                    $scriptUrl = $script;
                } elseif (strpos($script, '/') === 0) {
                    $parts = parse_url($loginUrl);
                    $scriptUrl = $parts['scheme'] . '://' . $parts['host'] . $script;
                } else {
                    $scriptUrl = rtrim(dirname($loginUrl), '/') . '/' . $script;
                }
                $javascript = Http::withHeaders(Schools::BROWSER_HEADERS)
                    ->withOptions(['cookies' => $cookieJar])
                    ->get($scriptUrl)
                    ->body();
                if (preg_match('/RSAUtils\.getKeyPair\(["\']([^"\']+)["\']\s*,\s*["\'][^"\']*["\']\s*,\s*["\']([^"\']+)/', $javascript, $key)) {
                    return [$key[1], $key[2]];
                }
            }
        }
        return [self::RSA_EXPONENT, self::RSA_MODULUS];
    }
}
