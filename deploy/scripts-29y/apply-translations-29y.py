"""把「英文节点 + 译文」写进某语种 delta 的 text 桶，然后走 apply-batch 强制 11/11。

输入 JSON（list，或 {"pairs": [...]}）：
  [{"text": "英文原文（必须与页面上的节点文本逐字节一致）", "tr": "译文"}, ...]
用法：
  python deploy/scripts-29y/apply-translations-29y.py <lang> <pairs.json> [--dry]
先试跑（--dry）只报会写多少条、有没有和现有词典冲突，再实写。
"""
import json, os, subprocess, sys

HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, HERE)
import dict_io_29y as dio  # noqa: E402


def load_pairs(path):
    raw = json.load(open(path, encoding="utf-8"))
    items = raw.get("pairs") if isinstance(raw, dict) else raw
    out = []
    for it in items:
        t = (it.get("text") or "").strip()
        tr = (it.get("tr") or "").strip()
        if t and tr:
            out.append((t, tr))
    return out


def main():
    sys.stdout.reconfigure(encoding="utf-8")
    if len(sys.argv) < 3:
        print(__doc__)
        return 2
    lang, pairs_path = sys.argv[1], sys.argv[2]
    dry = "--dry" in sys.argv
    repo = dio.repo_root()
    pairs = load_pairs(pairs_path)
    print(f"语种 {lang}，待写 {len(pairs)} 条")

    head = dio.head_dict(repo, lang)
    head_text = head.get("text", {})
    add_p = os.path.join(dio.delta_dir(repo), f"{lang}-add.json")
    cur = json.load(open(add_p, encoding="utf-8")) if os.path.exists(add_p) else {}
    cur.setdefault("text", {})

    n_new = n_upd = n_conflict = n_same = 0
    for t, tr in pairs:
        if t in head_text:
            if head_text[t] == tr:
                n_same += 1
                continue
            n_conflict += 1
            print(f"  CONFLICT 已在 HEAD 且值不同：{t[:60]!r}")
            continue
        if t in cur["text"]:
            n_upd += 1
        else:
            n_new += 1
        cur["text"][t] = tr

    print(f"新增 {n_new}，更新 delta 已有 {n_upd}，与 HEAD 相同跳过 {n_same}，冲突 {n_conflict}")
    if dry:
        print("DRY-RUN 未写盘")
        return 0
    with open(add_p, "w", encoding="utf-8", newline="\n") as fh:
        json.dump(cur, fh, ensure_ascii=False, indent=2)
        fh.write("\n")
    print(f"已写 {os.path.relpath(add_p, repo)}")
    r = subprocess.run([sys.executable, os.path.join(HERE, "apply-batch-29y.py"), lang], cwd=repo)
    return r.returncode


if __name__ == "__main__":
    sys.exit(main())
