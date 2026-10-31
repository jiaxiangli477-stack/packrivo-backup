"""29y 报价演练 v2：浏览器跑在演练网段里，先验证「确实连到演练栈」再动表单。

v1 的教训：宿主机根本连不到演练网关 172.19.0.1:443（Docker Desktop 走 WSL2，宿主不通），
Chrome 于是按真实 DNS 解析到了线上站，把三份演练报价单写进了生产库（PKQ-O24CQ / PKQ-9IK2C / PKQ-48FGX）。
所以 v2 做三件事：
  1) 浏览器用 selenium/standalone-chrome 容器、--network r28q-net，只能落到演练栈；
  2) 打开前先取 /29y-marker.txt，内容不等于本次标记就立刻退出，绝不碰表单；
  3) 关掉 Chrome 的 DoH / AsyncDns（它们会绕过 --host-resolver-rules）。

用法：
  python drill-quote-browser-29y.py --marker mk-xxxx --dims 9.5,9.5,4 --qty 500 --print 0 --finish 0 --tag A
环境变量：SELENIUM_URL（默认 http://127.0.0.1:4444/wd/hub）、REHEARSAL_IP（默认 172.19.0.5）
"""
import argparse, json, os, sys, time

from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

BASE = "https://www.packrivo.com"
OUT = r"E:\破甲工作区\.tmp\29y\drill"


def build_driver():
    o = Options()
    o.add_argument("--ignore-certificate-errors")
    o.add_argument("--window-size=1500,1400")
    o.add_argument(f"--host-resolver-rules=MAP www.packrivo.com {os.environ.get('REHEARSAL_IP', '172.19.0.5')}")
    o.add_argument("--dns-over-https-mode=off")
    o.add_argument("--disable-features=DnsOverHttps,AsyncDns")
    o.add_argument("--lang=nl-NL")
    o.add_argument("--no-sandbox")
    o.add_argument("--disable-dev-shm-usage")
    return webdriver.Remote(command_executor=os.environ.get("SELENIUM_URL", "http://127.0.0.1:4444/wd/hub"),
                            options=o)


def aside_rows(driver):
    return driver.execute_script(
        "var out=[];document.querySelectorAll('.pkq-aside dl').forEach(function(dl){"
        "var dts=dl.querySelectorAll('dt'),dds=dl.querySelectorAll('dd');"
        "for(var i=0;i<dts.length;i++){out.push([dts[i].textContent.trim(),dds[i]?dds[i].textContent.trim():'']);}"
        "});return out;")


def main():
    sys.stdout.reconfigure(encoding="utf-8")
    ap = argparse.ArgumentParser()
    ap.add_argument("--marker", required=True)
    ap.add_argument("--dims", default="9.5,9.5,4")
    ap.add_argument("--qty", type=int, default=500)
    ap.add_argument("--print", dest="pr", type=int, default=0)
    ap.add_argument("--finish", dest="fi", type=int, default=0)
    ap.add_argument("--material", dest="mat", type=int, default=-1)
    ap.add_argument("--tag", default="A")
    a = ap.parse_args()
    os.makedirs(OUT, exist_ok=True)

    d = build_driver()
    try:
        # 闸门一：确认目标就是演练栈
        d.get(BASE + "/29y-marker.txt")
        got = d.find_element(By.TAG_NAME, "body").text.strip()
        print("标记文件内容:", got, "| 期望:", a.marker)
        if got != a.marker:
            print("FAIL 目标不是演练栈，立即停止（不碰表单、不发任何请求）")
            raise SystemExit(2)
        print("PASS 目标确认是演练栈")

        d.get(BASE + "/nl/get-a-quote/")
        WebDriverWait(d, 60).until(EC.presence_of_element_located((By.CSS_SELECTOR, ".pkq-tile")))
        print("页面标题:", d.title, "| PKQ lang:", d.execute_script("return (window.PKQ||{}).lang"))

        # 先关掉 cookie 同意框，否则提交按钮被它挡住
        consent = d.find_elements(By.CSS_SELECTOR, "#pr-consent")
        if consent:
            btns = consent[0].find_elements(By.CSS_SELECTOR, "button")
            print("同意框按钮:", [b.text.strip() for b in btns])
            print("同意框 html 片段:", consent[0].get_attribute("outerHTML")[:400].replace("\n", " "))
            clicked = False
            for b in btns:
                t = b.text.strip().lower()
                if any(w in t for w in ("accepteer", "accepteren", "alles", "ok", "allow")):
                    d.execute_script("arguments[0].click();", b)
                    clicked = True
                    print("已点击:", b.text.strip())
                    break
            if not clicked and btns:
                d.execute_script("arguments[0].click();", btns[0])
                print("已点击第一个按钮:", btns[0].text.strip())
            time.sleep(0.8)
            print("同意框还在？", bool(d.find_elements(By.CSS_SELECTOR, "#pr-consent:not([hidden])")))

        tiles = d.find_elements(By.CSS_SELECTOR, ".pkq-tile")
        print(f"款式磁贴 {len(tiles)} 个:", [t.find_element(By.CSS_SELECTOR, ".pkq-tile-name").text for t in tiles])
        tiles[3].click()
        time.sleep(0.6)
        d.save_screenshot(os.path.join(OUT, f"drill{a.tag}-01-style.png"))

        d.find_elements(By.CSS_SELECTOR, ".pkq-step")[1].click()
        time.sleep(0.4)
        L, W, H = [float(x) for x in a.dims.split(",")]
        d.execute_script(
            "var ins=document.querySelectorAll('.pkq-dims input');var vals=[arguments[0],arguments[1],arguments[2]];"
            "for(var i=0;i<ins.length;i++){ins[i].value=vals[i];ins[i].dispatchEvent(new Event('input',{bubbles:true}));}",
            L, W, H)
        time.sleep(0.4)
        print("尺寸输入:", d.execute_script(
            "return Array.prototype.map.call(document.querySelectorAll('.pkq-dims input'),function(i){return i.value;})"))
        d.save_screenshot(os.path.join(OUT, f"drill{a.tag}-02-size.png"))

        d.find_elements(By.CSS_SELECTOR, ".pkq-step")[2].click()
        time.sleep(0.4)
        rows = d.find_elements(By.CSS_SELECTOR, ".pkq-options")
        print(f"选项组 {len(rows)} 组")
        if a.mat >= 0:
            rows[0].find_elements(By.CSS_SELECTOR, ".pkq-chip")[a.mat].click()
            time.sleep(0.3)
            rows = d.find_elements(By.CSS_SELECTOR, ".pkq-options")
        rows[1].find_elements(By.CSS_SELECTOR, ".pkq-chip")[a.pr].click()
        time.sleep(0.3)
        rows = d.find_elements(By.CSS_SELECTOR, ".pkq-options")
        rows[2].find_elements(By.CSS_SELECTOR, ".pkq-chip")[a.fi].click()
        time.sleep(0.4)
        print("已选（板/印刷/后道）:", d.execute_script(
            "return Array.prototype.map.call(document.querySelectorAll('.pkq-options'),function(g){"
            "var a=g.querySelector('.pkq-chip.is-active');return a?a.textContent.trim():'';});"))
        d.save_screenshot(os.path.join(OUT, f"drill{a.tag}-03-material.png"))

        d.find_elements(By.CSS_SELECTOR, ".pkq-step")[3].click()
        time.sleep(0.4)
        d.execute_script("var r=document.querySelector('.pkq-range');r.value=arguments[0];"
                         "r.dispatchEvent(new Event('input',{bubbles:true}));", a.qty)
        time.sleep(0.5)
        print("数量显示:", d.find_element(By.CSS_SELECTOR, ".pkq-big").text)
        rows_now = aside_rows(d)
        print("页面右侧价格卡:", json.dumps(rows_now, ensure_ascii=False))
        table = d.execute_script(
            "var out=[];document.querySelectorAll('.pkq-table tbody tr').forEach(function(tr){"
            "var t=Array.prototype.map.call(tr.querySelectorAll('td'),function(td){return td.textContent.trim();});"
            "out.push({qty:t[0],unit:t[1],total:t[2],active:tr.className.indexOf('is-active')>=0});});return out;")
        active_row = next((r for r in table if r["active"]), None)
        print("数量表当前行:", json.dumps(active_row, ensure_ascii=False))
        d.save_screenshot(os.path.join(OUT, f"drill{a.tag}-04-qty.png"))

        d.find_elements(By.CSS_SELECTOR, ".pkq-step")[4].click()
        time.sleep(0.5)
        for name, val in [("name", "29y drill " + a.tag), ("email", "drill-29y@example.com"),
                          ("company", "Packrivo 29y rehearsal"), ("country", "NL"),
                          ("phone", "+31 20 000 0000"), ("notes", "29y headless drill, rehearsal only")]:
            el = d.find_element(By.CSS_SELECTOR,
                                f".pkq-form input[name='{name}'], .pkq-form textarea[name='{name}']")
            el.clear()
            el.send_keys(val)
        d.save_screenshot(os.path.join(OUT, f"drill{a.tag}-05-form.png"))
        price_before_submit = aside_rows(d)
        submit_text = d.find_element(By.CSS_SELECTOR, ".pkq-main button.pkq-submit").text
        print("提交按钮文字（含前端算出的总价）:", submit_text)
        d.find_element(By.CSS_SELECTOR, ".pkq-main button.pkq-submit").click()
        ref = ""
        for _ in range(60):
            time.sleep(1)
            bigs = d.find_elements(By.CSS_SELECTOR, ".pkq-big")
            if bigs and bigs[0].text.strip().startswith("PKQ-"):
                ref = bigs[0].text.strip()
                break
            st = d.find_elements(By.CSS_SELECTOR, ".pkq-status")
            if st and st[0].text.strip():
                print("提交状态栏:", st[0].text.strip())
                break
        if not ref:
            print("诊断:", json.dumps(d.execute_script(
                "var s=document.querySelector('.pkq-status');"
                "return {status:s?s.textContent.trim():'',btns:Array.prototype.map.call("
                "document.querySelectorAll('.pkq-main button'),function(b){return b.textContent.trim();})};"),
                ensure_ascii=False))
            d.save_screenshot(os.path.join(OUT, f"drill{a.tag}-06-fail.png"))
            raise SystemExit("FAIL 提交后没有拿到参考号")
        print("提交后页面显示的参考号:", ref)
        d.save_screenshot(os.path.join(OUT, f"drill{a.tag}-06-done.png"))

        res = {"tag": a.tag, "dims": [L, W, H], "qty": a.qty, "print_index": a.pr, "finish_index": a.fi,
               "aside_price_rows": price_before_submit, "qty_table_active_row": active_row,
               "submit_button_text": submit_text, "ref": ref}
        p = os.path.join(OUT, f"drill{a.tag}.json")
        with open(p, "w", encoding="utf-8", newline="\n") as fh:
            json.dump(res, fh, ensure_ascii=False, indent=2)
            fh.write("\n")
        print("已写", p)
    finally:
        d.quit()


if __name__ == "__main__":
    main()
