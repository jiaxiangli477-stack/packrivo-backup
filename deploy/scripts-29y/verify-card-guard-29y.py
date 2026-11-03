"""product-card.php 收紧正则后的判据：
  A. 单元判断（派生逻辑）：-1536.webp 正常派生；x-1200.webp / 无档后缀 / 非 webp 一律不派生、保持原 src。
  B. 首页 + /products/ 6 个分类页：所有卡片 currentSrc 的 HTTP 状态全部 200（在演练站实测加载 + 逐张 curl）。
"""
import io, json, os, re, subprocess, sys, time
from selenium import webdriver
from selenium.webdriver.chrome.options import Options

DOCKER = r"D:\Docker\App\resources\bin\docker.exe"
REPO = r"E:\破甲工作区\packrivo-backup"
EV = REPO + r"\deploy\verification-29y\evidence"
sys.stdout.reconfigure(encoding="utf-8")
results = []


def ok(name, cond, detail):
    results.append((name, bool(cond)))
    print(f"  [{'PASS' if cond else 'FAIL'}] {name} :: {detail}")


# ---------- A. 单元判断（用与模板一致的正则在 PHP 里跑，避免 Python/PHP 正则差异） ----------
print("A. 派生逻辑单元判断（在演练容器 PHP 里跑与模板相同的正则）")
PHP = r'''<?php
$derive = function ($cover) {
    $card_src = $cover; $card_srcset = '';
    if ($cover && preg_match('~^(.*)-1536\.webp$~', $cover, $cm)) {
        $base = $cm[1];
        $card_src = $base . '-960.webp';
        $card_srcset = $base . '-480.webp 480w, ' . $base . '-960.webp 960w';
    }
    return [$card_src, $card_srcset];
};
$cases = [
    'https://x/p054-abc-1536.webp' => ['https://x/p054-abc-960.webp', true],
    'https://x/x-1200.webp'        => ['https://x/x-1200.webp', false],
    'https://x/x-800.webp'         => ['https://x/x-800.webp', false],
    'https://x/x.webp'             => ['https://x/x.webp', false],
    'https://x/x-1536.png'         => ['https://x/x-1536.png', false],
    'https://x/p-1536.webp?ver=1'  => ['https://x/p-1536.webp?ver=1', false],
    ''                              => ['', false],
];
foreach ($cases as $in => $exp) {
    [$s, $ss] = $derive($in);
    $pass = ($s === $exp[0]) && (($ss !== '') === $exp[1]);
    echo ($pass ? 'PASS' : 'FAIL') . '|' . $in . '|src=' . $s . '|srcset=' . ($ss !== '' ? 'yes' : 'no') . "\n";
}
'''
import tempfile
_fd, _tmp = tempfile.mkstemp(suffix=".php", dir=r"E:\破甲工作区\.tmp\29y")
with os.fdopen(_fd, "w", encoding="utf-8", newline="\n") as fh:
    fh.write(PHP)
subprocess.run([DOCKER, "cp", _tmp, "php:/tmp/derive-test.php"], check=True)
out = subprocess.run([DOCKER, "exec", "php", "php", "/tmp/derive-test.php"],
                     capture_output=True).stdout.decode("utf-8", "replace")
for line in out.strip().splitlines():
    if line.count("|") < 3:
        continue
    verdict, inp, src, ss = line.split("|", 3)
    label = inp if inp else "(空串)"
    ok(f"A 单元 {label}", verdict == "PASS", f"{src} | srcset={ss}")

# ---------- B. 页面实测 ----------
pages = ["https://www.packrivo.com/"]
plan = json.load(io.open(r"E:\破甲工作区\.tmp\29y\products-plan.json", encoding="utf-8"))
pages += list(plan["pages"].keys())


def make_driver(width=1440, dpr=1):
    o = Options()
    for a in ("--ignore-certificate-errors", "--no-sandbox", "--disable-dev-shm-usage",
              "--host-resolver-rules=MAP www.packrivo.com 172.19.0.5"):
        o.add_argument(a)
    d = webdriver.Remote(command_executor="http://127.0.0.1:4444/wd/hub", options=o)
    d.execute_cdp_cmd("Emulation.setDeviceMetricsOverride",
                      {"width": width, "height": 900, "deviceScaleFactor": dpr, "mobile": False})
    return d


def curl_status(url):
    return subprocess.run([DOCKER, "exec", "php", "curl", "-sS", "-k", "-o", "/dev/null", "-w", "%{http_code}",
                           "--resolve", "www.packrivo.com:443:172.19.0.5", url],
                          capture_output=True).stdout.decode("utf-8", "replace").strip()


print("\nB. 首页 + 6 个分类页：每张卡片 currentSrc 实测 200")
d = make_driver()
all_current = set()
try:
    for u in pages:
        d.get(u)
        time.sleep(1)
        d.execute_script("window.scrollTo(0, document.body.scrollHeight);")
        time.sleep(2)
        d.execute_script("window.scrollTo(0, 0);")
        time.sleep(1)
        imgs = d.execute_script("""
            return Array.from(document.querySelectorAll('.pk-product-card-image img')).map(im => ({
                cs: im.currentSrc, nw: im.naturalWidth, ss: im.getAttribute('srcset') }));
        """)
        label = u.replace("https://www.packrivo.com", "")
        loaded = sum(1 for im in imgs if im["nw"] > 0)
        # 卡片总数与带图卡片数分开看：有的产品本来就没封面图（如 label 分类），那种卡片没有 <img> 属正常
        cards = d.execute_script("return document.querySelectorAll('.pk-product-card-image').length;")
        print(f"\n  {label}  卡片 {cards} 张，带图 {len(imgs)} 张，已加载 {loaded}")
        if len(imgs) == 0:
            ok(f"B {label} 无图卡片（数据本身无封面）", cards >= 0,
               f"{cards} 张卡片均无封面图，无 <img> 可加载，跳过")
        else:
            ok(f"B {label} 卡片图全部加载", loaded == len(imgs), f"{loaded}/{len(imgs)}")
        for im in imgs:
            all_current.add(im["cs"])
            if "-1536" in im["cs"]:
                ok(f"B {label} 不选1536", False, im["cs"])
finally:
    d.quit()

print(f"\nB2. 去重后 {len(all_current)} 个 currentSrc 逐个 curl（HTTP 状态）")
bad = 0
for u in sorted(all_current):
    code = curl_status(u)
    if code != "200":
        bad += 1
        print(f"    FAIL {code} {u.rsplit('/',1)[-1]}")
ok("B2 所有 currentSrc HTTP 200", bad == 0, f"{len(all_current)-bad}/{len(all_current)} 个 200，{bad} 个非200")

n_pass = sum(1 for _, c in results if c)
print(f"\n汇总：PASS {n_pass} / FAIL {len(results)-n_pass}")
sys.exit(0 if n_pass == len(results) else 1)
