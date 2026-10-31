"""按语种汇总残留英文扫描结果（达标 / 豁免待批 / 不达标）。

用法：python by-lang-residual-29y.py <scan.jsonl>
"""
import json, re, sys

LEGAL = ("privacy", "terms", "cookie", "legal", "refund", "impressum")
ORDER = ["ar", "de", "es", "fr", "it", "ja", "ko", "nl", "pt", "zh-CN", "zh-TW"]


def lang_of(url):
    seg = re.sub(r"^https?://[^/]+/", "", url).split("/")[0]
    return seg if seg in ORDER else "en"


def main():
    sys.stdout.reconfigure(encoding="utf-8")
    rows = [json.loads(l) for l in open(sys.argv[1], encoding="utf-8") if l.strip()]
    stat = {}
    for r in rows:
        lg = lang_of(r["url"])
        tail = r["url"].rstrip("/").split("/")[-1]
        ok = int(r.get("residual_count", 0)) <= 5 and int(r.get("long_runs", 0)) == 0
        cls = "达标" if ok else ("豁免待批" if any(t in tail for t in LEGAL) else "不达标")
        s = stat.setdefault(lg, {"达标": 0, "豁免待批": 0, "不达标": 0, "总计": 0})
        s[cls] += 1
        s["总计"] += 1
    print(f"{'语种':8}{'已扫':>6}{'达标':>7}{'豁免待批':>9}{'不达标':>8}")
    tot = {"达标": 0, "豁免待批": 0, "不达标": 0, "总计": 0}
    for lg in ORDER + ["en"]:
        if lg not in stat:
            continue
        s = stat[lg]
        print(f"{lg:8}{s['总计']:6}{s['达标']:7}{s['豁免待批']:9}{s['不达标']:8}")
        for k in tot:
            tot[k] += s[k]
    print(f"{'合计':8}{tot['总计']:6}{tot['达标']:7}{tot['豁免待批']:9}{tot['不达标']:8}")
    miss = [lg for lg in ORDER if lg not in stat or stat[lg]["总计"] < 126]
    if miss:
        print("未扫完：" + "、".join(f"{lg}({stat.get(lg, {}).get('总计', 0)}/126)" for lg in miss))


if __name__ == "__main__":
    main()
