import json, glob, os

PREF = ("A coordination fee of", "Coordination fees range", "The quotation sets out supplier costs")
langs = ["ar","de","es","fr","it","ja","ko","nl","pt","zh-CN","zh-TW"]
for lg in langs:
    p = r"E:\破甲工作区\packrivo-backup-34a\deploy\i18n-merged\%s-dict.json" % lg
    d = json.load(open(p, encoding="utf-8"))
    print("#" * 20, lg)
    seen = set()
    for b, m in d.items():
        if not isinstance(m, dict):
            continue
        for k, v in m.items():
            if not isinstance(v, str):
                continue
            for pref in PREF:
                if k.startswith(pref) and k not in seen:
                    seen.add(k)
                    print("  [%s] EN: %s" % (b, k))
                    print("       TR: %s" % v)
