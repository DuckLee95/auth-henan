# 贡献指南

各校社长确定本校负责人后，按以下流程提交代码：

1. Fork `chank616/auth-henan`。
2. 在自己的 Fork 中创建分支并完成修改。
3. 将分支推送到自己的 Fork。
4. 提交 Pull Request，目标选择 `chank616/auth-henan` 的 `main` 分支。

推荐使用 AI 辅助开发。开始前请让 AI 阅读仓库根目录的 `AGENTS.md` 或 `CLAUDE.md`，Blessing Skin 相关实现请参考[官方插件开发文档](https://bs-plugin.netlify.app/)。

## Provider 要求

- 学校认证类放在 `src/Schools`，并实现 `SchoolAuth`。
- `login()` 认证成功返回 `true`，账号或密码错误返回 `false`，无法判断时抛出异常。
- 新增学校时，在 `src/SchoolRegistry.php` 注册学校标识、名称、邮箱后缀和认证类。
- 只修改自己负责学校的代码；需要调整公共逻辑时，在 Pull Request 中说明。

Pull Request 写明学校名称、认证入口和主要改动即可。不要提交真实账号、密码、Cookie 或验证码。

提交信息使用 Conventional Commits 类型和中文说明，例如：`feat: 新增河南大学统一认证`、`fix(ncwu): 修复华水登录响应解析`。完整规则见 `AGENTS.md`。
