# AI 开发指南

## 项目说明

本项目是适用于 Blessing Skin 5/6 的河南高校统一认证插件。各学校的认证逻辑彼此独立，学校 Provider 位于 `src/Schools`，统一由 `src/SchoolRegistry.php` 注册和调用。

开发前优先阅读 [Blessing Skin 官方插件开发文档](https://bs-plugin.netlify.app/)。涉及插件结构、入口、路由、视图、用户认证或 `package.json` 时，以官方文档为准，不要凭空假设 Blessing Skin API。

## 修改原则

- 只修改任务涉及的学校 Provider，不要顺带重写其他学校代码。
- 新增学校时实现 `SchoolAuth`，并在 `SchoolRegistry::SCHOOLS` 注册学校标识、名称、邮箱后缀和认证类。
- `login()` 成功返回 `true`，账号或密码错误返回 `false`，无法判断的异常情况抛出异常。
- 保持现有 PHP 实现，不引入 Python、Node.js 常驻服务或无必要的第三方依赖。
- 保持 Blessing Skin 5/6 兼容，不随意修改 `bootstrap.php`、公共登录流程和已有路由。
- 不在代码、提交信息、日志或文档中写入真实账号、密码、Cookie、验证码或 ticket。
- 修改前先阅读相关文件；修改后检查差异，避免提交临时文件、调试输出和无关格式化。

## AI 使用方式

推荐使用 AI 辅助分析学校认证流程、编写 Provider、解释报错和检查改动。AI 生成的代码必须由提交者确认，不能未经检查直接合并。

给 AI 下达任务时，应同时说明学校名称、目标认证入口、预期改动范围，并要求先阅读本文件和官方插件开发文档。

## 提交信息

提交信息采用 Conventional Commits，并使用中文描述：

```text
<type>(可选范围): <中文说明>
```

常用类型：

- `feat`: 新增学校或功能。
- `fix`: 修复认证问题。
- `docs`: 修改文档。
- `refactor`: 重构但不改变功能。
- `chore`: 仓库维护或杂项调整。
- `test`: 添加或修改测试。

示例：

```text
feat: 新增河南大学统一认证
fix(ncwu): 修复华水登录响应解析
docs: 补充学校接入说明
refactor(zzu): 整理郑大认证请求流程
```

不要使用 `update`、`修改一下`、`临时提交` 等含义不清的提交信息。

## 提交流程

贡献者在自己的 Fork 和分支中完成修改，推送后向 `chank616/auth-henan` 的 `main` 分支提交 Pull Request。
