"""把指定 JSON 文件统一成 UTF-8（无 BOM）+ LF，内容不变（缩进重排为 2 空格）。

用法：python normalize-json-lf-29y.py <文件> [文件...]
"""
import json, sys

BOM = bytes([0xEF, 0xBB, 0xBF])
CRLF = bytes([13, 10])
LF = bytes([10])


def main():
    sys.stdout.reconfigure(encoding="utf-8")
    for p in sys.argv[1:]:
        raw = open(p, "rb").read()
        had_bom = raw.startswith(BOM)
        if had_bom:
            raw = raw[len(BOM):]
        data = json.loads(raw.decode("utf-8"))
        out = (json.dumps(data, ensure_ascii=False, indent=2) + "\n").encode("utf-8")
        open(p, "wb").write(out)
        print("  %s: 原 %d B（BOM=%s，CRLF %d 处）→ %d B（BOM=否，CRLF %d 处，行 %d）"
              % (p, len(raw), had_bom, raw.count(CRLF), len(out), out.count(CRLF), out.count(LF)))


if __name__ == "__main__":
    main()
