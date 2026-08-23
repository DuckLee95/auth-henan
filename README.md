# 河南联合皮肤站各校统一登录
目前支持：华北水利水电大学、郑州大学。

## 使用

1. 将本项目克隆到皮肤站的 `plugins/auth-henan` 目录。
2. 确认 PHP 已启用 `bcmath`、`openssl` 扩展。
3. 在皮肤站插件管理中启用 `auth-henan`。

插件认证流程已全部使用 PHP，不需要 Python、Node.js 或额外的常驻服务。

## 添加学校

每所学校的认证代码独立放在 `src/Schools`。复制一个现有类、实现
`SchoolAuth::login()`，再到 `src/SchoolRegistry.php` 注册即可，详见
`CONTRIBUTING.md`。

## 贡献者

感谢所有参与学校认证适配和插件维护的同学，完整名单见
[CONTRIBUTORS.md](CONTRIBUTORS.md)。

## License

MIT License
