<?php
namespace Blessing\HAuth\Services;
use Blessing\HAuth\Services\BaseAuth;

use Blessing\HAuth\Consts\Schools;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\Http;

use Symfony\Component\Process\Process;
use Blessing\HAuth\Utils\ZutAESUtil;

class AuthZut extends BaseAuth
{
    public function login(): array
    {
        $cookieJar=$this->auth_login();

        if(!$cookieJar->getCookieByName("asessionid")){
            throw new \Exception("认证失败,用户名或密码错误");
        }

        return $cookieJar->toArray();
    }
    private function auth_login(){
        function log(string $msg)
        {
            echo '<div style="backgroud-color: #3fe7a1ff">' . $msg . '</div>';
        }
        $root_url = 'https://authserver.zut.edu.cn/authserver/';

        $login_url = Schools::LOGIN_URL['zut']; // 'https://cas.../cas/login'
        $domain = 'authserver.zut.edu.cn';
        $cookieJar = new CookieJar();
        $browserHeaders = Schools::BROWSER_HEADERS;
        // 001
        $response = Http::withHeaders($browserHeaders)
            ->withOptions(['cookies' => $cookieJar])
            ->get($login_url);
        // 002
        $response = Http::withHeaders($browserHeaders)
            ->withHeaders(['Refer' => 'https://authserver.zut.edu.cn/authserver/login?service=https%3A%2F%2Fi.zut.edu.cn%3A443%2Flogin%3Fservice%3Dhttps%3A%2F%2Fi.zut.edu.cn%2Fnew%2Findex.html'])
            ->withOptions(['cookies' => $cookieJar])
            ->get($login_url);
        // username
        $username=$this->form_request->input('identification');
        // lt
        preg_match('/name="lt" value="([^"]*)"/', $response->body(), $ltMatch);
        // execution
        preg_match('/name="execution" value="([^"]*)"/', $response->body(), $executionMatch);
        // pwdDefaultEncryptSalt
        preg_match('/id="pwdDefaultEncryptSalt" value="([^"]*)"/', $response->body(), $pwdDefaultEncryptSaltMatch);
        // sign_code
        $params = [
            'username' => $this->form_request->input('identification'),
            'pwdEncrypt2' => 'pwdEncryptSalt',
            '_' => (int) (microtime(true) * 1000),
        ];
        $response = Http::withHeaders($browserHeaders)
            ->withOptions(['cookies' => $cookieJar])
            ->get($root_url . 'needCaptcha.html', $params);
        // createSliderCaptcha()
        $params = ['_' => (int) (microtime(true) * 1000)];
        $response = Http::withHeaders($browserHeaders)
            ->withOptions(['cookies' => $cookieJar])
            ->get($root_url . 'sliderCaptcha.do', $params);

        function echoImageBase64($base64String)
        {
            echo '<img src="data:image/png;base64,' . $base64String . '" />';
        }
        function getImageData($image)
        {
            // 输出图片为PNG格式  
            ob_start(); // 开启输出控制  
            imagepng($image); // 输出PNG图片  
            $imageData = ob_get_contents(); // 获取输出的图片数据  
            ob_end_clean(); // 清除输出缓冲区
            return $imageData;
        }
        function printImage($image)
        {
            $imageData = getImageData($image);
            echoImageBase64(base64_encode($imageData));
        }
        $imgJsonString = $response->body();
        // 外部调用opencv-python
        $python_script = 'resources/py/matchTemplate.py';
        $python_script = dirname(__DIR__) . DIRECTORY_SEPARATOR . $python_script;
        $python_executable = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . "assets/py_venv/bin/python3";
        $args = [
            $python_executable,
            $python_script,
            $imgJsonString,
        ];
        $process = new Process($args);
        $process->run();
        if (!$process->isSuccessful()) {
            log("python进程失败!");
            // 打印标准错误输出
            log("STDERR: " . $process->getErrorOutput());
            log("STDOUT: " . $process->getOutput());
        }
        $output = $process->getOutput();

        $verify_info = json_decode($output, true);
        $verify_info['moveLength'] += 2;
        $verify_url = "https://authserver.zut.edu.cn/authserver/verifySliderImageCode.do";
        $response = Http::withHeaders($browserHeaders)
            ->withOptions(['cookies' => $cookieJar])
            ->get($verify_url, $verify_info);
        $sign_code = json_decode($response->body(), true)['sign'];
        // encrypt password
        $password=$this->form_request->input('password');
        $passwordEncrypt=ZutAESUtil::encryptWithTry($password,$pwdDefaultEncryptSaltMatch[1]);

        // username: from_request
        // password: encoded_password(from_request)
        // lt: LT-1807901-wzk4BwdcycdLZBdkRRWTXDWJgm5tew1762348570137-F41T-cas
        // dllt: userNamePasswordLogin (fixed)
        // execution: e2s1 (?)
        // _eventId: submit (fixed)
        // rmShown: 1 (?)
        // sign: 49efbe6bba4a11f0afea276b862311c0 (captcha fetch)

        $postData = [
            "username"=>$username,
            "password"=>$passwordEncrypt,
            "lt"=>$ltMatch[1],
            "dllt" => "userNamePasswordLogin",
            "execution" => $executionMatch[1],
            "_eventId" => "submit",
            "rmShown" => 1,
            "sign" => $sign_code,
        ];

        $response=Http::withHeaders($browserHeaders)
        ->withOptions(['cookies'=>$cookieJar])
        ->asForm()
        ->post($login_url,$postData);
        
        return $cookieJar;
    }
    /**
     * 弃用的图像处理,将使用外部调用opencv-pyhton
     * @return void
     */
    function _unuse_images_tool()
    {
        // 灰度化函数
        function imagetogray($image)
        {
            $width = imagesx($image);
            $height = imagesy($image);
            $gray_image = imagecreatetruecolor($width, $height);
            for ($y = 0; $y < $height; $y++) {
                for ($x = 0; $x < $width; $x++) {
                    $rgb = imagecolorat($image, $x, $y);
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;
                    $gray = (int) (0.299 * $r + 0.587 * $g + 0.114 * $b);
                    imagesetpixel($gray_image, $x, $y, imagecolorallocate($gray_image, $gray, $gray, $gray));
                }
            }
            return $gray_image;
        }
        // 边缘检测(Sobel算子)
        function imagetoedge($image)
        {
            $width = imagesx($image);
            $height = imagesy($image);
            $edge_image = imagecreatetruecolor($width, $height);

            // 高斯模糊预处理
            imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR);

            // Sobel算子
            $sobel_x = [[-1, 0, 1], [-2, 0, 2], [-1, 0, 1]];
            $sobel_y = [[-1, -2, -1], [0, 0, 0], [1, 2, 1]];
            // 两个3x3卷积核检测水平和垂直边缘
            for ($y = 1; $y < $height - 1; $y++) {
                for ($x = 1; $x < $width - 1; $x++) {
                    $pixel_x = $pixel_y = 0;

                    // 卷积计算
                    for ($i = -1; $i <= 1; $i++) {
                        for ($j = -1; $j <= 1; $j++) {
                            $gray = imagecolorat($image, $x + $j, $y + $i) & 0xff;
                            $pixel_x += $gray * $sobel_x[$i + 1][$j + 1];
                            $pixel_y += $gray * $sobel_y[$i + 1][$j + 1];
                        }
                    }

                    $magnitude = (int) sqrt($pixel_x * $pixel_x + $pixel_y * $pixel_y);
                    $magnitude = min(255, max(0, $magnitude)); // 限制在0-255范围内

                    // 阈值处理,二值化
                    $threshold = 250;
                    $magnitude = ($magnitude >= $threshold) ? 255 : 0;

                    imagesetpixel($edge_image, $x, $y, imagecolorallocate($edge_image, $magnitude, $magnitude, $magnitude));
                }
            }
            return $edge_image;
        }
    }
}