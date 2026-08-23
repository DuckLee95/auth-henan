# 豫高联认证

面向河南高校联合皮肤站的学校统一身份认证插件，适用于 Blessing Skin 5/6。

本项目采用“每所学校由本校同学维护”的方式持续扩展：熟悉本校统一认证流程的贡献者负责实现和维护对应 Provider，项目维护者负责公共结构、代码审核和版本发布。

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

## 安装

1. 将本项目克隆或下载到皮肤站的 `plugins/auth-henan` 目录。
2. 确认 PHP 已启用 `bcmath` 和 `openssl` 扩展。
3. 在 Blessing Skin 插件管理页面启用 `auth-henan`。

## 参与学校维护

我们欢迎各学校的同学负责本校适配，基本流程如下：

1. 先提交 Issue，说明准备适配或接手维护的学校，避免重复开发。
2. Fork 本仓库，在自己的仓库中完成开发并上传代码。
3. 在自己的测试环境中确认认证成功、错误密码和异常情况均符合预期。
4. 测试完成后，向本仓库提交 Pull Request，说明改动和测试结果。
5. Pull Request 合并后，贡献者会加入贡献者名单；愿意持续跟进该校认证变化的人可以登记为学校维护者。

所有外部贡献均通过 Fork 和 Pull Request 完成，不需要主仓库协作者权限。

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

### 学校维护者

| 学校 | 维护者 | 状态 |
| --- | --- | --- |
| 华北水利水电大学 | [Chank616](https://github.com/chank616) | 维护中 |
| 郑州大学 | 待认领 | 招募中 |

学校适配、问题修复、测试或文档贡献合并后，可以加入贡献者名单。愿意持续负责某所学校认证适配的人，可以申请登记为学校维护者。学校维护者是一项维护责任，不代表拥有主仓库写入权限；后续更新仍通过 Fork 和 Pull Request 提交。

## 安全

请勿在代码、Issue、Pull Request、日志或测试数据中提交真实密码、Cookie、验证码、ticket 等敏感信息。详细要求见 [SECURITY.md](SECURITY.md)。

## License

[MIT License](LICENSE)
