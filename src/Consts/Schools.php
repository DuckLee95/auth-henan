<?php
namespace Blessing\HAuth\Consts;

class Schools
{
    const NAME = [
        'ncwu' => '华北水利水电大学',
        'haust' => '河南科技大学',
        'zzu' => '郑州大学',
        'zut' => '中原工学院',
        'lit' => '洛阳理工学院',
        'htu' => '河南师范大学',
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
        'lit' => 'http://ids.lit.edu.cn/authserver/login',
        'zut' => 'https://authserver.zut.edu.cn/authserver/login?service=https%3A%2F%2Fi.zut.edu.cn%3A443%2Flogin%3Fservice%3Dhttps%3A%2F%2Fi.zut.edu.cn%2Fnew%2Findex.html',
        'htu' => 'https://jwc.htu.edu.cn/',
    ];

    const BROWSER_HEADERS = [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                'Accept-Language' => 'zh-CN,zh;q=0.9,en;q=0.8,en-GB;q=0.7,en-US;q=0.6',
                'Upgrade-Insecure-Requests' => '1',
                'Sec-Fetch-Dest' => 'document', // ⬅️ 额外添加更多浏览器头
                'Sec-Fetch-Mode' => 'navigate',
                'Sec-Fetch-User' => '?1',
            ];
}
