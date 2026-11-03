"""29y 负向测试 C（同形词句）：在 /<lang>/about/ 注入
   'Production notes and structure protection for the simple zone'
   必须报残留（residual_count > 0 或 long_runs >= 1）。
   理由：这 16 个同形实词在 LOCAL_STOP 里只是打断连续段，不能被用来把整句判成法语；
   它们同时进了 _homo，所以不投「本族语」票，整句仍应判成英文并报出来。
"""
import io, json, subprocess, sys

REPO = r"E:\破甲工作区\packrivo-backup"
TMP = r"E:\破甲工作区\.tmp\29y"
DOCKER = r"D:\Docker\App\resources\bin\docker.exe"
PY = sys.executable
sys.stdout.reconfigure(encoding="utf-8")

LANG = sys.argv[1] if len(sys.argv) > 1 else "fr"
SENT = "Production notes and structure protection for the simple zone"
PAGE_SLUG = "about"
LIST = TMP + f"\\{LANG}-homo.txt"
OUT = f"{LANG}-homo"


def wp(*args):
    return subprocess.run([DOCKER, "exec", "php", "sh", "-lc",
                           "cd /var/www/html/www.packrivo.com/wordpress && wp " + " ".join(args)
                           + " --allow-root --skip-plugins --skip-themes"],
                          capture_output=True).stdout.decode("utf-8", "replace").strip()


pid = wp("post", "list", "--post_type=page", f"--name={PAGE_SLUG}", "--format=ids")
print(f"测试页 slug={PAGE_SLUG} id={pid}")
if not pid.isdigit():
    sys.exit("FAIL 找不到测试页")

io.open(LIST, "w", encoding="utf-8", newline="\n").write(
    f"{LANG} https://www.packrivo.com/{LANG}/{PAGE_SLUG}/\n")
subprocess.run([DOCKER, "cp", REPO + r"\deploy\scripts-29y\inject-test-29y.php", "php:/tmp/inject-test-29y.php"], check=True)
print("注入:", wp("eval-file", "/tmp/inject-test-29y.php", "inject", pid, f'"{SENT}"'))

r = subprocess.run([PY, REPO + r"\deploy\scripts-29y\run-scan-29y.py", LIST, OUT], capture_output=True)
print(r.stdout.decode("utf-8", "replace")[-600:])

rows = [json.loads(l) for l in io.open(TMP + f"\\{OUT}.jsonl", encoding="utf-8") if l.strip()]
row = [x for x in rows if x["url"] == f"https://www.packrivo.com/{LANG}/{PAGE_SLUG}/"][0]
print(f"\nC 同形词句 -> residual_count={row['residual_count']} long_runs={row['long_runs']}")
print("   段样:", row["long_run_samples"])
print("   残留词:", row["residual_words"][:12])
ok = row["residual_count"] > 0 or row["long_runs"] >= 1
print(f"   判定={'PASS' if ok else 'FAIL'}")

print("还原:", wp("eval-file", "/tmp/inject-test-29y.php", "revert", pid))
sys.exit(0 if ok else 1)
