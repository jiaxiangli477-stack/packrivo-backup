import json, csv, io, sys, re
from collections import OrderedDict

BASE = r"E:\破甲工作区\packrivo-backup-34a\dsh-out"
keys = json.load(open(BASE + r"\34a-tools\keys.json", encoding="utf-8"))
final = keys["final"]          # key -> url  (key = '<url> meta')
where = keys["where"]

rows = list(csv.DictReader(open(BASE + r"\34a-i18n.csv", encoding="utf-8")))
print("rows=", len(rows))
hit = OrderedDict()
for r in rows:
    en = r["en"]
    if "retainer" in en or "production only" in en:
        hit.setdefault(en, set()).add(r["bucket"])
print("distinct en with retainer/production-only:", len(hit))
for en in hit:
    print("-" * 70)
    print("BUCKETS", sorted(hit[en]))
    print("EN:", en)
    urls = [u for u, e in final.items() if e == en]
    print("URLS:", urls)
    print("WHERE:", [where.get(en)])

# segment inventory of the 4 new keys
NEW = [
 "Custom packaging sourcing. Then a quote. 8-15% coordination fee, agreed in writing before work starts. Request a quote.",
 "Custom packaging guide. 8-15% coordination fee, agreed in writing before work starts. Dieline with every quote. Request a quote.",
 "Custom packaging quote request. 8-15% coordination fee, agreed in writing before work starts. Dieline with every quote. Request a quote.",
 "Custom packaging finishes. 8-15% coordination fee, agreed in writing before work starts. Dieline with every quote. Request a quote.",
]
print("=" * 70)
segs = set()
for k in NEW:
    for s in re.split(r"(?<=\.)\s+", k):
        segs.add(s)
print("segments needed:")
for s in sorted(segs):
    print("  ", repr(s))
print("=" * 70)
langs = ["ar","de","es","fr","it","ja","ko","nl","pt","zh-CN","zh-TW"]
for lg in langs:
    d = json.load(open(BASE + r"\34a-tools\lang\%s.json" % lg, encoding="utf-8"))
    SEG = d["SEG"]
    missing = [s for s in segs if s not in SEG]
    print(lg, "SEG=", len(SEG), "missing_segs=", len(missing))
    for m in missing:
        print("   MISSING:", repr(m))
