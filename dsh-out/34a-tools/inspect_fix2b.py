import json

BASE = r"E:\破甲工作区\packrivo-backup-34a\dsh-out"
langs = ["ar","de","es","fr","it","ja","ko","nl","pt","zh-CN","zh-TW"]
OLD = "8-15% coordination fee on production only."
OLD2 = "No retainer before scope."
for lg in langs:
    d = json.load(open(BASE + r"\34a-tools\lang\%s.json" % lg, encoding="utf-8"))
    SEG = d["SEG"]
    print(lg, "|", SEG.get(OLD), "|", SEG.get(OLD2))
print("=" * 70)
import io
txt = open(BASE + r"\34a-tools\glossary.txt", encoding="utf-8").read().splitlines()
for line in txt:
    if "coordination" in line.lower() or "retainer" in line.lower() or "fee" in line.lower():
        print(line)
print("=" * 70)
# also check dictionary files for coordination fee wording
import glob, os
for p in sorted(glob.glob(r"E:\破甲工作区\packrivo-backup-34a\deploy\i18n-merged\*-dict.json")):
    d = json.load(open(p, encoding="utf-8"))
    hits = []
    for b, m in d.items():
        if not isinstance(m, dict):
            continue
        for k, v in m.items():
            if isinstance(v, str) and ("coordination" in k.lower() or "retainer" in k.lower()):
                hits.append((b, k[:70], v[:110]))
    print(os.path.basename(p), len(hits))
    for h in hits:
        print("   ", h)
