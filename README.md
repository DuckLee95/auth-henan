# 豫高联认证

面向河南高校联合皮肤站的学校统一身份认证插件，适用于 Blessing Skin 5/6。

本项目采用“每所学校由本校同学维护”的方式持续扩展：各校社长在校内分配熟悉统一认证流程的同学负责对应 Provider，豫高联皮肤站负责人负责协作者管理和跨学校沟通。

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

本项目采用协作者模式。各校负责人由豫高联皮肤站负责人添加为 GitHub 协作者，在独立分支完成自己学校的改动，并自行提交和合并 Pull Request。

### 1. 确认校内分工

各校社长在校内确定负责同学，并把负责人的 GitHub 用户名发给豫高联皮肤站负责人。负责人接受仓库协作者邀请后即可开始；接口范围不明确时，由各校社长与豫高联皮肤站负责人沟通确认。

### 2. 克隆仓库并创建分支

接受协作者邀请后，克隆本仓库并为自己的任务创建分支：

```bash
git clone https://github.com/chank616/auth-henan.git
cd auth-henan
git switch -c feat/example
```

请把 `example` 换成学校标识。修复已有学校时，可以使用 `fix/example-login` 这样的分支名。不要直接向 `main` 分支提交代码。

### 3. 实现学校 Provider

1. 在 `src/Schools` 新增或修改该校认证类，并实现 `SchoolAuth::login()`。
2. 新学校需要在 `src/SchoolRegistry.php` 注册学校标识、名称、邮箱后缀和 Provider 类。
3. 认证成功返回 `true`，账号或密码错误返回 `false`；网络故障或学校认证流程变化时抛出不含敏感信息的异常。
4. 不要修改其他学校的 Provider；公共流程确实需要调整时，请在 Pull Request 中单独说明原因和影响范围。

实现接口和代码示例见 [CONTRIBUTING.md](CONTRIBUTING.md)。

### 4. 提交并合并

完成改动后，将分支推送到本仓库：

```bash
git add src/Schools/ExampleAuth.php src/SchoolRegistry.php README.md
git commit -m "feat: add example authentication"
git push -u origin feat/example
```

请按实际文件和学校标识调整示例中的 `ExampleAuth.php` 与 `example`。

然后从自己的功能分支向 `main` 分支提交 Pull Request，并写清楚：

- 学校名称、学校标识和认证入口。
- 新增或修改了哪些文件。
- 有验证码、二次认证等限制时简单说明。

各校负责人自行确认并合并自己负责的 Pull Request。首次学校适配或修复合并后，作者会加入 README 的贡献者名单；后续修改继续使用新的分支和 Pull Request。

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
