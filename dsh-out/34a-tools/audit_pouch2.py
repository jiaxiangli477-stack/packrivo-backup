import json, io
BASE = r"E:\破甲工作区\packrivo-backup-34a\dsh-out\34a-tools\lang"
for lg in ("zh-CN", "zh-TW"):
    d = json.load(io.open(BASE + "\\%s.json" % lg, encoding="utf-8"))
    print("#" * 25, lg)
    for sec in ("SEG", "H1", "TITLE", "OTHER"):
        for k, v in d[sec].items():
            if "pouch" in k.lower():
                print("  [%s] EN: %s" % (sec, k))
                print("        TR: %s" % v)
