# 河南联合皮肤站各校统一登录
## use
1. 在插件市场启用OAuth插件
2. 将本项目克隆到plugins目录下并启用插件auth-henan
3. 创建 Python 虚拟环境。该目录仅供插件后端运行，不会发布到 `public` 目录。
```bash
cd assets
python -m venv py_venv
source py_venv/bin/activate
pip install -r requirements.txt -i https://mirrors.tuna.tsinghua.edu.cn/pypi/web/simple
```

## License

MIT License
