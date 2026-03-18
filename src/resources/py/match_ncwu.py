import requests
import re
import execjs  # pyexecjs2，应该兼容
import sys

def encrypt_password(password):
    """使用页面硬编码的公钥加密密码"""
    with open("../js/security.js", "r", encoding="utf-8") as f:
        js_code = f.read()

    # 关键：polyfill 一个假的 window 对象，让 security.js 能正常执行
    polyfill = """
    var window = this || {};
    var navigator = { userAgent: "Mozilla/5.0" };  // 防止某些库检查 navigator
    var document = { createElement: function() { return {}; } };  // 防止 document 报错
    var console = { log: function() {} };  // 避免 console.log 报错
    """

    # 调用函数（和页面完全一致）
    call_js = """
    function getEncrypted(pwd) {
        RSAUtils.setMaxDigits(131);
        var exp = "010001";
        var mod = "008aed7e057fe8f14c73550b0e6467b023616ddc8fa91846d2613cdb7f7621e3cada4cd5d812d627af6b87727ade4e26d26208b7326815941492b2204c3167ab2d53df1e3a2c9153bdb7c8c2e968df97a5e7e01cc410f92c4c2c2fba529b3ee988ebc1fca99ff5119e036d732c368acf8beba01aa2fdafa45b21e4de4928d0d403";
        var rsaKey = RSAUtils.getKeyPair(exp, '', mod);
        var enc = RSAUtils.encryptedString(rsaKey, pwd);
        return enc.replace(/\\s+/g, '').toLowerCase();
    }
    """

    # 先 polyfill → 再执行 security.js → 再加调用函数
    full_js = polyfill + js_code + call_js

    # 编译
    ctx = execjs.compile(full_js)

    # print("RSAUtils 是否可用:", ctx.eval("typeof RSAUtils === 'object'"))  # 应输出 true

    # 调用加密函数
    return ctx.call("getEncrypted", password)


def login(username, password):
    url = "https://authserver.ncwu.edu.cn/authserver/login"
    s = requests.Session()
    s.headers.update({
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36",
        "Referer": url,
    })

    # 获取 execution
    r = s.get(url)
    execution_match = re.search(r'name="execution" value="(.*?)"', r.text)
    if not execution_match:
        # print("未找到 execution")
        return False
    execution = execution_match.group(1)
    # print("本次 execution:", execution)

    # 加密
    try:
        enc_pwd = encrypt_password(password)
        # print("加密 password:", enc_pwd)
        # print("长度:", len(enc_pwd))  # 应为 256
    except Exception as e:
        # print("加密失败:", str(e))
        return False

    # 提交
    data = {
        "username": username,
        "password": enc_pwd,
        "execution": execution,
        "encrypted": "true",
        "_eventId": "submit",
        "loginType": "1",
    }
    post = s.post(url, data=data, allow_redirects=False)

    # 判断
    cookies = s.cookies.get_dict()
    if post.status_code == 302 or "CASTGC" in cookies:
        # print("登录成功！CASTGC:", cookies.get("CASTGC", "未显示"))
        return True
    else:
        # print("登录失败，状态码:", post.status_code)
        # print("响应预览:", post.text[:400])
        return False


if __name__ == "__main__":
    u=sys.argv[1]
    p=sys.argv[2]
    success = login(u, p)
    print(success)