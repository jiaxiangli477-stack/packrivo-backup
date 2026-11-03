"""es 负向测试（三项，测完还原）：
  A 纯英文句           -> 必须报残留（检出能力还在）
  B 西语句夹借词/同形词 -> 不得报残留（allow-es 借词 + es 本族词不应造成误报）
  C 专名短语跨页       -> 在 pages 没列的页面注入 'Pinch Bottom Bags' 必须报残留（页面限定确实生效）
用法：python negtest-es-29y.py
"""
import io, json, subprocess, sys

REPO = r"E:\破甲工作区\packrivo-backup"
TMP = r"E:\破甲工作区\.tmp\29y"
DOCKER = r"D:\Docker\App\resources\bin\docker.exe"
PY = sys.executable
LANG = "es"
PAGE = "about"
URL = f"https://www.packrivo.com/{LANG}/{PAGE}/"
sys.stdout.reconfigure(encoding="utf-8")


def wp(*args):
    return subprocess.run([DOCKER, "exec", "php", "sh", "-lc",
                           "cd /var/www/html/www.packrivo.com/wordpress && wp " + " ".join(args)
                           + " --allow-root --skip-plugins --skip-themes"],
                          capture_output=True).stdout.decode("utf-8", "replace").strip()


def inject(pid, sent):
    return wp("eval-file", "/tmp/inject-test-29y.php", "inject", pid, f'"{sent}"')


def revert(pid):
    return wp("eval-file", "/tmp/inject-test-29y.php", "revert", pid)


def scan(tag):
    lst = f"{TMP}\\{LANG}-neg-{tag}.txt"
    io.open(lst, "w", encoding="utf-8", newline="\n").write(f"{LANG} {URL}\n")
    out = f"{LANG}-neg-{tag}"
    r = subprocess.run([PY, REPO + r"\deploy\scripts-29y\run-scan-29y.py", lst, out], capture_output=True)
    if r.returncode != 0:
        print("  扫描失败:", r.stdout.decode("utf-8", "replace")[-300:], r.stderr.decode("utf-8", "replace")[-300:])
    rows = [json.loads(l) for l in io.open(f"{TMP}\\{out}.jsonl", encoding="utf-8") if l.strip()]
    return [x for x in rows if x["url"] == URL][0]


pid = wp("post", "list", "--post_type=page", f"--name={PAGE}", "--format=ids")
print(f"测试页 {URL} id={pid}")
if not pid.isdigit():
    sys.exit("FAIL 找不到测试页")

CASES = [
    ("A", "Keep the outer carton and the flat delivery state separate in this review.",
     True, "纯英文句必须报残留"),
    ("B", "La caja usa liner kraft y packaging SOS para el envío, con una base interior y dos separadores.",
     False, "西语 + 借词/同形词不得报残留"),
    ("C", "Una prueba de página cruzada cita Pinch Bottom Bags en una página donde no corresponde.",
     True, "pages 没列的页面出现专名短语必须报残留"),
]

fails = 0
for tag, sent, want, why in CASES:
    print("\n" + "=" * 80)
    print(f"[{tag}] {why}")
    print("   句:", sent)
    print("   注入:", inject(pid, sent))
    row = scan(tag)
    hit = row["residual_count"] > 0 or row["long_runs"] >= 1
    print(f"   residual_count={row['residual_count']} long_runs={row['long_runs']}")
    print(f"   残留词={row['residual_words'][:10]}")
    print(f"   段样={row['long_run_samples'][:1] or row['english_segments'][:1]}")
    ok = (hit == want)
    print(f"   判定={'PASS' if ok else 'FAIL'}（期望{'报残留' if want else '干净'}）")
    if not ok:
        fails += 1
    print("   还原:", revert(pid))

print(f"\n负向测试 3 项：PASS {3 - fails} / FAIL {fails}")
sys.exit(0 if fails == 0 else 1)
