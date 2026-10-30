# -*- coding: utf-8 -*-
"""29z 提交前门禁（负责人 2026-10-30 复核第二.5 条）。

跑法：先 git add，再
    python deploy/verification-29z/scripts/check-commit-utf8-29z.py
逐条打印 PASS/FAIL，任一条 FAIL 就退出码 1。

检查项：
  G1 本次提交里所有 .md/.php/.json/.csv/.sh/.txt/.html 文件都能按 UTF-8 严格解码，且不含 U+FFFD
  G2 RUNBOOK 行数、每个 ``` 代码块的行数，相对 c62bc88 只增不减（按子序列匹配，允许中间插新块）
  G3 RUNBOOK 第 5.2 步的 theme install 单独占一行，且哈希是 7D792AD9
  G4 RUNBOOK 附录 D.1 表里锁定的 sha256 与磁盘上的真实文件一致
"""
import io
import os
import re
import subprocess
import sys

REPO = r"E:\破甲工作区\packrivo-backup"
BASE_REV = "c62bc88"
RUNBOOK = "deploy/RUNBOOK-launch.md"
EXTS = (".md", ".php", ".json", ".csv", ".sh", ".txt", ".html")

PASS = FAIL = 0


def ck(name, ok, detail=""):
    global PASS, FAIL
    if ok:
        PASS += 1
        print("  PASS  %s%s" % (name, ("（%s）" % detail) if detail else ""))
    else:
        FAIL += 1
        print("  FAIL  %s%s" % (name, ("（%s）" % detail) if detail else ""))


def git(*args):
    r = subprocess.run(["git", "-C", REPO] + list(args), capture_output=True)
    return r.stdout


def staged_files():
    raw = git("diff", "--cached", "--name-only", "--diff-filter=ACMR").decode("utf-8", "replace")
    return [f.strip() for f in raw.split("\n") if f.strip()]


def code_blocks(text):
    blocks, cur, inside = [], 0, False
    for line in text.split("\n"):
        if line.startswith("```"):
            if inside:
                blocks.append(cur)
                cur, inside = 0, False
            else:
                inside = True
            continue
        if inside:
            cur += 1
    return blocks


def main():
    files = staged_files()
    print("== 本次提交文件数：%d ==" % len(files))

    # G1
    targets = [f for f in files if f.lower().endswith(EXTS)]
    bad_utf8, bad_fffd = [], []
    for f in targets:
        p = os.path.join(REPO, f.replace("/", os.sep))
        if not os.path.exists(p):
            continue
        data = io.open(p, "rb").read()
        try:
            text = data.decode("utf-8")
        except UnicodeDecodeError as e:
            bad_utf8.append("%s (%s)" % (f, e))
            continue
        if "\ufffd" in text:
            bad_fffd.append("%s（%d 处）" % (f, text.count("\ufffd")))
    ck("G1a 文本文件严格 UTF-8 解码（%d 个）" % len(targets), not bad_utf8, "; ".join(bad_utf8[:3]))
    ck("G1b 文本文件无 U+FFFD", not bad_fffd, "; ".join(bad_fffd[:3]))

    # G2 / G3
    base = git("show", "%s:%s" % (BASE_REV, RUNBOOK)).decode("utf-8")
    cur_path = os.path.join(REPO, RUNBOOK.replace("/", os.sep))
    cur = io.open(cur_path, encoding="utf-8", newline="").read()
    ck("G2a RUNBOOK 行数不减少", cur.count("\n") >= base.count("\n"),
       "%d -> %d" % (base.count("\n"), cur.count("\n")))
    bb, cb = code_blocks(base), code_blocks(cur)
    j, missing = 0, []
    for i, want in enumerate(bb):
        while j < len(cb) and cb[j] != want:
            j += 1
        if j >= len(cb):
            missing.append("第 %d 块（%d 行）" % (i + 1, want))
            break
        j += 1
    ck("G2b 原有代码块都在且行数不减（%d 块 -> %d 块）" % (len(bb), len(cb)), not missing, "; ".join(missing))

    lines = cur.split("\n")
    inst = [i for i, l in enumerate(lines) if "theme install" in l]
    ok_inst = bool(inst) and all(l.strip().startswith("$WPCLI theme install") for l in (lines[i] for i in inst))
    ck("G3a theme install 独占一行（%d 处）" % len(inst), ok_inst)
    near = [lines[i - 1].strip() for i in inst if i > 0]
    ck("G3b 上一行哈希是 7d792ad9（大小写不敏感）", all("7d792ad9" in l.lower() for l in near), " / ".join(l[:70] for l in near[:2]))

    print("== 5.2 步原文 ==")
    start = next((i for i, l in enumerate(lines) if l.strip().startswith("# 5.2")), None)
    if start is None:
        print("  找不到 5.2 标记")
    else:
        for i in range(start, min(start + 7, len(lines))):
            print("  %4d | %s" % (i + 1, lines[i]))

    # G4
    app = cur.split("## 附录 D")[-1] if "## 附录 D" in cur else ""
    pairs = re.findall(r"\|\s*`([^`]+)`\s*\|\s*([\d,]+)\s*\|\s*`([0-9a-f]{64})`\s*\|", app)
    bad_hash = []
    for path, size, want in pairs:
        p = os.path.join(REPO, path.replace("/", os.sep))
        if not os.path.exists(p):
            bad_hash.append("%s 不存在" % path)
            continue
        import hashlib
        data = io.open(p, "rb").read()
        got = hashlib.sha256(data).hexdigest()
        if got != want:
            bad_hash.append("%s 锁的是 %s，实际 %s" % (path, want[:12], got[:12]))
    ck("G4 附录 D.1 锁定的文件哈希与磁盘一致（%d 项）" % len(pairs), bool(pairs) and not bad_hash,
       "; ".join(bad_hash[:3]) if bad_hash else "%d 项全对" % len(pairs))

    print("== 汇总：PASS=%d FAIL=%d ==" % (PASS, FAIL))
    sys.exit(1 if FAIL else 0)


if __name__ == "__main__":
    main()
