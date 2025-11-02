<?php
namespace Blessing\HAuth\Consts;

class Schools
{
    const NAME = [
        'ncwu' => '华北水利水电大学',
        'haust' => '河南科技大学',
        'zzu' => '郑州大学',
        'lit' => '洛阳理工学院(施工中)',
        'zut' => '中原工学院(施工中)',
        'htu' => '河南师范大学(施工中)',
    ];
    const EMAIL_DOMAIN = [
        'ncwu' => '@stu.ncwu.edu.cn',
        'haust' => '@stu.haust.edu.cn',
        'zzu' => '@stu.zzu.edu.cn',
        'lit' => '@lit.edu.cn',
        'zut' => '@zut.edu.cn',
        'htu' => '@stu.htu.edu.cn',
    ];
    const LOGIN_URL = [
        'ncwu' => 'https://authserver.ncwu.edu.cn/authserver/login',
        'haust' => 'https://cas-haust-edu-cn-s.haust.edu.cn/cas/login',
        'zzu' => 'https://cas.s.zzu.edu.cn/cas/a/login',
        'lit' => '洛阳理工学院',
        'zut' => '中原工学院',
        'htu' => '河南师范大学',
    ];
}