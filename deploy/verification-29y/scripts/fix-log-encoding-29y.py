"""29y：把日志类证据文件从 UTF-16LE / CRLF 统一成 UTF-8 + LF。

用 PowerShell 的 `>` 重定向写日志会落成 UTF-16LE（FF FE 开头），这类文件既不能当 UTF-8 读，
也会被门禁判 FAIL；这里按 BOM 判断真实编码再转，不动任何文本内容。

跑法：python fix-log-encoding-29y.py <file> [<file> ...]
"""
import sys

for p in sys.argv[1:]:
    raw = open(p, "rb").read()
    src = "utf-8"
    body = raw
    if raw.startswith(b"\xff\xfe"):
        src, body = "utf-16-le", raw[2:]
    elif raw.startswith(b"\xfe\xff"):
        src, body = "utf-16-be", raw[2:]
    elif raw.startswith(b"\xef\xbb\xbf"):
        body = raw[3:]
    text = body.decode(src)
    crlf = text.count("\r\n")
    text = text.replace("\r\n", "\n").replace("\r", "\n")
    with open(p, "w", encoding="utf-8", newline="\n") as f:
        f.write(text)
    now = open(p, "rb").read()
    print(f"{p.split('/')[-1].split(chr(92))[-1]}: {src} → utf-8，CRLF {crlf}→{now.count(b'CRLF'[:0] + b'\r\n')}，"
          f"BOM={now.startswith(b'\xef\xbb\xbf')}，{len(text.splitlines())} 行")
