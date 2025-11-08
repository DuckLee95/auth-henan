<?php

/**
 * JSEncrypt PHP 版本
 *
 * 这是一个模拟 jsencrypt.js 库公共 API 的 PHP 实现。
 * 它使用 PHP 内置的 OpenSSL 扩展来处理 RSA 加密和解密，
 * 从而代替 JS 中的手动 BigInteger 和 ASN.1 解析。
 *
 * 这使得代码保持简洁、安全和高效。
 */
namespace Blessing\HAuth\Utils;
class JSEncrypt {

    /** @var resource|OpenSSLAsymmetricKey|false 公钥资源 */
    private $publicKey = false;

    /** @var resource|OpenSSLAsymmetricKey|false 私钥资源 */
    private $privateKey = false;

    /**
     * 设置公钥
     *
     * @param string $key PEM 格式的公钥字符串 (例如: -----BEGIN PUBLIC KEY-----...)
     * @return bool 是否设置成功
     */
    public function setPublicKey($key) {
        $this->publicKey = openssl_pkey_get_public($key);
        return $this->publicKey !== false;
    }

    /**
     * 设置私钥
     *
     * @param string $key PEM 格式的私钥字符串 (例如: -----BEGIN RSA PRIVATE KEY-----...)
     * @return bool 是否设置成功
     */
    public function setPrivateKey($key) {
        // 假设私钥没有密码保护，这与 jsencrypt 的常见用法一致
        $this->privateKey = openssl_pkey_get_private($key);
        return $this->privateKey !== false;
    }

    /**
     * 公钥加密
     *
     * @param string $data 要加密的明文字符串
     * @return string|false Base64 编码的密文，如果失败则返回 false
     */
    public function encrypt($data) {
        if (!$this->publicKey) {
            trigger_error("JSEncrypt: Public key not set.", E_USER_WARNING);
            return false;
        }

        $encrypted = '';
        // JSEncrypt 默认使用 PKCS#1 v1.5 padding
        $success = openssl_public_encrypt(
            $data,
            $encrypted,
            $this->publicKey,
            OPENSSL_PKCS1_PADDING // 对应 JS 库的默认 Padding
        );

        return $success ? base64_encode($encrypted) : false;
    }

    /**
     * 私钥解密
     *
     * @param string $data Base64 编码的密文
     * @return string|false 解密后的明文字符串，如果失败则返回 false
     */
    public function decrypt($data) {
        if (!$this->privateKey) {
            trigger_error("JSEncrypt: Private key not set.", E_USER_WARNING);
            return false;
        }

        $decrypted = '';
        $dataToDecrypt = base64_decode($data);
        if ($dataToDecrypt === false) {
            trigger_error("JSEncrypt: Failed to base64 decode data.", E_USER_WARNING);
            return false;
        }

        // JSEncrypt 默认使用 PKCS#1 v1.5 padding
        $success = openssl_private_decrypt(
            $dataToDecrypt,
            $decrypted,
            $this->privateKey,
            OPENSSL_PKCS1_PADDING
        );

        return $success ? $decrypted : false;
    }
}

/*
 * --- 使用示例 ---
 *
 * // 0. 生成一个密钥对 (用于测试)
 * $config = [
 * "digest_alg" => "sha512",
 * "private_key_bits" => 1024, // jsencrypt 通常使用 1024 或 2048
 * "private_key_type" => OPENSSL_KEYTYPE_RSA,
 * ];
 * $res = openssl_pkey_new($config);
 * if (!$res) {
 * die("无法生成密钥对");
 * }
 *
 * // 提取私钥
 * openssl_pkey_export($res, $privateKeyPem);
 *
 * // 提取公钥
 * $publicKeyDetails = openssl_pkey_get_details($res);
 * $publicKeyPem = $publicKeyDetails["key"];
 *
 * echo "公钥:\n" . $publicKeyPem . "\n";
 * echo "私钥:\n" . $privateKeyPem . "\n";
 *
 * // 1. 实例化 JSEncrypt
 * $encrypt = new JSEncrypt();
 *
 * // 2. 设置密钥
 * $encrypt->setPublicKey($publicKeyPem);
 * $encrypt->setPrivateKey($privateKeyPem);
 *
 * // 3. 加密
 * $plaintext = "这是我的秘密信息";
 * $encryptedData = $encrypt->encrypt($plaintext);
 *
 * if ($encryptedData === false) {
 * echo "加密失败: " . openssl_error_string() . "\n";
 * } else {
 * echo "原文: " . $plaintext . "\n";
 * echo "加密后 (Base64): " . $encryptedData . "\n";
 * }
 *
 * // 4. 解密
 * if ($encryptedData) {
 * $decryptedData = $encrypt->decrypt($encryptedData);
 * if ($decryptedData === false) {
 * echo "解密失败: " . openssl_error_string() . "\n";
 * } else {
 * echo "解密后: " . $decryptedData . "\n";
 * }
 * }
 *
 * // 5. 验证与 JS 的互操作性
 * // 你可以将在 PHP 中生成的 $encryptedData 复制到 JS 中：
 * // var decrypt = new JSEncrypt();
 * // decrypt.setPrivateKey('... (PHP生成的 $privateKeyPem) ...');
 * // var decrypted = decrypt.decrypt('... (PHP生成的 $encryptedData) ...');
 * // console.log(decrypted); // 应输出 "这是我的秘密信息"
 */