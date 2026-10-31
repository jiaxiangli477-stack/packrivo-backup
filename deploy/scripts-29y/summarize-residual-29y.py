"""29y 残留英文汇总：把 scan-main-english-29y.php 的 jsonl 汇总成
达标 / 豁免待批 / 不达标 三类，判据写在代码里，逐条打 PASS/FAIL。

判据（任务单）：每页 residual_count ≤ 5 且 long_runs == 0。
  达标      residual_count ≤5 且 long_runs == 0
  豁免待批  法律页（privacy / terms / cookie 等）且超出判据 —— 只登记，不代表通过，等负责人拍板
  不达标    非法律页且超出判据

用法：
  python deploy/scripts-29y/summarize-residual-29y.py <scan.jsonl> [输出前缀]
"""
import csv, json, os, re, sys

LEGAL_SLUGS = ("privacy", "terms", "cookie", "legal", "refund", "return-policy", "impressum",
               "agb", "mentions-legales", "aviso-legal", "privacybeleid", "informativa")


def is_legal(url: str) -> bool:
    path = re.sub(r"^https?://[^/]+", "", url).strip("/").lower()
    segs = [s for s in path.split("/") if s]
    tail = segs[-1] if segs else ""
    return any(t in tail for t in LEGAL_SLUGS)


def main():
    sys.stdout.reconfigure(encoding="utf-8")
    src = sys.argv[1]
    prefix = sys.argv[2] if len(sys.argv) > 2 else "residual-summary-29y"
    rows = []
    for line in open(src, encoding="utf-8"):
        line = line.strip()
        if not line:
            continue
        r = json.loads(line)
        url = r.get("url", "")
        cnt = int(r.get("residual_count", 0))
        runs = int(r.get("long_runs", 0))
        ok = cnt <= 5 and runs == 0
        legal = is_legal(url)
        cls = "达标" if ok else ("豁免待批" if legal else "不达标")
        rows.append({"url": url,
                     "residual_raw": r.get("residual_raw", 0),
                     "residual_count": cnt,
                     "residual_tokens": r.get("residual_tokens", 0),
                     "long_runs": runs,
                     "class": cls,
                     "legal": legal,
                     "long_run_samples": " | ".join(r.get("long_run_samples", [])[:3]),
                     "residual_words": ",".join(r.get("residual_words", [])[:12])})

    out_dir = os.path.abspath(os.path.join(os.path.dirname(os.path.abspath(__file__)),
                                          "..", "verification-29y", "evidence"))
    os.makedirs(out_dir, exist_ok=True)
    csv_p = os.path.join(out_dir, f"{prefix}.csv")
    with open(csv_p, "w", encoding="utf-8", newline="") as fh:
        w = csv.DictWriter(fh, fieldnames=["url", "residual_raw", "residual_count", "residual_tokens",
                                           "long_runs", "class", "legal", "long_run_samples",
                                           "residual_words"], lineterminator="\n")
        w.writeheader()
        w.writerows(rows)

    n_pass = sum(1 for r in rows if r["class"] == "达标")
    n_ex = sum(1 for r in rows if r["class"] == "豁免待批")
    n_fail = sum(1 for r in rows if r["class"] == "不达标")
    print(f"扫描页数：{len(rows)}")
    print(f"  达标（residual_count ≤5 且 long_runs=0）：{n_pass}")
    print(f"  豁免待批（法律页，超判据）：{n_ex}")
    print(f"  不达标（非法律页，超判据）：{n_fail}")
    if n_ex:
        print("\n豁免待批清单（负责人拍板后才算豁免）：")
        for r in rows:
            if r["class"] == "豁免待批":
                print(f"  {r['url']}  词数 {r['residual_count']}（原始 {r['residual_raw']}）连续段 {r['long_runs']}")
    if n_fail:
        print("\n不达标页（前 40）：")
        for r in [x for x in rows if x["class"] == "不达标"][:40]:
            print(f"  {r['url']}  词数 {r['residual_count']}（原始 {r['residual_raw']}）"
                  f"连续段 {r['long_runs']}  样例 {r['long_run_samples'][:70]}")
    print()
    print(f"判据① 每页 ≤5 且 long_runs=0：达标 {n_pass} + 豁免待批 {n_ex} = {n_pass + n_ex} / {len(rows)}"
          f" —— {'PASS' if n_pass + n_ex == len(rows) else 'FAIL'}")
    print(f"判据② 不达标必须为 0：{n_fail} —— {'PASS' if n_fail == 0 else 'FAIL'}")
    print(f"CSV 已写 {csv_p}")


if __name__ == "__main__":
    main()
