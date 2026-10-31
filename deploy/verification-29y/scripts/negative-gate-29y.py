"""29y 负向验证：给编码门禁喂两个坏样本，它必须都报 FAIL。

  样本 A：往一个受监控的文本文件头部塞 BOM（EF BB BF）→ 期望 "带 BOM"
  样本 B：把同一个文件改成 CRLF → 期望 "含 CRLF"
两轮都跑完再还原，最后再跑一次门禁确认回到 PASS。

用法：python negative-gate-29y.py <repo_root>
"""
import os, json, shutil, subprocess, sys

TARGET = os.path.join("deploy", "verification-29y", "evidence", "29y-baseline.txt")
DICT = os.path.join("deploy", "i18n-merged", "it-dict.json")


def run_gate(repo):
    r = subprocess.run([sys.executable, os.path.join(repo, "deploy", "verification-29y", "scripts",
                                                     "check-commit-utf8-29y.py"), repo],
                       capture_output=True)
    return r.returncode, r.stdout.decode("utf-8", "replace")


def main():
    sys.stdout.reconfigure(encoding="utf-8")
    repo = sys.argv[1]
    p = os.path.join(repo, TARGET)
    bak = p + ".gatebak"
    shutil.copy2(p, bak)
    ok = True
    try:
        clean_rc, clean_out = run_gate(repo)
        line = [l for l in clean_out.splitlines() if l.startswith("PASS " + TARGET.replace("\\", "/"))]
        print("① 原始文件门禁：" + (line[0] if line else "（没看到该文件的 PASS 行）"))

        raw = open(p, "rb").read()
        open(p, "wb").write(b"\xef\xbb\xbf" + raw)
        rc, out = run_gate(repo)
        hit = [l for l in out.splitlines() if l.startswith("FAIL ") and TARGET.replace("\\", "/") in l]
        print("② 塞 BOM 后：" + (hit[0] if hit else "没报 FAIL"))
        if not hit or "带 BOM" not in hit[0] or rc == 0:
            print("FAIL 门禁没抓住 BOM")
            ok = False
        else:
            print("   PASS 门禁抓住 BOM")

        text = raw.decode("utf-8").replace("\r\n", "\n").replace("\n", "\r\n")
        open(p, "wb").write(text.encode("utf-8"))
        rc, out = run_gate(repo)
        hit = [l for l in out.splitlines() if l.startswith("FAIL ") and TARGET.replace("\\", "/") in l]
        print("③ 改成 CRLF 后：" + (hit[0] if hit else "没报 FAIL"))
        if not hit or "含 CRLF" not in hit[0] or rc == 0:
            print("FAIL 门禁没抓住 CRLF")
            ok = False
        else:
            print("   PASS 门禁抓住 CRLF")
    finally:
        shutil.copy2(bak, p)
        os.remove(bak)

    # ③' 词典被压成单行（i909 那次的形态）：行数少于 HEAD 必须 FAIL
    dp = os.path.join(repo, DICT)
    dbak = dp + ".gatebak"
    shutil.copy2(dp, dbak)
    try:
        raw = open(dp, "rb").read()
        one = json.dumps(json.loads(raw.decode("utf-8")), ensure_ascii=False,
                         separators=(",", ":")).encode("utf-8")
        open(dp, "wb").write(one)
        rc, out = run_gate(repo)
        hit = [l for l in out.splitlines() if l.startswith("FAIL ") and DICT.replace("\\", "/") in l]
        print("⑤ 词典压成单行后：" + (hit[0] if hit else "没报 FAIL"))
        if not hit or "行数" not in hit[0] or rc == 0:
            print("FAIL 门禁没抓住单行词典")
            ok = False
        else:
            print("   PASS 门禁抓住单行词典")
    finally:
        shutil.copy2(dbak, dp)
        os.remove(dbak)

    rc, out = run_gate(repo)
    tail = [l for l in out.splitlines() if l.startswith("合计")]
    print("④ 还原后门禁：" + (tail[0] if tail else out.strip()[-120:]))
    if rc != 0:
        print("FAIL 还原后门禁仍不通过")
        ok = False
    print("\n负向验证结论：" + ("PASS 两条新门禁都能抓到坏样本" if ok else "FAIL"))
    return 0 if ok else 1


if __name__ == "__main__":
    sys.exit(main())
