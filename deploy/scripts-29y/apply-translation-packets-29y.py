"""29y：把翻译包灌进词典，逐条自检，落盘走 dict_io_29y.write_dict。

起点固定为 HEAD 原文 + i18n/delta（不读工作区里可能被别的脚本压扁的旧文件），再叠翻译包。
只读仓库内文件，不碰 ARTEX 目录、不碰 .tmp。

自检（逐条 PASS/FAIL，不看 exit 码）：
  1. translation 为空 -> 算未翻，跳过
  2. translation 与 node 完全相同 -> FAIL（等于没翻）
  3. node 与 translation 的 %s 个数不一致 -> FAIL（sprintf 会崩）
  4. 拉丁语种：译文里英文功能词（扣掉本语种功能词后）≥3 个 -> FAIL（翻成了英文）
用法：
  python deploy/scripts-29y/apply-translation-packets-29y.py [lang ...]
"""
import os, re, sys

HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, HERE)
import dict_io_29y as dio  # noqa: E402

sys.stdout.reconfigure(encoding="utf-8")

CJK = re.compile(r"[\u3040-\u30ff\u4e00-\u9fff\uac00-\ud7af]")
ARABIC = re.compile(r"[\u0600-\u06ff]")
ASCII_WORD = re.compile(r"[A-Za-z][A-Za-z'\-]*")

EN_STOP = {"the", "and", "is", "are", "your", "our", "we", "you", "with", "for", "this", "that",
           "from", "to", "be", "not", "or", "it", "as", "at", "on", "will", "can", "need", "each",
           "per", "has", "have", "of", "in", "if", "than", "then", "when", "what", "how", "why",
           "who", "all", "any", "more", "most", "do", "does", "did", "was", "were", "been", "they",
           "their", "them", "there", "here", "which", "so", "but", "into", "over", "under", "about",
           "after", "before", "between", "should", "would", "could", "may", "must", "also", "only",
           "both", "up", "out", "off", "same", "other", "such", "very", "well", "keep", "used", "use"}


def main():
    repo = dio.repo_root()
    langs = [a for a in sys.argv[1:] if not a.startswith("--")] or dio.LANGS
    LOCAL = dio.load_local_stop(repo)
    fails, added, skipped_empty, same = [], 0, 0, 0
    for lang in langs:
        items = dio.load_packet(dio.packet_dir(repo), lang)
        if not items:
            print(f"  {lang:6} 没有翻译包，跳过")
            continue
        d, info = dio.build_dict(repo, lang, with_packets=False)
        n_add = n_skip = 0
        for it in items:
            node = it.get("node", "")
            tr = (it.get("translation") or "").strip()
            if not tr:
                skipped_empty += 1
                continue
            if tr == node:
                fails.append(f"{lang} 译文与原文相同：{node[:50]!r}")
                same += 1
                continue
            if node.count("%s") != tr.count("%s"):
                fails.append(f"{lang} %s 个数不一致 {node.count('%s')}→{tr.count('%s')}：{node[:40]!r}")
                continue
            if not (CJK.search(tr) or ARABIC.search(tr)):
                local = LOCAL.get(lang, set())
                hits = [w for w in ASCII_WORD.findall(tr.lower()) if w in EN_STOP and w not in local]
                if len(hits) >= 3:
                    fails.append(f"{lang} 译文仍是英文（{len(hits)} 个功能词：{','.join(hits[:4])}）：{tr[:50]!r}")
                    continue
            cur = d["block"].get(node)
            if cur is None:
                d["block"][node] = tr
                n_add += 1
                added += 1
            elif cur != tr:
                fails.append(f"{lang} 键冲突：{node[:40]!r} 已有 {cur[:30]!r}")
            else:
                n_skip += 1
        try:
            st = dio.write_dict(repo, lang, d)
        except dio.DictError as exc:
            fails.append(f"{lang} 写词典失败：{exc}")
            continue
        print(f"  {lang:6} 新增 {n_add:4} 已存在 {n_skip:4} block 键数 {st['keys']['block']} "
              f"总键 {st['total']} 行 {st['lines']}")

    print(f"\n合计新增 {added}，未翻 {skipped_empty}，与原文相同 {same}，FAIL {len(fails)}")
    for f in fails[:20]:
        print("FAIL", f)
    print("结论：" + ("PASS 全部通过" if not fails else "FAIL 见上"))


if __name__ == "__main__":
    main()
