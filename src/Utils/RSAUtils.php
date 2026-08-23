<?php


namespace Blessing\HAuth\Utils;
/**
 * RSAKeyPair 类，用于存储 RSA 密钥对和相关参数。
 * 对应 JS 中的 RSAKeyPair 构造函数。
 */
class RSAKeyPair {
    /** @var string 大数：加密指数 (e)，十进制字符串 */
    public $e;
    /** @var string 大数：解密指数 (d)，十进制字符串 */
    public $d;
    /** @var string 大数：模数 (m)，十进制字符串 */
    public $m;
    /** @var int 每个加密块的字节大小 */
    public $chunkSize;
    /** @var int 进制（始终为 16） */
    public $radix;
    /** @var int 加密块输出的十六进制长度 */
    public $blockHexLength;

    /**
     * 构造函数
     * @param string $encryptionExponent 加密指数 (e) 的十六进制字符串
     * @param string $decryptionExponent 解密指数 (d) 的十六进制字符串
     * @param string $modulus 模数 (m) 的十六进制字符串
     */
    public function __construct($encryptionExponent, $decryptionExponent, $modulus) {
        // 使用 BCMath，我们将所有大数存储为十进制字符串
        $this->e = RSAUtils::biFromHex($encryptionExponent);
        $this->d = RSAUtils::biFromHex($decryptionExponent);
        $this->m = RSAUtils::biFromHex($modulus);
        $this->radix = 16;

        // 计算 chunkSize，逻辑与 JS 版本保持一致
        // JS 版的 biHighIndex 是基于 16 位（4 个十六进制字符）的“数字”
        // (ceil(strlen($modulus) / 4) - 1) 对应 biHighIndex(this.m)
        // 每个 "digit" 对应 2 个字节，所以 chunkSize = 2 * (num_digits - 1)
        // JS 版 biFromHex 会忽略最高位的 0；公钥通常以 "00" 开头用于表示正数。
        $significantModulus = ltrim($modulus, '0');
        $num_digits = (int)ceil(strlen($significantModulus ?: '0') / 4);
        $this->chunkSize = 2 * ($num_digits - 1);
        $this->blockHexLength = $num_digits * 4;
    }
}

/**
 * RSAUtils 工具类
 * 使用 PHP 的 BCMath 扩展实现了 JS 代码中的核心功能。
 * 命名和高级逻辑严格参考 JS 代码。
 */
class RSAUtils {

    /**
     * 创建一个 RSAKeyPair 对象
     * @param string $encryptionExponent
     * @param string $decryptionExponent
     * @param string $modulus
     * @return RSAKeyPair
     */
    public static function getKeyPair($encryptionExponent, $decryptionExponent, $modulus) {
        return new RSAKeyPair($encryptionExponent, $decryptionExponent, $modulus);
    }

    /**
     * 加密字符串 (s)
     * @param RSAKeyPair $key 密钥对象
     * @param string $s 要加密的明文字符串
     * @return string 加密后的十六进制字符串，以空格分隔
     */
    public static function encryptedString($key, $s) {
        $a = []; // 存储字符的 ASCII 码
        $sl = strlen($s);
        $i = 0;
        while ($i < $sl) {
            $a[$i] = ord($s[$i]);
            $i++;
        }

        // 填充数组，使其长度成为 chunkSize 的倍数
        // JS: while (a.length % key.chunkSize != 0) { a[i++] = 0; }
        while (count($a) % $key->chunkSize != 0) {
            $a[$i++] = 0;
        }

        $al = count($a);
        $result = "";
        
        // 循环处理每个数据块
        for ($i = 0; $i < $al; $i += $key->chunkSize) {
            $block = '0'; // $block 是一个 BCMath 字符串
            $j = 0;
            
            // 将 chunkSize 个字节（每次 2 字节）打包成一个大数
            // JS: block.digits[j] = a[k++]; block.digits[j] += a[k++] << 8;
            for ($k = $i; $k < $i + $key->chunkSize; ++$j) {
                // 获取低位字节
                $byte1 = $a[$k++];
                // 获取高位字节
                $byte2 = $a[$k++];
                
                // 合并为 16 位值 (JS: a[k] + (a[k+1] << 8))
                $digit_val = $byte1 + ($byte2 << 8);

                // $block += $digit_val * (65536 ^ $j)
                // 65536 (2^16) 是 JS 代码中的 biRadix
                $power_of_radix = bcpow('65536', (string)$j);
                $term = bcmul((string)$digit_val, $power_of_radix);
                $block = bcadd($block, $term);
            }

            // 执行 RSA 核心操作：crypt = block^e mod m
            // JS: var crypt = key.barrett.powMod(block, key.e);
            // BCMath 的 bcpowmod 完美替代了 JS 中的 BarrettMu_powMod
            $crypt = bcpowmod($block, $key->e, $key->m);

            // 将大数转换为十六进制
            // JS: var text = key.radix == 16 ? RSAUtils.biToHex(crypt) : ...
            $text = str_pad(self::biToHex($crypt), $key->blockHexLength, '0', STR_PAD_LEFT);
            
            $result .= $text . " ";
        }
        
        // 移除最后一个空格
        return substr($result, 0, -1);
    }

    /**
     * 解密字符串 (s)
     * @param RSAKeyPair $key 密钥对象
     * @param string $s 加密的十六进制字符串，以空格分隔
     * @return string 解密后的明文字符串
     */
    public static function decryptedString($key, $s) {
        $blocks = explode(" ", $s);
        $result = "";

        // 循环解密每个数据块
        for ($i = 0; $i < count($blocks); ++$i) {
            // JS: bi = RSAUtils.biFromHex(blocks[i]);
            // 从十六进制转换为 BCMath 的十进制大数
            $bi = self::biFromHex($blocks[$i]);

            // 执行 RSA 核心操作：block = bi^d mod m
            // JS: block = key.barrett.powMod(bi, key.d);
            $block_dec = bcpowmod($bi, $key->d, $key->m);

            // 将解密后的大数（十进制）拆包成字节
            // JS: for (j = 0; j <= RSAUtils.biHighIndex(block); ++j) { ... }
            $j = 0;
            // 循环，直到大数变为 0
            while (bccomp($block_dec, '0') > 0) {
                // 提取最低的 16 位 (mod 65536)
                $digit_val_str = bcmod($block_dec, '65536');
                $digit_val = (int)$digit_val_str;

                // 从大数中移除这 16 位 (div 65536)
                $block_dec = bcdiv($block_dec, '65536', 0); // 0 表示整数除法

                // 拆分 16 位值为两个 8 位字节
                // JS: result += String.fromCharCode(block.digits[j] & 255, block.digits[j] >> 8);
                $low_byte = $digit_val & 255;
                $high_byte = $digit_val >> 8;

                $result .= chr($low_byte);
                // 只有当高位字节不为0时才添加，防止添加多余的 null 字节
                // （尽管 JS 总是添加，但最后会修剪）
                if ($high_byte != 0) {
                     $result .= chr($high_byte);
                }
                $j++;
            }
        }

        // 移除尾部的 null 字符（由填充产生）
        // JS: if (result.charCodeAt(result.length - 1) == 0) { ... }
        if (ord(substr($result, -1)) == 0) {
            $result = substr($result, 0, -1);
        }

        return $result;
    }

    // --- BCMath 辅助函数 (对应 JS 中的 bi... 函数) ---

    /**
     * (private) 将十六进制字符串转换为 BCMath 十进制字符串
     * @param string $hex 十六进制字符串
     * @return string 十进制字符串
     */
    private static function hex2dec($hex) {
        $dec = '0';
        $len = strlen($hex);
        for ($i = 0; $i < $len; $i++) {
            // hexdec() 只处理一个字符是安全的
            $digit = (string)hexdec($hex[$i]); 
            // $dec = $dec * 16 + $digit
            $dec = bcadd(bcmul($dec, '16'), $digit);
        }
        return $dec;
    }

    /**
     * (private) 将 BCMath 十进制字符串转换为十六进制字符串
     * @param string $dec 十进制字符串
     * @return string 十六进制字符串
     */
    private static function dec2hex($dec) {
        $hex = '';
        // 循环，直到 $dec 为 0
        while (bccomp($dec, '0') > 0) {
            // $last = $dec % 16
            $last = bcmod($dec, '16');
            // dechex() 处理小数字是安全的
            $hex = dechex((int)$last) . $hex;
            // $dec = $dec / 16 (整数除法)
            $dec = bcdiv($dec, '16', 0);
        }
        return $hex ?: '0';
    }

    /**
     * 对应 JS: biFromHex
     * @param string $s 十六进制字符串
     * @return string BCMath 十进制字符串
     */
    public static function biFromHex($s) {
        return self::hex2dec($s);
    }

    /**
     * 对应 JS: biToHex
     * @param string $x BCMath 十进制字符串
     * @return string 十六进制字符串
     */
    public static function biToHex($x) {
        return self::dec2hex($x);
    }
}
