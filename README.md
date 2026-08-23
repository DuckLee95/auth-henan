# 豫高联认证

面向河南高校联合皮肤站的学校统一身份认证插件，适用于 Blessing Skin 5/6。

本项目采用“每所学校由本校同学维护”的方式持续扩展：熟悉本校统一认证流程的贡献者负责实现和维护对应 Provider，项目维护者负责公共结构、代码审核和版本发布。

## 安全

- 学校账号和密码只能用于当次身份验证，不得保存到数据库、缓存或日志。
- 不得把学校返回的 Cookie、ticket 或完整认证响应发送给浏览器。
- 不得在代码、Issue、Pull Request、日志、截图或测试数据中提交真实账号、密码、Cookie、验证码、ticket 等敏感信息。
- 发现认证绕过、凭据泄漏等安全问题时，请私下联系项目维护者，不要创建公开 Issue。

## 已支持学校

| 学校 | 标识 | 维护状态 |
| --- | --- | --- |
| 华北水利水电大学 | `ncwu` | 维护中 |
| 郑州大学 | `zzu` | 维护中，招募维护者 |

## 工作方式

1. 用户在皮肤站选择学校，并提交学号和学校密码。
2. 插件调用该学校独立的 PHP Provider 完成一次身份验证。
3. 验证成功后，插件按学校邮箱后缀创建或关联皮肤站账号。
4. 学校密码只用于本次验证，不应被插件保存。

所有学校认证均使用 PHP 实现，不需要 Python、Node.js 或额外的常驻服务。

## 贡献流程

所有外部贡献都通过 Fork 和 Pull Request 完成，不需要主仓库写入权限。

### 1. 认领学校

先创建 Issue，写明学校名称、统一认证入口、准备新增还是修复现有 Provider，以及是否愿意持续维护。不要在 Issue 中发送测试账号或密码。

### 2. Fork 并创建分支

点击 GitHub 页面右上角的 **Fork**，将仓库复制到自己的账号，然后克隆自己的 Fork：

```bash
git clone https://github.com/YOUR_GITHUB_USERNAME/auth-henan.git
cd auth-henan
git switch -c feat/example
```

请把 `YOUR_GITHUB_USERNAME` 和 `example` 换成自己的 GitHub 用户名和学校标识。修复已有学校时，可以使用 `fix/example-login` 这样的分支名。

### 3. 实现学校 Provider

1. 在 `src/Schools` 新增或修改该校认证类，并实现 `SchoolAuth::login()`。
2. 新学校需要在 `src/SchoolRegistry.php` 注册学校标识、名称、邮箱后缀和 Provider 类。
3. 认证成功返回 `true`，账号或密码错误返回 `false`；网络故障或学校认证流程变化时抛出不含敏感信息的异常。
4. 不要修改其他学校的 Provider；公共流程确实需要调整时，请在 Pull Request 中单独说明原因和影响范围。

实现接口和代码示例见 [CONTRIBUTING.md](CONTRIBUTING.md)。

### 4. 在自己的环境测试

不要直接在正式皮肤站测试。请准备独立的 Blessing Skin 5/6 测试站，将自己 Fork 中的功能分支放入测试站的 `plugins/auth-henan`，确认 PHP 已启用 `bcmath` 和 `openssl`，并允许用户注册及使用角色名注册，然后在插件管理中启用或重载本插件。

先检查改动过的 PHP 文件是否存在语法错误：

```bash
php -l src/Schools/ExampleAuth.php
php -l src/SchoolRegistry.php
git diff --check
```

请把 `ExampleAuth.php` 换成实际修改的 Provider 文件名；没有修改注册表时可以跳过第二条命令。

随后访问测试站的 `/auth/login/henan`，选择对应学校并完成以下测试：

| 场景 | 操作 | 通过标准 |
| --- | --- | --- |
| 正确凭据，新用户 | 使用尚未注册的学号和新的游戏角色名登录 | 跳转到 `/user`，只创建一个皮肤站账号，邮箱后缀与注册表一致 |
| 正确凭据，已有用户 | 使用已经成功认证过的学号再次登录 | 登录到原账号，不创建重复账号或角色 |
| 错误密码 | 使用正确学号和错误密码登录 | 明确提示认证失败，不创建账号，不进入用户中心 |
| 空输入 | 分别留空学号、密码或新用户必填的角色名 | 页面正常给出校验提示，不出现 500 错误 |
| 学校接口异常 | 在未提交的临时改动中使用不可达地址，或临时阻断测试站网络 | 显示不含 Cookie、ticket、密码及完整响应的安全错误，不创建账号 |
| 学校隔离 | 在页面选择一个未修改的已支持学校 | 学校仍正常显示且不会因本次改动报错；没有该校账号时无需测试真实登录，也不要向他人索要凭据 |

测试只能使用贡献者本人的学校账号。提交截图或日志前，必须遮盖学号、密码、Cookie、验证码、ticket 和个人信息。

### 5. 提交 Pull Request

测试通过后，将分支推送到自己的 Fork：

```bash
git add src/Schools/ExampleAuth.php src/SchoolRegistry.php README.md
git commit -m "feat: add example authentication"
git push -u origin feat/example
```

请按实际文件和学校标识调整示例中的 `ExampleAuth.php` 与 `example`。

然后向本仓库的 `main` 分支提交 Pull Request，并写清楚：

- 学校名称、学校标识和认证入口。
- 新增或修改了哪些文件。
- 上述六项测试的实际结果。
- Blessing Skin、PHP 和插件版本。
- 已知限制，例如验证码、二次认证、频率限制或校园网限制。

项目维护者审核并合并后，作者会加入 README 的贡献者名单。后续修改继续使用新的分支和 Pull Request。

## 项目结构

```text
src/
├── SchoolRegistry.php       # 学校注册表与 Provider 调度
└── Schools/
    ├── SchoolAuth.php       # 学校认证统一接口
    ├── NcwuAuth.php         # 华水 Provider
    └── ZzuAuth.php          # 郑大 Provider
```

## 贡献者

感谢所有参与学校认证适配和插件维护的贡献者。

<table>
  <tr>
    <td align="center" width="220">
      <a href="https://github.com/homoarea">
        <img src="https://github.com/homoarea.png?size=160" width="96" height="96" alt="Homoarea 的头像"><br>
        <sub><b>Homoarea</b></sub>
      </a><br>
      <sub>原始作者<br>插件基础结构、早期学校认证适配</sub>
    </td>
    <td align="center" width="220">
      <a href="https://github.com/chank616">
        <img src="https://github.com/chank616.png?size=160" width="96" height="96" alt="Chank616 的头像"><br>
        <sub><b>Chank616</b></sub>
      </a><br>
      <sub>当前维护者<br>PHP 重构、Provider POC、部署与文档</sub>
    </td>
  </tr>
</table>

## License

[MIT License](LICENSE)
