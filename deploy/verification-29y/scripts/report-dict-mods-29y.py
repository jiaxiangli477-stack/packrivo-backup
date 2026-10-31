"""29y：列出「原有译文被改」的全部条目（工作区 vs HEAD），落证据。

每批译文灌完都跑一次。判据：
  - 只允许出现在这份清单里的键被改（改就改，但必须留档）
  - 清单为空 = 本轮没有任何原译文被动过
输出：
  deploy/verification-29y/evidence/dict-mods-29y.csv   语种,桶,键,旧译文,新译文
  deploy/verification-29y/evidence/dict-mods-29y.txt   人读表格 + 汇总
用法：python report-dict-mods-29y.py <repo_root>
"""
import csv, json, os, sys

HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, HERE)
from dictio_29y import head_dict, BUCKETS  # noqa: E402

LANGS = ["ar", "de", "es", "fr", "it", "ja", "ko", "nl", "pt", "zh-CN", "zh-TW"]
EVID = os.path.join("deploy", "verification-29y", "evidence")


def main():
    sys.stdout.reconfigure(encoding="utf-8")
    repo = sys.argv[1]
    rows = []
    for lg in LANGS:
        p = os.path.join(repo, "deploy", "i18n-merged", f"{lg}-dict.json")
        cur = json.load(open(p, encoding="utf-8"))
        head = head_dict(repo, lg)
        for b in BUCKETS:
            hv, cv = head.get(b, {}), cur.get(b, {})
            for k, old in hv.items():
                new = cv.get(k)
                if new is None:
                    rows.append([lg, b, k, old, "（本轮被删）"])
                elif new != old:
                    rows.append([lg, b, k, old, new])
            for k in cv:
                if k not in hv:
                    pass  # 新增不算改动
    out_csv = os.path.join(repo, EVID, "dict-mods-29y.csv")
    os.makedirs(os.path.dirname(out_csv), exist_ok=True)
    with open(out_csv, "w", encoding="utf-8", newline="") as fh:
        w = csv.writer(fh, lineterminator="\n")
        w.writerow(["lang", "bucket", "key", "old", "new"])
        w.writerows(rows)

    lines = [f"原有译文被改清单（工作区 vs HEAD），共 {len(rows)} 条", ""]
    per = {}
    for r in rows:
        per[r[0]] = per.get(r[0], 0) + 1
    lines.append("按语种：" + ("、".join(f"{k} {v}" for k, v in per.items()) if per else "无"))
    lines.append("")
    for i, r in enumerate(rows, 1):
        lines.append(f"[{i}] {r[0]} · {r[1]}")
        lines.append(f"    键：{r[2]}")
        lines.append(f"    旧：{r[3]}")
        lines.append(f"    新：{r[4]}")
    txt = "\n".join(lines) + "\n"
    out_txt = os.path.join(repo, EVID, "dict-mods-29y.txt")
    with open(out_txt, "w", encoding="utf-8", newline="\n") as fh:
        fh.write(txt)
    print(txt)
    print(f"写入 {out_csv}")
    print(f"写入 {out_txt}")


if __name__ == "__main__":
    main()
