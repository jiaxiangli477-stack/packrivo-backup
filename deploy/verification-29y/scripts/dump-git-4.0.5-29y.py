"""把 git grep 4.0.5 的两组输出原样落盘（用 Python 写，不用 PowerShell）。

  deploy/verification-29y/evidence/git-grep-4.0.5.txt
"""
import os, subprocess, sys

REPO = r"E:\破甲工作区\packrivo-backup"
OUT = os.path.join(REPO, "deploy", "verification-29y", "evidence", "git-grep-4.0.5.txt")

CMDS = [
    ('git log --all -S"4.0.5" --oneline', ["git", "log", "--all", "-S4.0.5", "--oneline"]),
    ('git grep -n "4.0.5"   （正则，点号是通配）', ["git", "grep", "-n", "4.0.5"]),
    ('git grep -n "4\\.0\\.5" （点号转义，按字面量）', ["git", "grep", "-n", r"4\.0\.5"]),
    ('git log --all -S"PKV4_VERSION\', \'4.0.5" --oneline',
     ["git", "log", "--all", "-SPKV4_VERSION', '4.0.5", "--oneline"]),
]


def main():
    sys.stdout.reconfigure(encoding="utf-8")
    chunks = []
    for label, cmd in CMDS:
        r = subprocess.run(cmd, cwd=REPO, capture_output=True)
        out = r.stdout.decode("utf-8", "replace")
        err = r.stderr.decode("utf-8", "replace")
        lines = [l for l in out.splitlines() if l]
        body = "\n".join(lines) if lines else "（空输出）"
        chunk = (f"$ {label}\n{body}\n（退出码 {r.returncode}，命中 {len(lines)} 行）\n"
                 + (f"[stderr] {err}\n" if err.strip() else ""))
        chunks.append(chunk)
        print(chunk)
    with open(OUT, "w", encoding="utf-8", newline="\n") as fh:
        fh.write("\n".join(chunks))
    print(f"已写入 {OUT}（{os.path.getsize(OUT)} B）")


if __name__ == "__main__":
    main()
