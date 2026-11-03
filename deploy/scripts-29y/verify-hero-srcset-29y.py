"""首页大图 srcset+?ver 判据（逐条 PASS/FAIL）。全部请求在演练容器内发。"""
import json
import re
import subprocess
import sys

DOCKER = r"D:\Docker\App\resources\bin\docker.exe"
HOST = "www.packrivo.com:443:172.19.0.5"
EXPECT = {"hero-480.webp": "266032c7bdfc0f51351bb1e7b0607ca66e07bb95fc7af1c1a9c6ab5075ee839d",
          "hero.webp": "6a55801599da73d2bd8148f152f04b469c2bc8076f57b36f1dc64ecc8e623e1e"}
sys.stdout.reconfigure(encoding="utf-8")
results = []


def ok(name, cond, detail):
    results.append((name, bool(cond), detail))
    print(f"  [{'PASS' if cond else 'FAIL'}] {name} :: {detail}")


def page(path):
    return subprocess.run([DOCKER, "exec", "php", "curl", "-sS", "-k", "--resolve", HOST,
                           "https://www.packrivo.com" + path], capture_output=True).stdout.decode("utf-8", "replace")


print("一、/ 与 /it/ 的 hero src / srcset 都带 ?ver=4.0.6")
for path in ("/", "/it/"):
    html = page(path)
    imgs = re.findall(r"<img[^>]*hero[^>]*>", html)
    tag = imgs[0] if imgs else ""
    src = re.search(r'\ssrc="([^"]*hero\.webp[^"]*)"', tag)
    ss = re.search(r'\ssrcset="([^"]*)"', tag)
    cands = [c.strip().split(" ")[0] for c in ss.group(1).split(",")] if ss else []
    ok(f"{path} 只有一个 hero <img>", len(imgs) == 1, f"实测 {len(imgs)} 个")
    ok(f"{path} src 带 ?ver=4.0.6", bool(src) and src.group(1).endswith("?ver=4.0.6"), (src.group(1) if src else "无 src"))
    ok(f"{path} srcset 两档都带 ?ver=4.0.6",
       len(cands) == 2 and all(c.endswith("?ver=4.0.6") for c in cands),
       " | ".join(cands))
    widths = re.findall(r"(\d+)w", ss.group(1)) if ss else []
    ok(f"{path} srcset 是 480w/1200w 两档", widths == ["480", "1200"], str(widths))
    ok(f"{path} sizes 未变", 'sizes="(max-width: 767px) 92vw, 640px"' in tag, "92vw, 640px" in tag and "命中")
    ok(f"{path} loading=eager 未变", 'loading="eager"' in tag, "命中" if 'loading="eager"' in tag else "缺失")

print("\n二、两档图片 HTTP 200 + sha256")
for name, want in EXPECT.items():
    r = subprocess.run([DOCKER, "exec", "php", "sh", "-lc",
                        f"curl -sS -k -o /tmp/{name} -w '%{{http_code}}' --resolve {HOST} "
                        f"'https://www.packrivo.com/wp-content/themes/packrivo-home-v4/assets/img/{name}?ver=4.0.6'; "
                        f"echo; sha256sum /tmp/{name} | cut -d' ' -f1; stat -c%s /tmp/{name}"],
                       capture_output=True).stdout.decode("utf-8", "replace").split()
    code, sha, size = r[0], r[1], r[2]
    ok(f"{name} HTTP 200", code == "200", f"HTTP {code}")
    ok(f"{name} sha256 相符", sha == want, f"{sha[:16]}… vs 期望 {want[:16]}…（{size} B）")

print("\n三、分类卡片 p054 不受影响")
html = page("/")
card = re.findall(r'<img[^>]*p054[^>]*>', html)
tag = card[0] if card else ""
ok("p054 卡片图仍在", len(card) >= 1, f"{len(card)} 个")
ok("p054 卡片图没被加 ?ver", "?ver=" not in tag, "无 ?ver" if "?ver=" not in tag else tag[:120])
ok("p054 卡片图没被加 srcset", "srcset" not in tag, "无 srcset" if "srcset" not in tag else "有 srcset")

bad = [n for n, c, _ in results if not c]
print(f"\n汇总：PASS {len(results)-len(bad)} / FAIL {len(bad)}" + ("" if not bad else " —— 失败项: " + ", ".join(bad)))
sys.exit(0 if not bad else 1)
