<?php

namespace Blessing\HAuth\Utils;

use Exception;

/**
 * 帮助类，用于复现 JavaScript 中的 CryptoJS AES 加密逻辑。
 * * 严格按照 JS 逻辑编写，不引入新的依赖库，仅依赖 PHP 的 openssl 扩展。
 * * JS 逻辑核心:
 * 1. `_rds(16)` 生成一个 16 字节的 IV。
 * 2. `_rds(64)` 生成一个 64 字节的随机前缀。
 * 3. 待加密数据 = 64字节前缀 + 原始数据。
 * 4. 使用 AES-CBC (Pkcs7) 模式加密。
 * 5. AES 算法 (128/192/256) 取决于传入密钥的长度 (16/24/32 字节)。
 * 6. 结果输出为 Base64 编码的字符串。
 */
class ZutAESUtil
{
    /**
     * JS 中的 `$_chars` (用于 _rds 函数)
     * 'ABCDEFGHJKMNPQRSTWXYZabcdefhijkmnprstwxyz2345678'
     */
    private const CHARS = 'ABCDEFGHJKMNPQRSTWXYZabcdefhijkmnprstwxyz2345678';

    /**
     * (私有) 复现 JS 中的 `_rds(len)` 函数.
     *
     * @param int $length 需要生成的随机字符串长度
     * @return string 
     * @throws Exception 如果 random_int 失败
     */
    private static function rds(int $length): string
    {
        $retStr = '';
        $charsLen = strlen(self::CHARS);
        
        for ($i = 0; $i < $length; $i++) {
            // 使用 PHP 内置的安全随机数生成器
            $retStr .= self::CHARS[random_int(0, $charsLen - 1)];
        }
        return $retStr;
    }

    /**
     * (私有) 复现 JS 中的 `_gas(data, key0, iv0)` 核心加密函数.
     *
     * @param string $data 待加密的原始数据 (已包含64位前缀)
     * @param string $key  UTF-8 密钥
     * @param string $iv   UTF-8 IV (16字节)
     * @return string Base64 编码的加密字符串
     * @throws Exception 如果密钥长度无效或 openssl 加密失败
     */
    private static function gas(string $data, string $key, string $iv): string
    {
        // 使用 `strlen` 获取字节长度 (而不是 `mb_strlen` 获取字符长度)
        $keyLength = strlen($key);
        $method = '';

        // JS 库根据密钥的 sigBytes (字节数) 自动选择 AES 轮数。
        // 16 字节 (d=4) -> 10 轮 -> AES-128
        // 24 字节 (d=6) -> 12 轮 -> AES-192
        // 32 字节 (d=8) -> 14 轮 -> AES-256
        if ($keyLength === 16) {
            $method = 'AES-128-CBC';
        } elseif ($keyLength === 24) {
            $method = 'AES-192-CBC';
        } elseif ($keyLength === 32) {
            $method = 'AES-256-CBC';
        } else {
            // 抛出异常，这与 JS 逻辑中密钥长度不匹配导致的行为一致
            throw new Exception('Invalid key length. Must be 16, 24, or 32 bytes.');
        }

        /**
         * 使用 openssl_encrypt
         * * @param string $data     数据
         * @param string $method   加密方法 (AES-128-CBC 等)
         * @param string $key      密钥 (原始字符串，openssl 会自动处理)
         * @param int    $options  0 = 默认 (PKCS7 padding, Base64 输出)
         * @param string $iv       IV (原始字符串)
         */
        $encrypted = openssl_encrypt(
            $data,
            $method,
            $key,
            0, // 0 选项表示默认行为: PKCS7 填充 并且 输出 Base64
            $iv
        );
        
        if ($encrypted === false) {
             throw new Exception('openssl_encrypt failed.');
        }

        return $encrypted;
    }

    /**
     * (公共) 复现 JS 中的 `encryptAES(data, _p1)` 函数.
     *
     * @param string $data 原始数据 (不带前缀)
     * @param string $key  加密密钥
     * @return string Base64 编码的加密字符串, 或在密钥为空时返回原数据
     * @throws Exception
     */
    public static function encrypt(string $data, string $key): string
    {
        // 对应 `if (!_p1) { return data; }`
        if (empty($key)) {
            return $data;
        }
        
        // 1. 生成 16 字节的随机 IV (对应 _rds(16))
        $iv = self::rds(16);
        
        // 2. 生成 64 字节的随机前缀 (对应 _rds(64))
        $prefix = self::rds(64);
        
        // 3. 组合数据 (前缀 + 原始数据)
        $dataToEncrypt = $prefix . $data;
        
        // 4. 调用核心加密函数 (对应 _gas(...))
        return self::gas($dataToEncrypt, $key, $iv);
    }
    
    /**
     * (公共) 复现 JS 中的 `_ep(p0, p1)` 顶层函数.
     * * 这是一个带 try-catch 的封装，在加密失败时返回原始数据，
     * 模仿 JS 中的 `catch (e) {}` 行为。
     *
     * @param string $data 原始数据
     * @param string $key  加密密钥
     * @return string 加密后的 Base64 字符串, 或在失败/密钥为空时返回原始数据
     */
    public static function encryptWithTry(string $data, string $key): string
    {
        try {
            // 调用主加密逻辑
            return self::encrypt($data, $key);
        } catch (Exception $e) {
            // 对应 JS 中的 `catch (e) {} return p0;`
            // 在生产环境中，你可能希望在这里记录错误
            // Log::error('AES encryption failed: ' . $e->getMessage());
            // dd($e->getMessage());
            return $data;
        }
    }
}