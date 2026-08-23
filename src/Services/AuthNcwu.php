<?php

namespace Blessing\HAuth\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

use Blessing\HAuth\Utils\RSAUtils;
use Blessing\HAuth\Utils\CookieUtil;
use Blessing\HAuth\Services\BaseAuth;
use Symfony\Component\Process\Process;
/**
 * 统一身份认证服务类
 */
class AuthNcwu extends BaseAuth
{
    public function __construct(string $username, string $password)
    {
        $this->username = $username;
        $this->password = $password;
        $this->cookies=[];
    }

    /**
     * 执行登录
     */
    public function login(): array
    {
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
        // throw new \Exception('args'.json_encode($args).'python'.$process->getOutput().'error:'.$process->getErrorOutput());
        if (!$process->isSuccessful()) {
            throw new \Exception('python执行失败'.$process->getErrorOutput());
        }
        $output = $process->getOutput();
        // throw new \Exception($output);
        $this->cookies=[
            'auth_type'=>'nodriver_managed'
            ];
        // 脚本登录成功会输出True
        if (
            trim($output) === 'True'
        ) {
            return $this->cookies;
        } else {
            throw new \Exception('登录失败，请检查用户名和密码');
        }
    }
}