# 豫高联认证

面向河南高校联合皮肤站的学校统一身份认证插件，适用于 Blessing Skin 5/6。

本项目采用“每所学校由本校同学维护”的方式持续扩展：各校社长在校内分配熟悉统一认证流程的同学负责对应 Provider，豫高联皮肤站负责人负责跨学校沟通和主仓库维护。

## 已支持学校

| 学校 | 标识 | 状态 |
| --- | --- | --- |
| 华北水利水电大学 | `ncwu` | 已接入 |
| 郑州大学 | `zzu` | 已接入 |

## 工作方式

1. 用户在皮肤站选择学校，并提交学号和学校密码。
2. 插件调用该学校独立的 PHP Provider 完成一次身份验证。
3. 验证成功后，插件按学校邮箱后缀创建或关联皮肤站账号。
4. 学校密码只用于本次验证，不应被插件保存。

所有学校认证均使用 PHP 实现，不需要 Python、Node.js 或额外的常驻服务。

## 贡献流程

1. 各校社长确定本校负责同学。
2. 负责人 Fork 本仓库，并在自己的 Fork 中创建分支。
3. 在该分支修改本校 Provider；新增学校时，同时更新 `src/SchoolRegistry.php`。
4. 将改动推送到自己的 Fork，然后向本仓库的 `main` 分支提交 Pull Request。

无需认领 Issue，也无需添加主仓库协作者。具体代码要求见 [CONTRIBUTING.md](CONTRIBUTING.md)。

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
