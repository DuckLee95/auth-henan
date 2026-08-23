# 贡献学校认证

每所学校只需要完成两步：

1. 在 `src/Schools` 复制一个现有认证类，实现 `SchoolAuth::login()`。
2. 在 `src/SchoolRegistry.php` 的 `SCHOOLS` 中注册学校名称、邮箱后缀和认证类。

`login()` 成功时返回 `true`，账号或密码错误返回 `false`；只有网络故障或学校页面结构变化时才抛出异常。

请勿在代码、Issue、日志和测试文件中提交真实账号、密码、Cookie、验证码或 ticket。
