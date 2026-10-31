"""确认无头 Chrome 到底连的是演练栈还是线上。

宿主机有 http_proxy，Chrome 会继承它 —— 上一轮演练很可能被代理转发到真站。
本脚本强制 --no-proxy-server + host-resolver-rules，打印：
  location / server 头 / 页面里只有演练栈才有的标记 / 证书签发者
"""
import json, sys

from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait

GATEWAY = "172.19.0.1"
o = Options()
o.add_argument("--headless=new")
o.add_argument("--ignore-certificate-errors")
o.add_argument("--no-proxy-server")
o.add_argument("--proxy-bypass-list=*")
o.add_argument(f"--host-resolver-rules=MAP www.packrivo.com {GATEWAY}")
o.add_argument("--window-size=1400,1000")
o.binary_location = r"C:\Program Files\Google\Chrome\Application\chrome.exe"
d = webdriver.Chrome(options=o)
try:
    d.get("https://www.packrivo.com/nl/get-a-quote/")
    WebDriverWait(d, 40).until(lambda x: x.execute_script("return document.readyState") == "complete")
    info = d.execute_script("""
      return (async () => {
        const r = await fetch('/wp-json/', {method:'GET'});
        const h = {};
        r.headers.forEach((v,k)=>{h[k]=v;});
        return {href: location.href, server: h['server']||'', reqId: h['x-request-id']||'',
                hdrs: h, title: document.title};
      })();
    """)
    print(json.dumps(info, ensure_ascii=False, indent=2))
    print("UA:", d.execute_script("return navigator.userAgent"))
    print("演练标记 /nl/ 头部 H1:", d.execute_script(
        "var h=document.querySelector('h1');return h?h.textContent.trim():''"))
    srv = (info.get("server") or "").lower()
    print("闸门：Server =", info.get("server"), "| x-request-id =", info.get("reqId") or "-")
    if "cloudflare" in srv:
        print("FAIL 目标带 Cloudflare 头 —— 这是线上，不是演练栈")
        raise SystemExit(3)
    print("PASS 目标不是 Cloudflare 边缘")
finally:
    d.quit()
