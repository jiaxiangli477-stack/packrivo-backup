"""29y 第 5 项：外链风险清单（只出报告，不生成 disavow）。

数据来源分两类，逐行标在 source 列，不混：
  cited    = 任务单引用的 Semrush 报表数字（本机没有 Semrush 导出文件）
  measured = 本机现场实测（HTTP 状态、页面 title、首页是否出现指向本站的链接）

如果拿到 Semrush 导出（列：Source page url / Source domain / Anchor text / ...），
用 --semrush=<文件> 传进来，脚本会按域名聚合，cited 列换成真实条数。

用法：
  python build-backlinks-29y.py --out=<csv> [--semrush=<导出.csv>]
"""
import csv, json, os, sys, collections

sys.stdout.reconfigure(encoding="utf-8")

out = None
semrush = None
for a in sys.argv[1:]:
    if a.startswith("--out="):
        out = a.split("=", 1)[1]
    elif a.startswith("--semrush="):
        semrush = a.split("=", 1)[1]
if not out:
    print("需要 --out=<csv>")
    sys.exit(2)

# 现场实测结果（29y 本轮抓的，落盘在 .tmp/29y/bl-*.head|html）
MEASURED = {
    "rankvance.com": {
        "http": 200,
        "title": "#1 Backlink Agency: Check 100% Quality Backlink Plans for Your Website at Rankvance.com (Offering 10+ Backlink Types)",
        "homepage_links_to_site": 0,
        "note": "首页标题自称 #1 Backlink Agency，明码标价卖外链套餐",
    },
    "8coint.com": {
        "http": 403,
        "title": "Just a moment...",
        "homepage_links_to_site": 0,
        "note": "首页被 Cloudflare 挑战拦截，抓取不到正文",
    },
    "link-pen.com": {
        "http": 200,
        "title": "Newly Registered & Expired Domains - Checker Tools | link-pen.com",
        "homepage_links_to_site": 0,
        "note": "过期域名/新注册域名查询工具站，典型链接网络入口",
    },
}

# 任务单里明确给出条数的域名
CITED = {"rankvance.com": 162}
CITED_TOTAL = 235
CITED_ANCHORS = {
    "rankvance.com": ["High-quality backlink", "#1 Backlink Agency"],
}

rows = []
if semrush and os.path.exists(semrush):
    agg = collections.defaultdict(lambda: {"count": 0, "anchors": collections.Counter(), "urls": []})
    with open(semrush, encoding="utf-8-sig", newline="") as fh:
        rd = csv.DictReader(fh)
        for rec in rd:
            dom = ""
            for k in ("Source domain", "source_domain", "Domain", "domain", "Source"):
                if rec.get(k):
                    dom = rec[k].strip().lower()
                    break
            if not dom:
                continue
            a = ""
            for k in ("Anchor text", "anchor", "Anchor"):
                if rec.get(k):
                    a = rec[k].strip()
                    break
            agg[dom]["count"] += 1
            if a:
                agg[dom]["anchors"][a] += 1
            u = rec.get("Source page url") or rec.get("source_url") or ""
            if u and len(agg[dom]["urls"]) < 3:
                agg[dom]["urls"].append(u)
    for dom, v in sorted(agg.items(), key=lambda kv: -kv[1]["count"]):
        m = MEASURED.get(dom, {})
        top = v["anchors"].most_common(3)
        rows.append({
            "domain": dom,
            "links": v["count"],
            "source": "semrush-export",
            "anchor_samples": " | ".join(f"{a} ×{c}" for a, c in top),
            "http": m.get("http", ""),
            "page_title": m.get("title", ""),
            "rationale": m.get("note", ""),
            "evidence_urls": " ".join(v["urls"]),
            "action": "负责人决定（本轮不提交 disavow）",
        })
else:
    for dom in ("rankvance.com", "8coint.com", "link-pen.com"):
        m = MEASURED[dom]
        rows.append({
            "domain": dom,
            "links": CITED.get(dom, ""),
            "source": "cited: 任务单引 Semrush" if dom in CITED else "cited: 任务单列名（条数未给）",
            "anchor_samples": " | ".join(CITED_ANCHORS.get(dom, [])) or "（任务单未列出）",
            "http": m["http"],
            "page_title": m["title"],
            "rationale": m["note"] + "；首页不含指向本站的链接，被链页面在导出明细里才能定位",
            "evidence_urls": "",
            "action": "负责人决定（本轮不提交 disavow）",
        })

with open(out, "w", encoding="utf-8", newline="\n") as fh:
    w = csv.DictWriter(fh, fieldnames=["domain", "links", "source", "anchor_samples", "http",
                                       "page_title", "rationale", "evidence_urls", "action"],
                       lineterminator="\n")
    w.writeheader()
    w.writerows(rows)

total_cited = sum(r["links"] for r in rows if isinstance(r["links"], int))
print(f"写入 {len(rows)} 行 -> {out}")
print(f"已给条数的合计 {total_cited} 条（任务单口径全站 {CITED_TOTAL} 条，差 {CITED_TOTAL - total_cited} 条在未列名的域名里）")
print("本文件只做报告，不生成 disavow，也不改任何线上配置。")
