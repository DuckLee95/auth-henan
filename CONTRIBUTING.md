# 贡献指南

## 贡献一个新学校

开始开发前，请先创建 Issue，说明学校名称、统一认证系统的大致类型以及你是否愿意长期维护。请勿在 Issue 中发送测试账号或密码。

### 1. 实现 Provider

在 `src/Schools` 中新增一个认证类并实现 `SchoolAuth`：

```php
<?php

namespace Blessing\HAuth\Schools;

class ExampleAuth implements SchoolAuth
{
    public function login(string $username, string $password): bool
    {
        // 调用学校统一认证系统并判断结果。
    }
}
```

`login()` 的返回约定：

- 验证成功：返回 `true`。
- 账号或密码错误：返回 `false`。
- 网络故障、学校页面改版或无法判断结果：抛出异常，并提供不含凭据的错误信息。

### 2. 注册学校

在 `src/SchoolRegistry.php` 的 `SCHOOLS` 中添加学校标识、显示名称、皮肤站邮箱后缀和 Provider 类。

学校标识应使用简短、稳定的小写英文缩写，例如 `ncwu`、`zzu`。

### 3. 自测与提交

Pull Request 至少应说明：

- 适配的学校及认证入口。
- 成功登录、错误密码、网络异常三种情况的测试结果。
- 是否愿意担任该学校的长期维护者。
- 学校认证流程中已知的限制，例如验证码、二次认证或频率限制。

可以提供脱敏日志或截图，但不得提交任何真实凭据和会话数据。

## 学校维护者与协作者

学校维护者负责：

- 跟进本校统一认证系统的页面、接口和加密方式变化。
- 处理与本校 Provider 有关的 Issue 和 Pull Request。
- 在发布前完成必要的真实环境验证。
- 无法继续维护时及时说明，以便招募接任者。

首次学校适配合并后，贡献者会加入 `README.md` 的贡献者名单。确认愿意持续承担上述职责后，仓库所有者会将其登记为该校维护者，并邀请为 GitHub 协作者。

协作者应使用独立分支提交 Pull Request；涉及公共认证流程、注册表、安全逻辑或其他学校的修改，需要至少一名其他维护者审核后再合并。

## 其他贡献

修复问题、改进文档和补充测试同样欢迎。提交贡献时，可以在同一个 Pull Request 中更新 `README.md` 的贡献者名单，写明自己的主要贡献。

## 安全要求

- 学校密码只能用于当次认证，不得写入数据库、缓存或日志。
- 不得提交真实账号、密码、Cookie、验证码、ticket 或完整认证响应。
- 不得把学校返回的 Cookie、ticket 或完整响应正文发送给浏览器。
- 发现认证绕过或凭据泄漏问题时，请按 [SECURITY.md](SECURITY.md) 私下报告，不要创建公开 Issue。
