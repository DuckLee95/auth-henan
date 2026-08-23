# Claude 开发指南

开始工作前先完整阅读根目录的 `AGENTS.md`，并遵守其中的项目结构、修改范围和敏感信息要求。

- Blessing Skin 相关实现以[官方插件开发文档](https://bs-plugin.netlify.app/)为准。
- 只修改任务涉及的学校 Provider；新增学校时同步更新 `src/SchoolRegistry.php`。
- 优先沿用现有 PHP 代码，不增加 Python、Node.js 常驻服务或无必要依赖。
- 生成代码后检查完整差异，不提交调试输出、临时文件或真实凭据。

提交信息必须使用 Conventional Commits 类型和中文说明，格式为：

```text
<type>(可选范围): <中文说明>
```

例如：

```text
feat: 新增河南大学统一认证
fix(ncwu): 修复华水登录响应解析
docs: 更新贡献流程
```

详细规则以 `AGENTS.md` 为准。
