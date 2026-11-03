"""首页卡片图瘦身判据（selenium 走 29y-sel 容器，它在 r28q-net 里能直接访问 web）。

判据（逐条 PASS/FAIL）：
  A. 375 宽 DPR 3：每张卡片 currentSrc 必须是 -960，不能是 -1536；
  B. 1440 宽 DPR 1：每张卡片 currentSrc 必须是 -480；
  C. 两种情况各截一张卡片放大图（PNG，element 截图后 3 倍放大）；
  D. src/srcset/sizes/width/height/loading 写法符合要求。
"""
import io, json, os, sys, time
from selenium import webdriver
from selenium.webdriver.chrome.options import Options

OUT = r"E:\破甲工作区\.tmp\29y\card-shots"
os.makedirs(OUT, exist_ok=True)
EV = r"E:\破甲工作区\packrivo-backup\deploy\verification-29y\evidence\card-shots"
os.makedirs(EV, exist_ok=True)
sys.stdout.reconfigure(encoding="utf-8")

URL = "https://www.packrivo.com/"
results = []


def ok(name, cond, detail):
    results.append((name, bool(cond)))
    print(f"  [{'PASS' if cond else 'FAIL'}] {name} :: {detail}")


def make_driver(width, height, dpr):
    o = Options()
    o.add_argument("--ignore-certificate-errors")
    o.add_argument("--no-sandbox")
    o.add_argument("--disable-dev-shm-usage")
    o.add_argument("--host-resolver-rules=MAP www.packrivo.com 172.19.0.5")
    o.set_capability("goog:loggingPrefs", {"performance": "ALL"})
    d = webdriver.Remote(command_executor="http://127.0.0.1:4444/wd/hub", options=o)
    # 用 CDP 同时设视口和 DPR
    d.execute_cdp_cmd("Emulation.setDeviceMetricsOverride", {
        "width": width, "height": height, "deviceScaleFactor": dpr, "mobile": width < 500,
    })
    return d


def measure(d, label):
    d.get(URL)
    time.sleep(2)
    # 滚到底再回顶，确保 lazy 图都加载
    d.execute_script("window.scrollTo(0, document.body.scrollHeight);")
    time.sleep(2)
    d.execute_script("window.scrollTo(0, 0);")
    time.sleep(1)
    data = d.execute_script("""
        return Array.from(document.querySelectorAll('.pk-cards-4 img')).map(im => ({
            src: im.getAttribute('src'),
            srcset: im.getAttribute('srcset'),
            sizes: im.getAttribute('sizes'),
            currentSrc: im.currentSrc,
            w: im.getAttribute('width'), h: im.getAttribute('height'),
            loading: im.getAttribute('loading'),
            complete: im.complete, nw: im.naturalWidth,
        }));
    """)
    print(f"\n--- {label}（{len(data)} 张卡片图）---")
    return data


def card_zoom(d, name, idx=0):
    """截第 idx 张卡片，放大 3 倍存 PNG。"""
    el = d.find_elements("css selector", ".pk-cards-4 .pk-product-card")[idx]
    d.execute_script("arguments[0].scrollIntoView({block:'center'});", el)
    time.sleep(1)
    png = el.screenshot_as_png
    from PIL import Image
    im = Image.open(io.BytesIO(png))
    im3 = im.resize((im.width * 3, im.height * 3), Image.LANCZOS)
    for p in (os.path.join(OUT, name), os.path.join(EV, name)):
        im3.save(p)
    print(f"  截图 {name}: 卡片 {im.size} -> 放大 {im3.size}")
    return im.size


EXPECT_SIZES = "(max-width:560px) calc(100vw - 48px), (max-width:900px) calc(50vw - 32px), (max-width:1100px) calc(33vw - 30px), 300px"

# ---- A: 375 / DPR 3 ----
d = make_driver(375, 812, 3)
try:
    data = measure(d, "A 375 宽 DPR 3")
    ok("A 有卡片图", len(data) >= 4, f"{len(data)} 张")
    for im in data:
        name = im["src"].rsplit("/", 1)[-1]
        ok(f"A {name} currentSrc=-960", im["currentSrc"].endswith("-960.webp"), im["currentSrc"].rsplit("/", 1)[-1])
        ok(f"A {name} 不是-1536", "-1536" not in im["currentSrc"], "-")
        ok(f"A {name} 已加载", im["complete"] and im["nw"] > 0, f"naturalWidth={im['nw']}")
    card_zoom(d, "card-mobile-375-dpr3.png")
finally:
    d.quit()

# ---- B: 1440 / DPR 1 ----
d = make_driver(1440, 900, 1)
try:
    data = measure(d, "B 1440 宽 DPR 1")
    for im in data:
        name = im["src"].rsplit("/", 1)[-1]
        ok(f"B {name} currentSrc=-480", im["currentSrc"].endswith("-480.webp"), im["currentSrc"].rsplit("/", 1)[-1])
        ok(f"B {name} 已加载", im["complete"] and im["nw"] > 0, f"naturalWidth={im['nw']}")
    # 写法判据（DOM 属性）
    im0 = data[0]
    ok("B src 用 -960", im0["src"].endswith("-960.webp"), im0["src"].rsplit("/", 1)[-1])
    ok("B srcset 只两档 480/960",
       im0["srcset"] is not None and "480w" in im0["srcset"] and "960w" in im0["srcset"] and "1536" not in im0["srcset"],
       (im0["srcset"] or "")[:120])
    ok("B sizes 按 pk-cards-4 排版", im0["sizes"] == EXPECT_SIZES, (im0["sizes"] or "")[:80])
    ok("B width/height=400/300", im0["w"] == "400" and im0["h"] == "300", f"{im0['w']}x{im0['h']}")
    ok("B loading=lazy 保留", im0["loading"] == "lazy", str(im0["loading"]))
    card_zoom(d, "card-desktop-1440-dpr1.png")
finally:
    d.quit()

bad = [n for n, c in results if not c]
print(f"\n汇总：PASS {len(results)-len(bad)} / FAIL {len(bad)}" + ("" if not bad else " —— " + "; ".join(bad)))
sys.exit(0 if not bad else 1)
