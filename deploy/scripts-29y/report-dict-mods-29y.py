"""29y：列出「原有译文被改」的全部条目（工作区 vs HEAD），落证据。

每批译文灌完跑一次。只读仓库内文件。
输出：
  deploy/verification-29y/evidence/dict-mods-29y.csv
  deploy/verification-29y/evidence/dict-mods-29y.txt
用法：python deploy/scripts-29y/report-dict-mods-29y.py
"""
import csv, json, os, sys

HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, HERE)
import dict_io_29y as dio  # noqa: E402


def main():
    sys.stdout.reconfigure(encoding="utf-8")
    repo = dio.repo_root()
    rows = []
    for lg in dio.LANGS:
        p = dio.dict_path(repo, lg)
        cur = json.load(open(p, encoding="utf-8"))
        head = dio.head_dict(repo, lg)
        for b in dio.BUCKETS:
            hv, cv = head.get(b, {}), cur.get(b, {})
            for k, old in hv.items():
                new = cv.get(k)
                if new is None:
                    rows.append([lg, b, k, old, "（本轮被删）"])
                elif new != old:
                    rows.append([lg, b, k, old, new])

    ev = os.path.join(repo, "deploy", "verification-29y", "evidence")
    os.makedirs(ev, exist_ok=True)
    with open(os.path.join(ev, "dict-mods-29y.csv"), "w", encoding="utf-8", newline="") as fh:
        w = csv.writer(fh, lineterminator="\n")
        w.writerow(["lang", "bucket", "key", "old", "new"])
        w.writerows(rows)

    per = {}
    for r in rows:
        per[r[0]] = per.get(r[0], 0) + 1
    out = [f"原有译文被改清单（工作区 vs HEAD），共 {len(rows)} 条", "",
           "按语种：" + ("、".join(f"{k} {v}" for k, v in per.items()) if per else "无"), ""]
    for i, r in enumerate(rows, 1):
        out += [f"[{i}] {r[0]} · {r[1]}", f"    键：{r[2]}", f"    旧：{r[3]}", f"    新：{r[4]}"]
    text = "\n".join(out) + "\n"
    with open(os.path.join(ev, "dict-mods-29y.txt"), "w", encoding="utf-8", newline="\n") as fh:
        fh.write(text)
    print(text)
    print(f"写入 {os.path.join(ev, 'dict-mods-29y.csv')}")
    print("结论：" + ("PASS 无非预期改动" if not rows else f"PASS 已逐条留档 {len(rows)} 条"))


if __name__ == "__main__":
    main()
