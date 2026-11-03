"""29y 负向测试（豁免表改动后必跑）：
   A. 任意 <lang> 页注入 "paper bags with lid cover" -> 必须 long_runs >= 1；
   B. /<lang>/product-paper-merchandise-sleeve-bag/ 上的 "Pinch Bottom Bags" 不报（短语豁免只在列出的页面、整短语生效）。

用法：python negtest-allow-29y.py [lang]   （默认 it）
"""
import io, json, subprocess, sys

REPO = r"E:\破甲工作区\packrivo-backup"
TMP = r"E:\破甲工作区\.tmp\29y"
DOCKER = r"D:\Docker\App\resources\bin\docker.exe"
PY = sys.executable
sys.stdout.reconfigure(encoding="utf-8")

LANG = sys.argv[1] if len(sys.argv) > 1 else "it"
SENT = "paper bags with lid cover"
PAGE_SLUG = "about"
LIST = TMP + f"\\{LANG}-negt.txt"
OUT = f"{LANG}-negt"


def wp(*args):
    return subprocess.run([DOCKER, "exec", "php", "sh", "-lc",
                           "cd /var/www/html/www.packrivo.com/wordpress && wp " + " ".join(args)
                           + " --allow-root --skip-plugins --skip-themes"],
                          capture_output=True).stdout.decode("utf-8", "replace").strip()


pid = wp("post", "list", "--post_type=page", f"--name={PAGE_SLUG}", "--format=ids")
print(f"测试页 slug={PAGE_SLUG} id={pid}")
if not pid.isdigit():
    print("FAIL 找不到测试页")
    sys.exit(1)

io.open(LIST, "w", encoding="utf-8", newline="\n").write(
    f"{LANG} https://www.packrivo.com/{LANG}/{PAGE_SLUG}/\n"
    f"{LANG} https://www.packrivo.com/{LANG}/product-paper-merchandise-sleeve-bag/\n"
)

subprocess.run([DOCKER, "cp", REPO + r"\deploy\scripts-29y\inject-test-29y.php", "php:/tmp/inject-test-29y.php"], check=True)
print("注入:", wp("eval-file", "/tmp/inject-test-29y.php", "inject", pid, f'"{SENT}"'))

r = subprocess.run([PY, REPO + r"\deploy\scripts-29y\run-scan-29y.py", LIST, OUT], capture_output=True)
print(r.stdout.decode("utf-8", "replace"))
print(r.stderr.decode("utf-8", "replace")[:500])

rows = [json.loads(l) for l in io.open(TMP + f"\\{OUT}.jsonl", encoding="utf-8") if l.strip()]
by = {x["url"]: x for x in rows}
a = by.get(f"https://www.packrivo.com/{LANG}/{PAGE_SLUG}/")
b = by.get(f"https://www.packrivo.com/{LANG}/product-paper-merchandise-sleeve-bag/")

ok_a = bool(a) and a["long_runs"] >= 1
print(f"\nA 注入句「{SENT}」 -> long_runs={a['long_runs'] if a else 'NA'} "
      f"段样={a['long_run_samples'] if a else ''}  判定={'PASS' if ok_a else 'FAIL'}")

pinch_hit = bool(b) and any(w in ("pinch", "bottom", "bags") for w in b["residual_words"])
ok_b = bool(b) and b["long_runs"] == 0 and not pinch_hit
print(f"B sleeve-bag 页 -> long_runs={b['long_runs'] if b else 'NA'} "
      f"残留词含 pinch/bottom/bags={pinch_hit} "
      f"豁免命中={list((b.get('allowed_used') or {}).keys()) if b else ''}  判定={'PASS' if ok_b else 'FAIL'}")

print("\n还原:", wp("eval-file", "/tmp/inject-test-29y.php", "revert", pid))
print("\n汇总：", "PASS A/B 两条都符合预期" if (ok_a and ok_b) else "FAIL 有用例不符")
sys.exit(0 if (ok_a and ok_b) else 1)
