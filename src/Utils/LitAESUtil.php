<?php

namespace Blessing\HAuth\Utils;

use Illuminate\Support\Facades\Log;

/**
 * AES加密解密工具类
 * 对应JavaScript CryptoJS的AES/CBC/PKCS7Padding加密方式
 */
class LitAESUtil
{
    /**
     * AES加密
     * 
     * @param string $data 待加密数据
     * @param string $key 加密密钥
     * @param string $iv 初始化向量，默认'1234567890abcdef'
     * @return string|null 加密后的base64字符串，失败返回null
     */
    public static function encrypt(string $data, string $key, string $iv = '1234567890abcdef'): ?string
    {
        try {
            // 验证参数
            if (empty($data) || empty($key)) {
                throw new \InvalidArgumentException('Data and key cannot be empty');
            }

            // 确保key和iv是16字节（AES-128）
            $key = self::ensureKeyLength($key);
            $iv = self::ensureKeyLength($iv);

            // 使用AES-128-CBC模式，PKCS7填充
            $encrypted = openssl_encrypt(
                $data,
                'AES-128-CBC',
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($encrypted === false) {
                throw new \RuntimeException('Encryption failed: ' . openssl_error_string());
            }

            // 返回base64编码结果（与CryptoJS一致）
            return base64_encode($encrypted);

        } catch (\Exception $e) {
            Log::error('AES encryption error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * AES解密
     * 
     * @param string $encryptedData 加密的base64字符串
     * @param string $key 解密密钥
     * @param string $iv 初始化向量，默认'1234567890abcdef'
     * @return string|null 解密后的原始数据，失败返回null
     */
    public static function decrypt(string $encryptedData, string $key, string $iv = '1234567890abcdef'): ?string
    {
        try {
            // 验证参数
            if (empty($encryptedData) || empty($key)) {
                throw new \InvalidArgumentException('Encrypted data and key cannot be empty');
            }

            // 确保key和iv是16字节（AES-128）
            $key = self::ensureKeyLength($key);
            $iv = self::ensureKeyLength($iv);

            // 解码base64数据
            $decodedData = base64_decode($encryptedData);
            if ($decodedData === false) {
                throw new \RuntimeException('Base64 decode failed');
            }

            // 使用AES-128-CBC模式解密
            $decrypted = openssl_decrypt(
                $decodedData,
                'AES-128-CBC',
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($decrypted === false) {
                throw new \RuntimeException('Decryption failed: ' . openssl_error_string());
            }

            return $decrypted;

        } catch (\Exception $e) {
            Log::error('AES decryption error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 确保密钥长度为16字节（AES-128）
     * 如果密钥长度不足，用0填充；如果过长，截取前16字节
     * 
     * @param string $key 原始密钥
     * @return string 处理后的16字节密钥
     */
    private static function ensureKeyLength(string $key): string
    {
        $requiredLength = 16; // AES-128需要16字节密钥
        
        if (strlen($key) < $requiredLength) {
            // 长度不足，用0填充
            return str_pad($key, $requiredLength, "\0");
        } elseif (strlen($key) > $requiredLength) {
            // 长度过长，截取前16字节
            return substr($key, 0, $requiredLength);
        }
        
        return $key;
    }

    /**
     * 生成随机盐值（用于加密密钥）
     * 
     * @param int $length 盐值长度，默认16
     * @return string 随机盐值
     */
    public static function generateSalt(int $length = 16): string
    {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * 验证加密解密是否一致（测试用）
     * 
     * @param string $data 测试数据
     * @param string $key 测试密钥
     * @param string $iv 测试向量
     * @return bool 是否一致
     */
    public static function testEncryption(string $data, string $key, string $iv = '1234567890abcdef'): bool
    {
        $encrypted = self::encrypt($data, $key, $iv);
        if ($encrypted === null) {
            return false;
        }

        $decrypted = self::decrypt($encrypted, $key, $iv);
        return $decrypted === $data;
    }
}