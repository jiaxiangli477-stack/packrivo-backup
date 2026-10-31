"""把本轮五处补齐的关键命令输出落成一份证据（用 Python 跑、Python 写，不经 PowerShell）。

  deploy/verification-29y/evidence/29y-five-items.txt
"""
import os, subprocess, sys

REPO = r"E:\破甲工作区\packrivo-backup"
PY = sys.executable
OUT = os.path.join(REPO, "deploy", "verification-29y", "evidence", "29y-five-items.txt")

STEPS = [
    ("① 只用仓库内文件从 HEAD + delta + packets 重建 11 个词典，与工作树逐字节比对",
     [PY, "deploy/scripts-29y/verify-dict-rebuild-29y.py"]),
    ("② 原有译文被改清单", [PY, "deploy/scripts-29y/report-dict-mods-29y.py"]),
    ("③ 残留英文按语种汇总（当前扫描进度）",
     [PY, "deploy/scripts-29y/by-lang-residual-29y.py",
      r"E:\破甲工作区\.tmp\29y\scan-v3-partial.jsonl"]),
    ("④ 门禁（编码 / 换行 / 词典专项）",
     [PY, "deploy/verification-29y/scripts/check-commit-utf8-29y.py", "."]),
]


def main():
    sys.stdout.reconfigure(encoding="utf-8")
    chunks = []
    for title, cmd in STEPS:
        r = subprocess.run(cmd, cwd=REPO, capture_output=True)
        out = r.stdout.decode("utf-8", "replace")
        err = r.stderr.decode("utf-8", "replace")
        keep = [l for l in out.splitlines()
                if ("PASS" in l or "FAIL" in l or "汇总" in l or "结论" in l or "合计" in l
                    or "语种" in l or l.startswith("  ") and "达标" not in l)]
        if title.startswith("④"):
            keep = [l for l in out.splitlines() if l.startswith("FAIL") or "词典专项" in l or "合计" in l]
        if title.startswith("③"):
            keep = [l for l in out.splitlines() if l.strip()]
        body = "\n".join(keep) if keep else "（无输出）"
        chunk = f"==== {title} ====\n$ {' '.join(cmd)}\n{body}\n（退出码 {r.returncode}）\n"
        if err.strip():
            chunk += f"[stderr] {err.strip()[:400]}\n"
        chunks.append(chunk)
        print(chunk)
    with open(OUT, "w", encoding="utf-8", newline="\n") as fh:
        fh.write("\n".join(chunks))
    print(f"已写入 {OUT}（{os.path.getsize(OUT)} B）")


if __name__ == "__main__":
    main()
