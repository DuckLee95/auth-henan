<?php
namespace Blessing\HAuth\Services;
use Blessing\HAuth\Services\BaseAuth;
use Blessing\HAuth\Consts\Schools;

use Illuminate\Support\Facades\Http;
use GuzzleHttp\Cookie\CookieJar;

use Blessing\HAuth\Utils\JSEncrypt;
use Illuminate\Support\Str;

class AuthHaust extends BaseAuth
{
    public function login(): array
    {
        $login_url = Schools::LOGIN_URL['haust']; // 'https://cas.../cas/login'
        $vpn_domain = 'vpn.haust.edu.cn';
        $cookieJar = new CookieJar();
        $cookieJar = CookieJar::fromArray([
            'language' => 'zh-CN',
        ], $vpn_domain);

        $debug_steps = [];

        try {
            // 1. 模拟一个非常真实的浏览器头
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
            /**
             * needed params
             * username
             * password
             * captcha: (fixed)
             * currentMenu: 1 (fixed)
             * failN: -1 (fixed)
             * mfaState: (fixed)
             * execution
             * _eventId: submit
             * geolocation:
             * fpVistorId
             * submit1: Login1
             */
            $login_url = 'https://cas.haust.edu.cn/cas/login?service=https%3A%2F%2Fvpn.haust.edu.cn%3A443%2Fpassport%2Fv1%2Fauth%2Fcas%3FsfDomain%3Dxkp';
            $response = Http::withHeaders($browserHeaders)
                ->withOptions(['cookies' => $cookieJar])
                ->get($login_url);

            // execution
            preg_match('/name="execution" value="([^"]*)"/', $response->body(), $executionMatch);
            // encrypt password
            $encrypt = new JSEncrypt();
            $publicKey = Http::withHeaders($browserHeaders)
                ->withOptions(['cookies' => $cookieJar])
                ->get('https://cas.haust.edu.cn/cas/jwt/publicKey')->body();
            $encrypt->setPublicKey($publicKey);
            $encodedPassword = '__RSA__' . $encrypt->encrypt($this->form_request->input('password'));
            // fingerprint visitor id
            $fpVistorId = $this->form_request->input('fpVistorId');

            $formData = [
                'username' => $this->form_request->input('identification'),
                'password' => $encodedPassword,
                'captcha' => '',
                'currentMenu' => '1',
                'failN' => '-1',
                'mfaState' => '',
                'execution' => $executionMatch[1] ?? '',
                '_eventId' => 'submit',
                'geolocation' => '',
                'fpVistorId' => $fpVistorId,
                'submit1' => 'Login1',
            ];

            $post_url = 'https://cas.haust.edu.cn/cas/login?service=https%3A%2F%2Fvpn.haust.edu.cn%3A443%2Fpassport%2Fv1%2Fauth%2Fcas%3FsfDomain%3Dxkp';
            $response = Http::withHeaders($browserHeaders)
                ->withoutRedirecting()
                ->withOptions(['cookies' => $cookieJar])
                ->asForm()
                ->post($login_url, $formData);

            $redirect_url = $response->header('Location');
            $response = Http::withHeaders($browserHeaders)
                ->withoutRedirecting()
                ->withOptions(['cookies' => $cookieJar])
                ->get($redirect_url);
            $redirect_url = $response->header('Location');
            preg_match('/"ticket":"([^"]*)"/', urldecode($redirect_url), $ticketMatch);


            $auth_config_url = 'https://vpn.haust.edu.cn/passport/v1/public/authConfig?clientType=SDPBrowserClient&platform=Linux&lang=zh-CN&mod=1';
            $response = Http::withHeaders($browserHeaders)
                ->withOptions(['cookies' => $cookieJar])
                ->get($auth_config_url);


            $report_env_url = 'https://vpn.haust.edu.cn/controller/v1/public/reportEnv?clientType=SDPBrowserClient&platform=Linux&lang=zh-CN';
            $postData = json_decode('{"ticket":"","deviceId":"00803968cf0f31734101e934a405170617949b5a6020a4b53c01d20f78cd84a2b7","env":{"endpoint":{"device_id":"00803968cf0f31734101e934a405170617949b5a6020a4b53c01d20f78cd84a2b7","device":{"type":"browser"}}}}', true);
            $postData['ticket'] = $ticketMatch[1] ?? '';
            $response = Http::withHeaders($browserHeaders)
                ->withOptions(['cookies' => $cookieJar])
                ->post($report_env_url, $postData);

            $auth_check_url = 'https://vpn.haust.edu.cn/passport/v1/auth/authCheck?clientType=SDPBrowserClient&platform=Linux&lang=zh-CN';
            $response = Http::withHeaders($browserHeaders)
                ->withOptions(['cookies' => $cookieJar])
                ->get($auth_check_url);

            $login_url = 'https://cas.haust.edu.cn/cas/login';
            $response = Http::withHeaders($browserHeaders)
                ->withoptions(['cookies' => $cookieJar])
                ->get($login_url);

            // dd($response->body(),$response->header('Location'));

        } catch (\Exception $e) {
            throw new \Exception('异常发生: ' . $e->getMessage());
        }

        // preg_match('/name="execution" value="([^"]*)"/', $response->body(), $executionMatch);

        if (
            Str::contains($response->body(), '登录成功') ||
            Str::contains($response->body(), 'success')
        ) {
            return $cookieJar->toArray();
        } else {
            throw new \Exception('登录失败，请检查用户名和密码');
        }
    }
    /**
     * 测试使用,已弃用
     * @throws \Exception
     * @return never
     */
    public function _useless_login(): array
    {
        $login_url = Schools::LOGIN_URL['haust']; // 'https://cas.../cas/login'
        $vpn_domain = 'vpn.haust.edu.cn';
        $cookieJar = new CookieJar();
        $cookieJar = CookieJar::fromArray([
            'language' => 'zh-CN',
        ], $vpn_domain);

        $debug_steps = [];

        try {
            // 1. 模拟一个非常真实的浏览器头
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
            // my-test
            $login_url = 'https://cas.haust.edu.cn/cas/login?service=https%3A%2F%2Fvpn.haust.edu.cn%3A443%2Fpassport%2Fv1%2Fauth%2Fcas%3FsfDomain%3Dxkp';
            $response = Http::withHeaders($browserHeaders)
                ->withOptions(['cookies' => $cookieJar])
                ->get($login_url);

            dd($cookieJar->toArray(), $response->body());

            // --- 步骤 0: 预热会话，获取 vpn.haust.edu.cn 的前置 Cookies ---
            // 这一步的目的 *仅仅* 是为了让服务器在 $cookieJar 中设置 sid 等
            /*
            import {af as e, bq as n, bD as i, aY as a, h as o, bE as l, l as s, bF as r, E as u, j as d, bG as c, bH as f, bI as v, x as y, bJ as p, bK as h, bL as E, bM as m, u as C, I, bN as N, bO as T, bP as g, bQ as S, bR as A, bc as b, bS as k, bT as _, bU as R, aP as w, bV as D, bW as L, bX as O, bY as P, be as U, S as x, b0 as H, s as F, bZ as G, aQ as M, g as K, b_ as q, ad as V, B as $, b$ as j, c0 as W, d as Y, c1 as Q, aa as J, c2 as z, ab as B, c3 as X, ae as Z, c4 as tt, c5 as et, c6 as nt, c7 as it, c8 as at, a as ot, c9 as lt, bs as st, ca as rt, cb as ut, cc as dt, c as ct, cd as ft, ce as vt, v as yt, cf as pt, cg as ht, ch as Et, ci as mt, cj as Ct, ck as It, cl as Nt, cm as Tt, bu as gt, cn as St, co as At, cp as bt, cq as kt, t as _t, cr as Rt, cs as wt, ct as Dt, cu as Lt, cv as Ot, cw as Pt, cx as Ut} from "./iconfont.4bcb48a1.js";
import {T as xt} from "./time_clock.010318c5.js";
import {m as Ht, A as Ft} from "./utils_method.67372ca9.js";
import {w as Gt, x as Mt, y as Kt, t as qt, z as Vt, A as $t} from "./client.34a80640.js";
            */
            // 001
            $online_info_url = 'https://vpn.haust.edu.cn/passport/v1/user/onlineInfo?clientType=SDPBrowserClient&platform=Linux&lang=zh-CN';
            $response = Http::withHeaders($browserHeaders)
                ->withOptions(['cookies' => $cookieJar])
                ->get($online_info_url);

            // 002 - auth first
            $auth_config_url = 'https://vpn.haust.edu.cn/passport/v1/public/authConfig?clientType=SDPBrowserClient&platform=Linux&lang=zh-CN&needTicket=1';
            $response = Http::withHeaders($browserHeaders)
                // ->withoutRedirecting()
                ->withOptions(['cookies' => $cookieJar])
                ->get($auth_config_url);

            // 002 - auth secondary
            $auth_config_url = 'https://vpn.haust.edu.cn/passport/v1/public/authConfig?clientType=SDPBrowserClient&platform=Linux&lang=zh-CN&mod=1';

            //002 - report env
            $report_env_url = 'https://vpn.haust.edu.cn/controller/v1/public/reportEnv?clientType=SDPBrowserClient&platform=Linux&lang=zh-CN';

            // 002 - auth check
            $auth_check_url = 'https://vpn.haust.edu.cn/passport/v1/auth/authCheck?clientType=SDPBrowserClient&platform=Linux&lang=zh-CN';



            // dd($cookieJar->toArray(),json_decode($response->body(),true));

            $debug_steps[] = ['step' => 0, 'url' => $auth_config_url, 'status' => $response->status()];


            // 003
            // param:sfDomain=xkp
            // &ticket=ST-146172-QH1gPFk3BEudaBBrQ6HJQvnXsJIYqfOE
            $cas_url = 'https://vpn.haust.edu.cn/passport/v1/auth/cas';
            $response = Http::withHeaders($browserHeaders)
                ->withoutRedirecting()
                ->withOptions(['cookies' => $cookieJar])
                ->get($cas_url);


            $debug_steps[] = ['step' => 0, 'url' => $auth_config_url, 'status' => $response->status()];
            // 此时，$cookieJar 应该已经包含了 sid, straceid 等

            // --- 步骤 1: 真正开始 CAS 登录 (携带步骤0的 cookie) ---
            // 更新 Headers，因为 Referer 和 Fetch-Site 变了
            $headers_step1 = $browserHeaders + [
                'Sec-Fetch-Site' => 'cross-site', // 可能是 'same-origin' 或 'cross-site'
                'Referer' => $auth_config_url,
            ];

            $response = Http::withHeaders($headers_step1)
                ->withoutRedirecting()
                ->withOptions(['cookies' => $cookieJar])
                ->get($login_url);

            $location = $response->redirect() ? $response->header('Location') : null;
            $debug_steps[] = ['step' => 1, 'url' => $login_url, 'status' => $response->status(), 'location' => $location];

            if (!$location) {
                throw new \Exception('步骤1: 没有收到重定向。');
            }

            // --- 步骤 2: 访问 /verify (携带 sid 等 cookie) ---
            $current_url = $location; // 'https://vpn.../verify?t=...'
            $headers_step2 = $browserHeaders + [
                'Sec-Fetch-Site' => 'same-origin', // 现在是 vpn.haust.edu.cn 域内跳转
                'Referer' => $login_url, // Referer 是上一步的 cas 地址
            ];

            $response = Http::withHeaders($headers_step2)
                ->withoutRedirecting()
                ->withOptions(['cookies' => $cookieJar]) // 关键：携带 'sid'
                ->get($current_url);

            $location = $response->redirect() ? $response->header('Location') : null;
            // ⬇️ 检查这一步！
            // 如果成功，这里的 location 应该不再是 'shortcut.html'
            $debug_steps[] = ['step' => 2, 'url' => $current_url, 'status' => $response->status(), 'location' => $location];

            if ($location && strpos($location, 'shortcut.html') === false) {
                // --- 步骤 3: 成功！访问真正的登录页 ---
                $current_url = $location;
                if (strpos($current_url, 'http') !== 0) {
                    $current_url = 'https://' . $vpn_domain . $current_url;
                }

                $response = Http::withHeaders($browserHeaders)
                    ->withoutRedirecting()
                    ->withOptions(['cookies' => $cookieJar])
                    ->get($current_url);

                $debug_steps[] = ['step' => 3, 'url' => $current_url, 'status' => $response->status(), 'location' => $response->header('Location')];
            }

            // 打印所有步骤和 *所有* cookies
            dd($debug_steps, $cookieJar->toArray());

        } catch (\Exception $e) {
            dd('异常发生', $e->getMessage(), $debug_steps, $cookieJar->toArray());
        }

        // preg_match('/name="execution" value="([^"]*)"/', $response->body(), $executionMatch);

        // TODO
        if (
            // Str::contains($response->body(), '登录成功') ||
            // Str::contains($response->body(), 'success')
            false
        ) {
            return $cookieJar->toArray();
        } else {
            throw new \Exception('登录失败，请检查用户名和密码');
        }
    }

}