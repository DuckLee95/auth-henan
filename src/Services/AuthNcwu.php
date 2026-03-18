<?php

namespace Blessing\HAuth\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

use Blessing\HAuth\Utils\RSAUtils;
use Blessing\HAuth\Utils\CookieUtil;
use Blessing\HAuth\Services\BaseAuth;

/**
 * 统一身份认证服务类
 */
class AuthNcwu extends BaseAuth
{
    // private $username;
    // private $password;
    // private $cookies = [];
    // private $client;
    // private $domain = 'authserver.ncwu.edu.cn';

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
        $this->domain = 'authserver.ncwu.edu.cn';
    }

    /**
     * 获取session,登录所需参数 (execution)
     */
    public function getLoginParams(): ?array
    {
        //获取session
        $response = $this->client->get('https://authserver.ncwu.edu.cn/authserver/login');
        $this->cookies = array_merge($this->cookies, CookieUtil::getCookies($response->cookies()));

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

        $this->cookies->set('', $params['username'], $params['password']);
        // 外部调用python脚本
        $python_script = 'resources/py/match_ncwu.py';
        $python_script = dirname(__DIR__) . DIRECTORY_SEPARATOR . $python_script;
        $python_executable = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . "assets/py_venv/bin/python3";
        $args = [
            $python_executable,
            $python_script,
            $this->username,
            $this->password,
        ];
        $process = new Process($args);
        $process->run();
        if (!$process->isSuccessful()) {
            log("python进程失败!");
            // 打印标准错误输出
            log("STDERR: " . $process->getErrorOutput());
            log("STDOUT: " . $process->getOutput());
            throw new \Exception('可能服务器驱动异常,请重试');
        }
        $output = $process->getOutput();
        $lines = explode("\n", $output);
        $firstLine = $lines[0] ?? "";

        // 脚本登录成功会输出True
        if (
            trim($firstLine) === 'True'
        ) {
            return $this->cookies;
        } else {
            throw new \Exception('登录失败，请检查用户名和密码');
        }
    }
}