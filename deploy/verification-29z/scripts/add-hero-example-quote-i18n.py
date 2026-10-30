# -*- coding: utf-8 -*-
"""29z 复核第 4 条：首页说明卡片文案改成 "Example quote · Rigid magnetic box · 9.5×9.5×4 cm"，
把这条新文案补进 11 个语种词典。

只做行级插入，不重新序列化（词典里有重复键，JSON 往返会丢条目）。
插入点：text 段里已有的 "Rigid magnetic box · 9.5×9.5×4 cm" 那一行之后，沿用它的缩进与逗号。
"""
import io
import json
import os
import sys

DICT_DIR = r"E:\破甲工作区\packrivo-backup\deploy\i18n-merged"

KEY = "Example quote · Rigid magnetic box · 9.5×9.5×4 cm"
ANCHOR_KEY = "Rigid magnetic box · 9.5×9.5×4 cm"

TRANS = {
    "de": "Beispielangebot · Feste Magnetbox · 9.5×9.5×4 cm",
    "fr": "Devis indicatif · Boîte rigide magnétique · 9.5×9.5×4 cm",
    "es": "Presupuesto orientativo · Caja rígida magnética · 9.5×9.5×4 cm",
    "it": "Preventivo indicativo · Scatola rigida magnetica · 9.5×9.5×4 cm",
    "nl": "Voorbeeldopgave · Stijve magneetdoos · 9.5×9.5×4 cm",
    "pt": "Orçamento de exemplo · Caixa rígida magnética · 9.5×9.5×4 cm",
    "ar": "عرض سعر تقديري · صندوق صلب مغناطيسي · 9.5×9.5×4 سم",
    "ja": "参考見積 · マグネット式硬質箱 · 9.5×9.5×4 cm",
    "ko": "예시 견적 · 자석 경질 박스 · 9.5×9.5×4 cm",
    "zh-CN": "示例报价 · 磁吸硬盒 · 9.5×9.5×4 厘米",
    "zh-TW": "範例報價 · 磁吸硬盒 · 9.5×9.5×4 公分",
}


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
            report.append((lang, "已有，跳过", 0))
            continue
        idx = next((i for i, ln in enumerate(lines) if '"%s":' % ANCHOR_KEY in ln), None)
        if idx is None:
            sys.exit("%s: 找不到锚点 %r" % (lang, ANCHOR_KEY))
        anchor = lines[idx]
        indent = anchor[: len(anchor) - len(anchor.lstrip(" "))]
        has_comma = anchor.rstrip().endswith(",")
        lines.insert(idx + 1, '%s"%s": "%s"%s' % (indent, KEY, val, "," if has_comma else ""))
        out = nl.join(lines)
        json.loads(out)  # 解析一遍再落盘，失败直接退出，不写坏文件
        io.open(path, "w", encoding="utf-8", newline="").write(out)
        report.append((lang, "已插入", len(out) - len(raw)))
    for lang, action, delta in report:
        print("%-6s %-10s %+d bytes" % (lang, action, delta))
    ok = sum(1 for r in report if r[1] == "已插入")
    print("插入 %d / 11，跳过 %d" % (ok, 11 - ok))


if __name__ == "__main__":
    main()
