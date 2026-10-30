# -*- coding: utf-8 -*-
"""29z §3c：把导航新文案 "Price calculator" 补进 11 个语种词典。

只做行级插入，不重新序列化（词典里有重复键，JSON 往返会丢条目）。
插入点：text 段的 "Instant quote" 行之后，与相邻条目同样的缩进和逗号。
"""
import io
import json
import os
import sys

DICT_DIR = r"E:\破甲工作区\packrivo-backup\deploy\i18n-merged"

TRANS = {
    "de": "Preisrechner",
    "fr": "Calculateur de prix",
    "es": "Calculadora de precios",
    "it": "Calcolatore di prezzi",
    "nl": "Prijsberekenaar",
    "pt": "Calculadora de preços",
    "ar": "حاسبة الأسعار",
    "ja": "価格計算ツール",
    "ko": "가격 계산기",
    "zh-CN": "价格计算器",
    "zh-TW": "價格計算器",
}
KEY = "Price calculator"


def main():
    missing = [l for l in TRANS if not os.path.exists(os.path.join(DICT_DIR, l + "-dict.json"))]
    if missing:
        sys.exit("词典缺失: " + ", ".join(missing))
    report = []
    for lang, val in TRANS.items():
        path = os.path.join(DICT_DIR, lang + "-dict.json")
        raw = io.open(path, "r", encoding="utf-8", newline="").read()
        nl = "\r\n" if "\r\n" in raw else "\n"
        lines = raw.split(nl)
        if any(('"%s":' % KEY) in ln for ln in lines):
            report.append((lang, path, "已有，跳过", 0))
            continue
        idx = next((i for i, ln in enumerate(lines) if '"Instant quote":' in ln), None)
        if idx is None:
            sys.exit("%s: 找不到 Instant quote 锚点" % lang)
        anchor = lines[idx]
        indent = anchor[: len(anchor) - len(anchor.lstrip(" "))]
        has_comma = anchor.rstrip().endswith(",")
        new_line = '%s"%s": "%s"%s' % (indent, KEY, val, "," if has_comma else "")
        lines.insert(idx + 1, new_line)
        out = nl.join(lines)
        # 语法自检：解析一遍再落盘（解析失败直接退出，不写坏文件）
        json.loads(out)
        io.open(path, "w", encoding="utf-8", newline="").write(out)
        report.append((lang, path, "已插入", len(out) - len(raw)))
    for lang, path, action, delta in report:
        print("%-6s %-9s %+d bytes" % (lang, action, delta))
    ok = sum(1 for r in report if r[2] == "已插入")
    print("插入 %d / 11，跳过 %d" % (ok, 11 - ok))


if __name__ == "__main__":
    main()
