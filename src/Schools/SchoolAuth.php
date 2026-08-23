<?php

namespace Blessing\HAuth\Schools;

interface SchoolAuth
{
    /**
     * 验证学校账号。成功返回 true，账号或密码错误返回 false。
     */
    public function login(string $username, string $password): bool;
}
