"""29y：给门禁做字节级体检——本轮所有改动文件必须是 UTF-8 无 BOM、纯 LF。

判据（任一不满足即 FAIL）：
  1. 文件可按 UTF-8 严格解码
  2. 首三字节不是 EF BB BF
  3. 不含 CRLF，也不含孤立 CR
另外对 .json / .csv 额外做结构校验：JSON 必须能解析，CSV 表头必须能读出。

跑法：python check-commit-utf8-29y.py <repo_root>
输出 PASS/FAIL 逐条，任一 FAIL 退出码 1（退出码只做辅助，证据看逐条 PASS/FAIL）。
"""
import csv, io, json, os, subprocess, sys

SKIP_SUFFIX = (".png", ".jpg", ".jpeg", ".webp", ".gif", ".zip", ".gz", ".sql", ".ico", ".woff", ".woff2",
               ".ttf", ".pyc", ".so", ".pdf")
# 备份文件不进提交，也就没必要过编码门禁
SKIP_NAME_MARKERS = (".pre29y-", ".29x-bak", ".bak-29y", ".pre29y-bak")
SKIP_DIR_PARTS = ("__pycache__", ".git")


def changed_files(repo):
    out = subprocess.run(["git", "diff", "--name-only", "HEAD"], cwd=repo, capture_output=True)
    if out.returncode != 0:
        raise SystemExit("git diff 失败：" + out.stderr.decode("utf-8", "replace"))
    staged = subprocess.run(["git", "diff", "--cached", "--name-only"], cwd=repo, capture_output=True)
    untracked = subprocess.run(["git", "ls-files", "--others", "--exclude-standard"], cwd=repo,
                               capture_output=True)
    names = set()
    for blob in (out.stdout, staged.stdout, untracked.stdout):
        for line in blob.decode("utf-8", "replace").splitlines():
            if line.strip():
                names.add(line.strip())
    return sorted(names)


def main():
    sys.stdout.reconfigure(encoding="utf-8")
    repo = sys.argv[1] if len(sys.argv) > 1 else "."
    files = changed_files(repo)
    ok = fail = skip = 0
    for rel in files:
        if rel.lower().endswith(SKIP_SUFFIX):
            print(f"SKIP {rel}（二进制/数据导出，不做文本编码校验）")
            skip += 1
            continue
        if any(m in os.path.basename(rel) for m in SKIP_NAME_MARKERS):
            print(f"SKIP {rel}（本轮备份文件，不进提交）")
            skip += 1
            continue
        if any(part in rel.split("/") for part in SKIP_DIR_PARTS):
            print(f"SKIP {rel}（缓存/工具目录）")
            skip += 1
            continue
        p = os.path.join(repo, rel)
        if not os.path.exists(p):
            print(f"SKIP {rel}（已删除）")
            skip += 1
            continue
        raw = open(p, "rb").read()
        problems = []
        if raw.startswith(b"\xef\xbb\xbf"):
            problems.append("带 BOM")
        try:
            text = raw.decode("utf-8")
        except UnicodeDecodeError as e:
            problems.append(f"不是合法 UTF-8（{e}）")
            text = None
        crlf = raw.count(b"\r\n")
        if crlf:
            problems.append(f"含 CRLF {crlf} 处")
        if text is not None:
            lone = text.count("\r") - crlf
            if lone:
                problems.append(f"含孤立 CR {lone} 处")
        if text is not None and rel.endswith(".json"):
            try:
                json.loads(text)
            except Exception as e:
                problems.append(f"JSON 解析失败（{e}）")
        if text is not None and rel.endswith(".csv"):
            try:
                rows = list(csv.reader(io.StringIO(text)))
                if not rows or not rows[0]:
                    problems.append("CSV 读不出表头")
            except Exception as e:
                problems.append(f"CSV 解析失败（{e}）")
        if problems:
            fail += 1
            print(f"FAIL {rel} —— {'；'.join(problems)}")
        else:
            ok += 1
            print(f"PASS {rel} —— UTF-8 无 BOM，纯 LF")

    # 词典专项：不许被压成单行，也不许整文件重写。
    # 起因：i909/dict-add-29y-v2.py 用 separators=(',',':') 落盘，把 it-dict.json 写成 1 行 1.5MB，
    # git diff 变成 +1/-7742，评审看不到真实改动。
    d_fail, d_ok = 0, 0
    for rel in files:
        if not (rel.startswith("deploy/i18n-merged/") and rel.endswith("-dict.json")):
            continue
        p = os.path.join(repo, rel)
        if not os.path.exists(p):
            continue
        head = subprocess.run(["git", "show", f"HEAD:{rel}"], cwd=repo, capture_output=True)
        head_lines = head.stdout.count(b"\n")
        now_lines = open(p, "rb").read().count(b"\n")
        stat = subprocess.run(["git", "diff", "--numstat", "HEAD", "--", rel], cwd=repo,
                              capture_output=True).stdout.decode("utf-8", "replace").split()
        add = int(stat[0]) if stat and stat[0].isdigit() else 0
        dele = int(stat[1]) if len(stat) > 1 and stat[1].isdigit() else 0
        bad = []
        if head_lines and now_lines < head_lines:
            bad.append(f"行数 {now_lines} 少于 HEAD 的 {head_lines}（被压成单行或重排）")
        if dele > add * 2 + 50:
            bad.append(f"删除 {dele} 行 vs 新增 {add} 行（整文件重写，不是真实改动）")
        if bad:
            d_fail += 1
            print(f"FAIL {rel} —— {'；'.join(bad)}")
        else:
            d_ok += 1
            print(f"PASS {rel} —— 行数 {now_lines} ≥ HEAD {head_lines}，改动 +{add}/-{dele} 属真实增删")
    print(f"词典专项 PASS {d_ok} / FAIL {d_fail}")
    fail += d_fail

    print(f"\n合计 PASS {ok} / FAIL {fail} / SKIP {skip}（逐文件 {len(files)} 个待提交文件；"
          f"词典专项另有 {d_ok + d_fail} 条判据，FAIL 已并进上面的 FAIL 计数）")
    return 0 if fail == 0 else 1


if __name__ == "__main__":
    sys.exit(main())
