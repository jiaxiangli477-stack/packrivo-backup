"""29y 第 3 项：把产品卡片/价格条上的拼接型英文改成走词典。

这些文字是 sprintf 拼出来的（数字每次不同），输出缓冲按整串匹配替换不到，
所以主题里用 pkv4_i18n_t('from $%s / unit') 取模板，再 sprintf。

用法：python add-card-i18n-29y.py <i18n-merged 目录>
幂等：键已存在且值相同就跳过；键已存在但值不同会报冲突并退出 1。
"""
import json, os, shutil, sys

sys.stdout.reconfigure(encoding="utf-8")

BASE = sys.argv[1] if len(sys.argv) > 1 else r"E:\破甲工作区\packrivo-backup\deploy\i18n-merged"
LANGS = ["ar", "de", "es", "fr", "it", "ja", "ko", "nl", "pt", "zh-CN", "zh-TW"]

K1 = "from $%s / unit"
K2 = "at %s pcs · %s cm"
K3 = "at %s pcs · %s cm · full-colour print, matte"
# MOQ 500 词典里已有各语种原有译法，这里不覆盖。

T = {
    "ar": {
        K1: "ابتداءً من %s$ للوحدة",
        K2: "من %s قطعة · %s سم",
        K3: "من %s قطعة · %s سم · طباعة كاملة الألوان، مطفي",
    },
    "de": {
        K1: "ab $%s / Stück",
        K2: "ab %s Stück · %s cm",
        K3: "ab %s Stück · %s cm · Vollfarbdruck, matt",
    },
    "es": {
        K1: "desde $%s / unidad",
        K2: "desde %s uds. · %s cm",
        K3: "desde %s uds. · %s cm · impresión a todo color, mate",
    },
    "fr": {
        K1: "à partir de $%s / unité",
        K2: "à partir de %s pièces · %s cm",
        K3: "à partir de %s pièces · %s cm · impression quadri, mat",
    },
    "it": {
        K1: "da $%s / unità",
        K2: "da %s pz · %s cm",
        K3: "da %s pz · %s cm · stampa a colori pieni, opaco",
    },
    "ja": {
        K1: "$%s / 個〜",
        K2: "%s個〜 · %s cm",
        K3: "%s個〜 · %s cm · フルカラー印刷・マット",
    },
    "ko": {
        K1: "개당 $%s부터",
        K2: "%s개부터 · %s cm",
        K3: "%s개부터 · %s cm · 풀컬러 인쇄, 무광",
    },
    "nl": {
        K1: "vanaf $%s / stuk",
        K2: "vanaf %s st. · %s cm",
        K3: "vanaf %s st. · %s cm · full colour bedrukt, mat",
    },
    "pt": {
        K1: "a partir de $%s / unidade",
        K2: "a partir de %s un. · %s cm",
        K3: "a partir de %s un. · %s cm · impressão a cores, mate",
    },
    "zh-CN": {
        K1: "每件 $%s 起",
        K2: "%s 件起 · %s cm",
        K3: "%s 件起 · %s cm · 全彩印刷，哑膜",
    },
    "zh-TW": {
        K1: "每件 $%s 起",
        K2: "%s 件起 · %s cm",
        K3: "%s 件起 · %s cm · 全彩印刷，霧面",
    },
}

fails = []
added = skipped = 0
for lang in LANGS:
    path = os.path.join(BASE, f"{lang}-dict.json")
    if not os.path.exists(path):
        fails.append(f"{lang} 字典不存在")
        continue
    d = json.load(open(path, encoding="utf-8"))
    d.setdefault("block", {})
    bak = path + ".pre29y-card"
    if not os.path.exists(bak):
        shutil.copy2(path, bak)
    for key, val in T[lang].items():
        cur = d["block"].get(key)
        if cur is None:
            d["block"][key] = val
            added += 1
        elif cur != val:
            fails.append(f"{lang} 键冲突 {key!r}: 已有 {cur!r} 想写 {val!r}")
        else:
            skipped += 1
    with open(path, "w", encoding="utf-8") as fh:
        json.dump(d, fh, ensure_ascii=False, indent=1, sort_keys=True)
        fh.write("\n")
    print(f"  {lang:6} block 键数 {len(d['block'])}")

print(f"新增 {added} 条，已存在 {skipped} 条，冲突 {len(fails)} 条")
if fails:
    for f in fails:
        print("FAIL", f)
    sys.exit(1)
print("PASS 11 语种的卡片/价格条模板键齐全")
