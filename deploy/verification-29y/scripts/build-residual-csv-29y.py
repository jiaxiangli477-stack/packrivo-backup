"""29y 第 3 项：把扫描 JSONL 汇总成清单 CSV + 统计。

用法：python build-residual-csv-29y.py <scan.jsonl> <out.csv> [--stats]
"""
import csv, json, sys, io, collections

sys.stdout.reconfigure(encoding="utf-8")

src, dst = sys.argv[1], sys.argv[2]
rows = []
with open(src, encoding="utf-8") as fh:
    for line in fh:
        line = line.strip()
        if not line:
            continue
        rows.append(json.loads(line))

total = len(rows)
http_ok = sum(1 for r in rows if r.get("http") == 200)
main_ok = sum(1 for r in rows if r.get("main_found"))
flag = [r for r in rows if (r.get("residual_count") or 0) > 5]
clean = [r for r in rows if 0 <= (r.get("residual_count") or -1) <= 5]
bad = [r for r in rows if (r.get("residual_count") or -1) < 0]

print(f"总页数        {total}")
print(f"HTTP 200      {http_ok}")
print(f"<main> 命中   {main_ok}")
print(f"残留>5 词     {len(flag)}")
print(f"残留 0-5 词   {len(clean)}")
print(f"异常页        {len(bad)}")

by_lang = collections.Counter(r["lang"] for r in flag)
print("按语种分布：", dict(sorted(by_lang.items())))

words = collections.Counter()
for r in flag:
    for w in r.get("residual_words", []):
        words[w] += 1
print("最高频残留词 top20：", words.most_common(20))

# 唯一英文片段（跨页去重），用于补词典
segs = collections.Counter()
for r in flag:
    for s in r.get("english_segments", []):
        segs[s.strip()] += 1
print(f"唯一英文片段  {len(segs)}（出现次数合计 {sum(segs.values())}）")

with open(dst, "w", encoding="utf-8", newline="\n") as fh:
    w = csv.writer(fh, lineterminator="\n")
    w.writerow(["lang", "url", "residual_count", "segment_count", "residual_words",
                "first_segment", "second_segment"])
    for r in sorted(flag, key=lambda x: (-(x.get("residual_count") or 0), x["lang"], x["url"])):
        seg = r.get("english_segments", [])
        w.writerow([r["lang"], r["url"], r.get("residual_count"), r.get("segment_count"),
                    " ".join(r.get("residual_words", []))[:400],
                    (seg[0] if seg else "")[:300], (seg[1] if len(seg) > 1 else "")[:300]])

if bad:
    with open(dst.replace(".csv", "-anomalies.csv"), "w", encoding="utf-8", newline="\n") as fh:
        w = csv.writer(fh, lineterminator="\n")
        w.writerow(["lang", "url", "http", "note"])
        for r in bad:
            w.writerow([r["lang"], r["url"], r.get("http"), r.get("error", "no <main>")])
    print(f"异常页清单 -> {dst.replace('.csv', '-anomalies.csv')}")

print(f"清单 -> {dst}")
