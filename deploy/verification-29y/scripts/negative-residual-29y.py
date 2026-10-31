"""29y 负向验证：往演练站一页里塞一句真英文，残留英文检查必须报 FAIL。

做法（全部走真实链路，不伪造输出）：
  1. 备份当前词典（deploy/i18n-merged/<lang>-dict.json → .negbak）
  2. 把词典里某个真实出现在该页的英文字串临时改成英文长句（页面渲染后就出现英文）
  3. docker cp 进容器，扫描该页 —— 期望 residual_count > 5（即检查必须判 FAIL）
  4. 还原词典，docker cp 回去，再扫一次 —— 期望 residual_count == 0

用法：python negative-residual-29y.py <repo_root> [lang] [url]
"""
import json, os, shutil, subprocess, sys

DOCKER = r"D:\Docker\App\resources\bin\docker.exe"
GW = "172.19.0.1"
INJECT_KEY = "All products \u2192"
INJECT_EN = ("This page has been deliberately filled with plain English sentences so that the "
             "residual English detector must report a failure here instead of passing quietly.")
SCAN = "/tmp/scan-main-english-29y.php"


def docker(*args, timeout=180):
    r = subprocess.run([DOCKER, *args], capture_output=True, timeout=timeout)
    return r.returncode, r.stdout.decode("utf-8", "replace"), r.stderr.decode("utf-8", "replace")


def scan(lang, url):
    docker("exec", "php", "sh", "-c", f"printf '%s %s\\n' {lang} {url} > /tmp/neg-urls.txt")
    rc, out, err = docker("exec", "php", "sh", "-c",
                          "rm -f /tmp/neg.jsonl; php " + SCAN +
                          " /tmp/neg-urls.txt /tmp/neg.jsonl " + GW + " 2>/dev/null; cat /tmp/neg.jsonl")
    lines = [l for l in out.splitlines() if l.strip().startswith("{")]
    if not lines:
        raise SystemExit("扫描没有输出，rc=%s err=%s" % (rc, err[:300]))
    return json.loads(lines[-1])


def main():
    sys.stdout.reconfigure(encoding="utf-8")
    repo = sys.argv[1]
    lang = sys.argv[2] if len(sys.argv) > 2 else "de"
    url = sys.argv[3] if len(sys.argv) > 3 else "https://www.packrivo.com/de/"
    dict_path = os.path.join(repo, "deploy", "i18n-merged", lang + "-dict.json")
    bak = dict_path + ".negbak"
    remote = ("/var/www/html/www.packrivo.com/wordpress/wp-content/uploads/packrivo-i18n/"
              + lang + "-dict.json")
    shutil.copy2(dict_path, bak)
    ok_all = True
    try:
        base = scan(lang, url)
        print("① 注入前该页残留 %d 词（期望 ≤5）" % base["residual_count"])
        if base["residual_count"] > 5:
            print("FAIL 注入前这一页就已经超阈值，负向验证失去意义")
            return 1

        d = json.load(open(dict_path, encoding="utf-8"))
        bucket = next((b for b in ("block", "text", "attr", "title") if INJECT_KEY in d.get(b, {})), None)
        if bucket is None:
            print("FAIL 词典里没有注入用的键 %r" % INJECT_KEY)
            return 1
        old = d[bucket][INJECT_KEY]
        d[bucket][INJECT_KEY] = INJECT_EN
        with open(dict_path, "w", encoding="utf-8", newline="\n") as fh:
            json.dump(d, fh, ensure_ascii=False, indent=2)
            fh.write("\n")
        print("   注入到 %s 桶：%r  %r → 英文长句" % (bucket, INJECT_KEY, old))
        rc, out, err = docker("cp", dict_path, "php:" + remote)
        if rc != 0:
            raise SystemExit("docker cp 失败：" + err[:300])

        hit = scan(lang, url)
        print("② 注入英文长句后该页残留 %d 词（期望 >5，即检查必须报 FAIL）" % hit["residual_count"])
        if hit["residual_count"] <= 5:
            print("FAIL 塞了真英文却仍判通过——残留英文检查是坏的")
            ok_all = False
        else:
            print("   命中片段示例：" + str(hit.get("english_segments", [])[:1])[:160])
    finally:
        shutil.copy2(bak, dict_path)
        os.remove(bak)
        docker("cp", dict_path, "php:" + remote)

    back = scan(lang, url)
    print("③ 还原词典后该页残留 %d 词（期望 0）" % back["residual_count"])
    if back["residual_count"] != 0:
        print("FAIL 还原后仍有残留，说明注入没被完全撤掉")
        ok_all = False
    print("\n负向验证结论：" + ("PASS 真英文会被抓住，且可完全还原" if ok_all else "FAIL"))
    return 0 if ok_all else 1


if __name__ == "__main__":
    sys.exit(main())
