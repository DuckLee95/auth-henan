<?php

namespace Blessing\HAuth\Utils;

/**
 * 兼容金智教务 jkingo.des.js 的 DES 加密实现。
 */
final class KingoDes
{
    private const STANDARD_PC1 = [
        56, 48, 40, 32, 24, 16, 8, 0,
        57, 49, 41, 33, 25, 17, 9, 1,
        58, 50, 42, 34, 26, 18, 10, 2,
        59, 51, 43, 35, 62, 54, 46, 38,
        30, 22, 14, 6, 61, 53, 45, 37,
        29, 21, 13, 5, 60, 52, 44, 36,
        28, 20, 12, 4, 27, 19, 11, 3,
    ];

    public static function encrypt(string $data, string $key): string
    {
        $keyUnits = self::toUtf16CodeUnits($key);
        if (count($keyUnits) === 0) {
            throw new \RuntimeException('华水教务系统未返回临时加密密钥');
        }

        $dataUnits = self::toUtf16CodeUnits($data);
        $encrypted = '';

        foreach (array_chunk($dataUnits, 4) as $dataChunk) {
            $block = self::unitsToBlock($dataChunk);
            foreach (array_chunk($keyUnits, 4) as $keyChunk) {
                $block = self::encryptBlock($block, self::transformKey($keyChunk));
            }
            $encrypted .= strtoupper(bin2hex($block));
        }

        return $encrypted;
    }

    private static function encryptBlock(string $block, string $key): string
    {
        $cipher = self::cipher();
        $encrypted = openssl_encrypt(
            $block,
            $cipher,
            str_repeat($key, 3),
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING
        );

        if ($encrypted === false) {
            throw new \RuntimeException('当前 PHP OpenSSL 无法执行华水登录参数加密');
        }

        return $encrypted;
    }

    private static function cipher(): string
    {
        static $cipher;
        if ($cipher !== null) {
            return $cipher;
        }

        $available = array_map('strtolower', openssl_get_cipher_methods());
        foreach (['des-ede3', 'des-ede3-ecb'] as $candidate) {
            if (in_array($candidate, $available, true)) {
                return $cipher = $candidate;
            }
        }

        throw new \RuntimeException('当前 PHP OpenSSL 不支持华水登录所需的 3DES 算法');
    }

    private static function transformKey(array $units): string
    {
        $source = self::bytesToBits(self::unitsToBlock($units));
        $destination = array_fill(0, 64, 0);

        for ($position = 0; $position < 56; ++$position) {
            $group = intdiv($position, 8);
            $offset = $position % 8;
            $customPosition = 8 * (7 - $offset) + $group;
            $destination[self::STANDARD_PC1[$position]] = $source[$customPosition];
        }

        return self::bitsToBytes($destination);
    }

    private static function unitsToBlock(array $units): string
    {
        $block = '';
        for ($index = 0; $index < 4; ++$index) {
            $unit = $units[$index] ?? 0;
            $block .= chr(($unit >> 8) & 0xff) . chr($unit & 0xff);
        }

        return $block;
    }

    private static function bytesToBits(string $bytes): array
    {
        $bits = [];
        foreach (unpack('C*', $bytes) as $byte) {
            for ($shift = 7; $shift >= 0; --$shift) {
                $bits[] = ($byte >> $shift) & 1;
            }
        }

        return $bits;
    }

    private static function bitsToBytes(array $bits): string
    {
        $bytes = '';
        for ($offset = 0; $offset < 64; $offset += 8) {
            $byte = 0;
            for ($bit = 0; $bit < 8; ++$bit) {
                $byte |= $bits[$offset + $bit] << (7 - $bit);
            }
            $bytes .= chr($byte);
        }

        return $bytes;
    }

    private static function toUtf16CodeUnits(string $value): array
    {
        if (!preg_match('/[^\x00-\x7f]/', $value)) {
            return array_values(unpack('C*', $value));
        }

        if (!function_exists('iconv')) {
            throw new \RuntimeException('当前 PHP 缺少处理华水登录参数所需的 iconv 扩展');
        }

        $utf16 = @iconv('UTF-8', 'UTF-16BE', $value);
        if ($utf16 === false) {
            throw new \RuntimeException('华水登录参数不是有效的 UTF-8 字符串');
        }

        return array_values(unpack('n*', $utf16));
    }
}
