"""29y 第 3 项：把填好的翻译包灌进 11 语种词典，并做自检。

输入  <packetdir>/<lang>-todo.json（make-translation-packets-29y.py 产出，translation 已填）
      <i18ndir>/<lang>-dict.json
输出  改写词典（block 桶），打印新增/跳过/冲突/未翻
自检  - translation 为空 -> 跳过（算未翻）
      - translation 与 node 完全相同 -> FAIL（等于没翻）
      - 目标文本里 ASCII 英文词 > 2 个且没有本语种字符 -> FAIL（翻成了英文）
      - node 里的 %s 个数必须与 translation 一致 -> 否则 FAIL（sprintf 会崩）
      - 键已存在且值不同 -> FAIL（冲突，不静默覆盖）
用法  python apply-translation-packets-29y.py <packetdir> <i18ndir> [lang...]
退出码：有 FAIL 就是 1
"""
import json, os, re, shutil, sys

sys.stdout.reconfigure(encoding="utf-8")

packetdir = sys.argv[1]
i18n = sys.argv[2]
langs = sys.argv[3:] or ["ar", "de", "es", "fr", "it", "ja", "ko", "nl", "pt", "zh-CN", "zh-TW"]
# i18n = <repo>/deploy/i18n-merged，所以仓库根是上两级
REPO_ROOT = os.path.abspath(os.path.join(i18n, "..", ".."))
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from dictio_29y import write_dict, head_dict, apply_delta  # noqa: E402  唯一写入口
DELTA_DIR = os.environ.get("PKV29Y_DELTA") or os.path.join(
    REPO_ROOT, "deploy", "verification-29y", "i18n", "delta")

CJK = re.compile(r"[\u3040-\u30ff\u4e00-\u9fff\uac00-\ud7af]")
ARABIC = re.compile(r"[\u0600-\u06ff]")
ASCII_WORD = re.compile(r"[A-Za-z][A-Za-z'\-]*")

# 拉丁字母语种（de/fr/es/it/nl/pt）的目标文本本身就是 ASCII 字母，不能按「有没有 ASCII 词」
# 判英文。改成数英文功能词：出现 3 个以上才认定这段没翻。
EN_STOP = {"the", "and", "is", "are", "your", "our", "we", "you", "with", "for", "this", "that",
           "from", "to", "be", "not", "or", "it", "as", "at", "on", "will", "can", "need", "each",
           "per", "has", "have", "of", "in", "if", "than", "then", "when", "what", "how", "why",
           "who", "all", "any", "more", "most", "do", "does", "did", "was", "were", "been", "they",
           "their", "them", "there", "here", "which", "so", "but", "into", "over", "under", "about",
           "after", "before", "between", "should", "would", "could", "may", "must", "also", "only",
           "both", "up", "out", "off", "same", "other", "such", "very", "well", "keep", "used", "use"}

# "in" 是德语介词、"was" 是德语疑问词、"plus" 是法语词 —— 判「译文是不是英文」时必须先扣掉
# 目标语言自己也在用的词，否则正经译文会被误判成英文丢掉。
# 本族词表只保留一份真源：deploy/verification-29y/i18n/local-stop.json，这里只负责加载。
LOCAL_STOP_FILE = os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", "i18n", "local-stop.json")


def load_local_stop():
    with open(LOCAL_STOP_FILE, encoding="utf-8") as fh:
        raw = json.load(fh)
    return {k: set(v) for k, v in raw.items() if not k.startswith("_")}


LOCAL_STOP = load_local_stop()

fails = []
added = skipped_empty = same = 0
for lang in langs:
    pp = os.path.join(packetdir, f"{lang}-todo.json")
    if not os.path.exists(pp):
        print(f"  {lang:6} 没有翻译包，跳过")
        continue
    dp = os.path.join(i18n, f"{lang}-dict.json")
    # 起点一律是 HEAD 原文 + 本轮增量，再叠翻译包 —— 不读工作区里可能被别的脚本
    # 写成单行的旧文件，避免「谁最后写谁说话」。
    d = head_dict(REPO_ROOT, lang)
    for b in ("title", "attr", "block", "text"):
        d.setdefault(b, {})
    apply_delta(d, DELTA_DIR, lang)
    bak = dp + ".pre29y-batch"
    if not os.path.exists(bak):
        shutil.copy2(dp, bak)
    items = json.load(open(pp, encoding="utf-8"))
    n_add = n_skip = 0
    for it in items:
        node = it["node"]
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
            local = LOCAL_STOP.get(lang, set())
            hits = [w for w in ASCII_WORD.findall(tr.lower()) if w in EN_STOP and w not in local]
            if len(hits) >= 3:
                fails.append(f"{lang} 译文仍是英文（{len(hits)} 个英文功能词：{','.join(hits[:4])}）：{tr[:50]!r}")
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
    # 落盘统一走 dictio-29y.write_dict：以 HEAD 原文为底、indent=2、sort_keys=False、newline="\n"。
    # 早先这里自己写 indent=1/sort_keys=True（整文件重排），i909 那边又写 separators=(',',':')（单行），
    # 两边谁最后写谁决定文件形态，it-dict.json 就被压成过单行 1.5MB（git diff +1/-7742）。
    try:
        st = write_dict(REPO_ROOT, lang, d)
    except Exception as exc:
        fails.append(f"{lang} 写词典失败：{exc}")
        continue
    print(f"  {lang:6} 新增 {n_add:4} 已存在 {n_skip:4} block 键数 {st['keys']['block']} 总键 {st['total']} 行 {st['lines']}")

print(f"\n合计新增 {added}，未翻 {skipped_empty}，与原文相同 {same}，FAIL {len(fails)}")
for f in fails[:20]:
    print("FAIL", f)
sys.exit(0 if not fails else 1)
