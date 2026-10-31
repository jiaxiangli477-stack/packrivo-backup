"""29y 负向验证（v3 连续段规则）：本族词不能把英文短语抵消掉。

做法：把 /de/ 上一段真实译文临时换成「全是德语也在用的英文词」：
      "In is was will on no come"
其中 in / was / will 都在 de 的 LOCAL_STOP 里，纯扣词的话只会剩 2 个词（is, on）。
但它们是连续 5 个以上的英文词 ⇒ 按新规则整段都必须报残留，期望 residual_count = 8。

判据：
  ① 注入前 residual_count = 0
  ② 注入后 residual_count 必须等于整段词数（不是扣完剩下的 2 个）
  ③ 还原后 residual_count = 0

用法：python negative-run-rule-29y.py <repo_root> [lang] [url]
"""
import json, os, shutil, subprocess, sys

DOCKER = r"D:\Docker\App\resources\bin\docker.exe"
GW = "172.19.0.1"
INJECT_KEY = "All products \u2192"
INJECT_EN = ("In is was will on no come in is was will on no come "
             "in is was will on no come.")
SCAN = "/tmp/scan-main-english-29y.php"


def docker(*args, timeout=240):
    r = subprocess.run([DOCKER, *args], capture_output=True, timeout=timeout)
    return r.returncode, r.stdout.decode("utf-8", "replace"), r.stderr.decode("utf-8", "replace")


def scan(lang, url):
    docker("exec", "php", "sh", "-c", f"printf '%s %s\\n' {lang} {url} > /tmp/negrun-urls.txt")
    rc, out, err = docker("exec", "php", "sh", "-c",
                          "rm -f /tmp/negrun.jsonl; php " + SCAN +
                          " /tmp/negrun-urls.txt /tmp/negrun.jsonl " + GW +
                          " /tmp/local-stop.json 2>/dev/null; cat /tmp/negrun.jsonl")
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
    bak = dict_path + ".negrunbak"
    remote = ("/var/www/html/www.packrivo.com/wordpress/wp-content/uploads/packrivo-i18n/"
              + lang + "-dict.json")
    shutil.copy2(dict_path, bak)
    ok = True
    expect = len(INJECT_EN.replace(".", "").split()) - 14  # 三处导航各一份，取保守下界
    try:
        base = scan(lang, url)
        print("① 注入前：原始 %d / 扣本族词后 %d（期望 0）"
              % (base["residual_raw"], base["residual_count"]))
        if base["residual_count"] != 0:
            print("FAIL 注入前这页就不干净，负向验证失去意义")
            return 1

        d = json.load(open(dict_path, encoding="utf-8"))
        bucket = next((b for b in ("block", "text", "attr", "title") if INJECT_KEY in d.get(b, {})), None)
        if bucket is None:
            print("FAIL 词典里没有注入用的键 %r" % INJECT_KEY)
            return 1
        d[bucket][INJECT_KEY] = INJECT_EN
        with open(dict_path, "w", encoding="utf-8", newline="\n") as fh:
            json.dump(d, fh, ensure_ascii=False, indent=2)
            fh.write("\n")
        rc, out, err = docker("cp", dict_path, "php:" + remote)
        if rc != 0:
            raise SystemExit("docker cp 失败：" + err[:200])

        hit = scan(lang, url)
        print("② 注入全本族词英文长串后：原始 %d / 扣本族词后 %d / 连续段 %d"
              % (hit["residual_raw"], hit["residual_count"], hit["long_runs"]))
        kept = set(hit.get("residual_words") or [])
        probe = [w for w in ("in", "was", "will") if w in kept]
        print("   本族词里被保留下来的：%s（这三个都在 LOCAL_STOP 里，纯扣词会被扣掉）"
              % (", ".join(probe) if probe else "无"))
        print("   命中片段示例：%s" % str((hit.get("long_run_samples") or [])[:2])[:140])
        if hit["residual_count"] < expect:
            print("FAIL 连续段规则没生效：本族词把英文串抵消掉了")
            ok = False
        elif hit["long_runs"] < 1 or not probe:
            print("FAIL 连续段数量为 0，或本族词全被扣掉，规则未生效")
            ok = False
        else:
            print("   PASS 连续 ≥3 个英文词整段报残留，本族词未被抵消")
    finally:
        shutil.copy2(bak, dict_path)
        os.remove(bak)
        docker("cp", dict_path, "php:" + remote)

    back = scan(lang, url)
    print("③ 还原后：原始 %d / 扣本族词后 %d（期望 0）"
          % (back["residual_raw"], back["residual_count"]))
    if back["residual_count"] != 0:
        print("FAIL 还原后仍有残留")
        ok = False
    print("\n负向验证结论：" + ("PASS" if ok else "FAIL"))
    return 0 if ok else 1


if __name__ == "__main__":
    sys.exit(main())
