"""29y：把指定文件的 BOM 去掉、换行统一成 LF，写回时显式 encoding='utf-8' / newline='\\n'。

用法：python normalize-lf-29y.py <repo_root> <相对路径> [<相对路径> ...]
不给路径就扫 git 里所有 29y 相关改动文件？不——路径一律显式给，避免误伤。
"""
import sys, io, subprocess

def read_bytes(p):
    with open(p, "rb") as f:
        return f.read()

def main():
    sys.stdout.reconfigure(encoding="utf-8")
    root = sys.argv[1]
    paths = sys.argv[2:]
    if not paths:
        print("没有给文件路径")
        return 1
    for rel in paths:
        p = f"{root}/{rel}"
        raw = read_bytes(p)
        had_bom = raw.startswith(b"\xef\xbb\xbf")
        body = raw[3:] if had_bom else raw
        text = body.decode("utf-8")
        crlf = text.count("\r\n")
        lone_cr = text.count("\r") - crlf
        # 统一成 LF
        norm = text.replace("\r\n", "\n").replace("\r", "\n")
        changed = had_bom or crlf or lone_cr
        if changed:
            with open(p, "w", encoding="utf-8", newline="\n") as f:
                f.write(norm)
        print(f"{'修复' if changed else '已是 LF'}  {rel}  BOM={had_bom} CRLF={crlf} CR={lone_cr}")
    return 0

if __name__ == "__main__":
    sys.exit(main())
