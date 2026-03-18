import asyncio
import nodriver as uc
import re
import sys

arg_username=sys.argv[1]
arg_password=sys.argv[2]

async def login():
    browser = await uc.start(
        headless=True,
        browser_args=[
            "--disable-gpu",
            "--no-sandbox",
            "--disable-dev-shm-usage",
            "--disable-browser-side-navigation",
            "--blink-settings=imagesEnabled=false",
            "--dns-prefetch-disable",
            "--incognito",
            "--disable-logging",
            "--silent",
            "--log-level=3",
        ],
    )
    page = await browser.get("https://authserver.ncwu.edu.cn/authserver/login")

    try:
        await asyncio.sleep(1)
        username = await page.wait_for("#username")
        await username.send_keys(arg_username)

        password = await page.wait_for("#passwordShow")
        await password.send_keys(arg_password)

        login_btn = await page.wait_for("#passbutton")
        await login_btn.click()

        await asyncio.sleep(1)
        pattern=r'<h2\s*>(.*?)</h2>'
        msg = await page.select("#msg>h2")
        result=re.search(pattern,str(msg))
        print(result.group(1)=='登录成功')

    except Exception as e:
        print(f"error: {e}")
        with open("debug_page.html", "w", encoding="utf-8") as f:
            f.write(await page.get_content())


if __name__ == "__main__":
    uc.loop().run_until_complete(login())
