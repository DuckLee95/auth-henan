<?php

namespace Blessing\HAuth\Utils;

use Exception;

/**
 * 模拟JS CryptoJS AES ECB加密的工具类
 *
 * 对应JS逻辑:
 * 1. key = CryptoJS.enc.Utf8.parse(verifycode.repeat(4));
 * 2. srcs = CryptoJS.enc.Utf8.parse(password);
 * 3. encrypted = CryptoJS.AES.encrypt(srcs, key, {
 * mode: CryptoJS.mode.ECB,
 * padding: CryptoJS.pad.Pkcs7
 * });
 * 4. output = encrypted.ciphertext.toString(); (Hex 编码)
 */
class HtuAESUtil
{
    /**
     * 模拟 JS crypto-js 的 AES ECB 加密
     *
     * @param string $password   要加密的明文 (JS: password)
     * @param string $verifycode 用于生成密钥的字符串 (JS: verifycode)
     * @return string            加密后的十六进制(Hex)字符串
     * @throws Exception
     */
    public static function encryptPassword(string $password, string $verifycode): string
    {
        // 1. JS: var key = CryptoJS.enc.Utf8.parse(verifycode+verifycode+verifycode+verifycode);
        //    在PHP中, 这意味着我们使用原始UTF-8字符串作为密钥.
        $key = str_repeat($verifycode, 4);

        // 2. 根据密钥长度(bytes)确定AES加密方法
        //    JS CryptoJS 会自动根据密钥长度选择 AES-128/192/256
        //    PHP openssl 需要我们明确指定
        $keyLength = strlen($key);
        $method = '';

        if ($keyLength == 16) {
            // 16 bytes = 128 bits
            $method = 'aes-128-ecb';
        } elseif ($keyLength == 24) {
            // 24 bytes = 192 bits
            $method = 'aes-192-ecb';
        } elseif ($keyLength == 32) {
            // 32 bytes = 256 bits
            $method = 'aes-256-ecb';
        } else {
            // 抛出异常, 因为密钥长度不是 16, 24, 或 32 字节
            throw new Exception(
                "AES加密失败: 密钥长度无效 (" . $keyLength . " 字节)。密钥必须为 16, 24, 或 32 字节。"
            );
        }

        // 3. JS: var encrypted = CryptoJS.AES.encrypt(srcs, key, {mode:ECB, padding:Pkcs7});
        //    执行加密:
        //    - $password:      JS中的 srcs
        //    - $method:        aes-XXX-ecb (上一步确定的)
        //    - $key:           JS中的 key
        //    - OPENSSL_RAW_DATA: 告诉openssl返回原始二进制数据,
        //                      因为JS中 `ciphertext.toString()` 是
        //                      对原始Ciphertext进行Hex编码, 而不是
        //                      OpenSSL格式的Base64.
        //    - Pkcs7 padding 是 openssl_encrypt 的默认值, 完美匹配.
        //    - ECB 模式不需要 IV.
        $encrypted_raw = openssl_encrypt(
            $password,
            $method,
            $key,
            OPENSSL_RAW_DATA
        );

        if ($encrypted_raw === false) {
            throw new Exception("openssl_encrypt 加密失败。");
        }

        // 4. JS: password = encrypted.ciphertext.toString();
        //    CryptoJS WordArray.toString() 默认使用 Hex 编码.
        //    PHP中, 我们将原始二进制数据转换为Hex.
        return bin2hex($encrypted_raw);
    }
}