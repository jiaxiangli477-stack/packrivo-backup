"""29y 第 3 项：把文本节点清单做成「翻译包」，翻完再灌词典。

输入  nodes-<lang>.jsonl（extract-en-nodes-29y.php 的输出）
输出  <outdir>/<lang>-todo.csv   列：lang,node,pages,words_est,translation
      <outdir>/<lang>-todo.json  同一份数据的机读版（injector 读这个更快）
规则  - 同一语种内按 node 去重，pages 记出现页数（翻一条修多页）
      - 只收 >=1 个残留词的节点，最多 600 字符
      - translation 列留空，等翻译
用法  python make-translation-packets-29y.py <nodes 目录|单个文件> <输出目录> [lang...]
"""
import csv, json, os, sys, collections

sys.stdout.reconfigure(encoding="utf-8")

src = sys.argv[1] if len(sys.argv) > 1 else r"E:\破甲工作区\.tmp\29y"
outdir = sys.argv[2] if len(sys.argv) > 2 else r"E:\破甲工作区\packrivo-backup\deploy\i18n-packets"
langs = sys.argv[3:] or None

os.makedirs(outdir, exist_ok=True)

files = []
if os.path.isdir(src):
    for f in sorted(os.listdir(src)):
        if f.startswith("nodes-") and f.endswith(".jsonl"):
            files.append(os.path.join(src, f))
else:
    files = [src]

total_nodes = total_words = 0
for path in files:
    lang = os.path.basename(path)[6:-6]
    if langs and lang not in langs:
        continue
    rows = [json.loads(l) for l in open(path, encoding="utf-8") if l.strip()]
    pages = collections.defaultdict(set)
    words = {}
    for r in rows:
        pages[r["node"]].add(r["url"])
        words[r["node"]] = r["words"]
    items = []
    for node, urls in sorted(pages.items(), key=lambda kv: (-len(kv[1]), -len(kv[0]))):
        items.append({
            "lang": lang,
            "node": node,
            "pages": len(urls),
            "words_est": len(node.split()),
            "translation": "",
        })
    csv_path = os.path.join(outdir, f"{lang}-todo.csv")
    with open(csv_path, "w", encoding="utf-8", newline="\n") as fh:
        w = csv.DictWriter(fh, fieldnames=["lang", "node", "pages", "words_est", "translation"],
                           lineterminator="\n")
        w.writeheader()
        w.writerows(items)
    json_path = os.path.join(outdir, f"{lang}-todo.json")
    with open(json_path, "w", encoding="utf-8", newline="\n") as fh:
        json.dump(items, fh, ensure_ascii=False, indent=2)
        fh.write("\n")
    nw = sum(i["words_est"] for i in items)
    total_nodes += len(items)
    total_words += nw
    print(f"  {lang:6} 节点 {len(items):4} 个（覆盖 {len(set().union(*pages.values())) if pages else 0} 页）"
          f" 源词数 {nw:6} -> {csv_path}")

print(f"合计 {total_nodes} 个节点，源词数 {total_words}")
print("下一步：把 translation 列填满，再用 apply-translation-packets-29y.py 灌进词典。")
