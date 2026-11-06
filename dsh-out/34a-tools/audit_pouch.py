import csv, io, re
CSV = r"E:\破甲工作区\packrivo-backup-34a\dsh-out\34a-i18n.csv"
rows = list(csv.DictReader(io.open(CSV, encoding="utf-8")))
print("rows", len(rows))
for lg, word in (("zh-CN", "收纳"), ("zh-TW", "收納")):
    sel = [r for r in rows if r["lang"] == lg and word in r["value"]]
    print("=" * 30, lg, word, len(sel))
    for r in sel:
        print("--[%s] en: %s" % (r["bucket"], r["en"]))
        print("        zh: %s" % r["value"])
print("=" * 30, "pouch in en vs zh-CN value")
for r in rows:
    if r["lang"] == "zh-CN" and "pouch" in r["en"].lower():
        print("--[%s] en: %s" % (r["bucket"], r["en"]))
        print("        zh: %s" % r["value"])
