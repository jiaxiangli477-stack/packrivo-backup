# -*- coding: utf-8 -*-
"""Build dsh-out/33ab-i18n-report.md from plan.json, the delivered CSV and bt.py.

Everything in the report is derived from files on disk except the prose sections,
so the numbers cannot drift from the delivered CSV.
"""
import csv
import json
import os
import sys
from collections import Counter, OrderedDict

HERE = os.path.dirname(os.path.abspath(__file__))
OUT = os.path.abspath(os.path.join(HERE, '..'))
sys.path.insert(0, HERE)

LANGS = ["ar", "de", "es", "fr", "it", "ja", "ko", "nl", "pt", "zh-CN", "zh-TW"]

from bt import BT  # noqa: E402


def read_csv(path):
    with open(path, encoding='utf-8-sig', newline='') as f:
        return list(csv.DictReader(f))


def esc(s):
    return s.replace('|', '\\|')


def main():
    plan = json.load(open(os.path.join(HERE, 'plan.json'), encoding='utf-8'))
    rows = read_csv(os.path.join(OUT, '33ab-i18n.csv'))
    chk = open(os.path.join(HERE, 'check-out.txt'), encoding='utf-8').read().rstrip('\n')

    a = read_csv(os.path.join(OUT, '33a-new-strings.csv'))
    b = read_csv(os.path.join(OUT, '33b-new-strings.csv'))
    en_a = [r['英文原文'] for r in a]
    en_b = [r['英文原文'] for r in b]
    dup = [s for s in en_a if s in set(en_b)]

    # en -> (round, bucket, where, pages) as frozen in plan.json
    meta = OrderedDict()
    for r in plan:
        if r['en'] not in meta:
            meta[r['en']] = r
    reused = OrderedDict()
    for r in plan:
        if r['origin'] == 'reused' and r['en'] not in reused:
            reused[r['en']] = r

    value = {}
    for r in rows:
        value[(r['en'], r['lang'])] = r['value']

    per_lang = OrderedDict((l, Counter()) for l in LANGS)
    for r in rows:
        per_lang[r['lang']][r['origin']] += 1

    L = []
    w = L.append

    w('# 33a / 33b 新增英文字符串 · 11 语言译文（CSV 交付说明）')
    w('')
    w('交付物：`dsh-out/33ab-i18n.csv`（UTF-8，无 BOM，无附加列）与本文档 '
      '`dsh-out/33ab-i18n-report.md`。本任务只新增这两个文件与 `dsh-out/33ab-tools/` 下的脚本，'
      '词典 JSON、容器、数据库均未改动。')
    w('')
    w('## 1. 交付物与行数口径')
    w('')
    w('| 项 | 值 |')
    w('| --- | --- |')
    w('| 文件 | `dsh-out/33ab-i18n.csv` |')
    w('| 编码 | UTF-8（无 BOM），LF 换行，`,` 分隔，双引号包住含逗号字段 |')
    w('| 列序 | `round, en, bucket, lang, value, origin` |')
    w('| 数据行 | %d |' % len(rows))
    w('| 表头 + 数据 | %d 行 |' % (len(rows) + 1))
    w('| 唯一英文串 | %d |' % len(meta))
    w('| 语言 | %s（顺序固定） |' % ' '.join('`%s`' % l for l in LANGS))
    w('')
    w('**128 → 124 的口径差异。** 两个输入清单合计 43 + 85 = %d 条英文条目，'
      '其中 %d 条的英文在两个轮次里完全相同：%s。'
      '英文相同的条目只出一份译文（保留首条出现，`round` 记为首次出现的轮次），'
      '%d × 11 = %d 行；若按 128 × 11 计则为 %d 行。差额 44 行即这 4 条重复串的重复语言行。'
      % (len(a) + len(b), len(dup), '、'.join('`%s`' % s for s in dup), len(meta), len(rows), len(a + b) * 11))
    w('')
    w('**为什么英文相同也只出一份。** 词典查找是「整串精确匹配」，同一英文串在四个桶里命中的译文必须一致，'
      '出两份就会在下游合并时产生同一键两个值的冲突（`scripts/ui-28k/merge-i18n.mjs` 按桶键去重）。')
    w('')
    w('## 2. 自检原始输出')
    w('')
    w('`dsh-out/33ab-tools/build.py` 的完整 stdout，未经编辑：')
    w('')
    w('```')
    w(chk)
    w('```')
    w('')
    w('## 2.2 独立复核（`verify.py`，不复用 build.py 的计数器）')
    w('')
    vchk = open(os.path.join(HERE, 'verify-out.txt'), encoding='utf-8').read().rstrip('\n')
    w('```')
    w(vchk)
    w('```')
    w('')
    w('两点需要说明：`reused` 行的比对基准是 32a 词典原文件 '
      '（`packrivo-backup-32a/deploy/i18n-merged/<lang>-dict.json`），不是 `plan.json` 里的缓存值；'
      '`new` 行做反向检查——若某条被标 `new` 却在 32a 词典里有完全相同的值，脚本会报错，实测 0 例。')
    w('')
    w('## 3. 每种语言的 reused / new 计数')
    w('')
    w('| lang | new | reused | 合计 |')
    w('| --- | --- | --- | --- |')
    tot = Counter()
    for l in LANGS:
        c = per_lang[l]
        tot['new'] += c['new']
        tot['reused'] += c['reused']
        w('| %s | %d | %d | %d |' % (l, c['new'], c['reused'], c['new'] + c['reused']))
    w('| **合计** | **%d** | **%d** | **%d** |' % (tot['new'], tot['reused'], tot['new'] + tot['reused']))
    w('')
    w('`new` = 该语言下没有同桶同英文的现成译文，本轮新写；`reused` = 从 32a 词典原值直接搬用，未重译。')
    w('')
    w('## 4. 桶判定（bucket）')
    w('')
    w('### 4.1 规则')
    w('')
    w('| bucket | 判据 |')
    w('| --- | --- |')
    w('| `title` | 文档 `<title>` / 页面主标题。本轮新增串中没有命中该桶的条目。 |')
    w('| `attr` | 出现在 HTML 属性值里：`aria-label`、`alt`、`placeholder`、`title`、`value`、`content`。 |')
    w('| `block` | 成段通告：含 2 个以上句末标点，或单句 ≥70 字符，或多句说明。 |')
    w('| `text` | 其余可见文本：标题、标签、表格单元格、kicker、短注释、按钮/链接文字、JS 运行时提示。 |')
    w('')
    w('### 4.2 复用判定')
    w('')
    w('同英文 **且** 同桶 → `reused`。另有一条来自 `scripts/ui-28k/merge-i18n.mjs:22-26` 的机械事实：')
    w('')
    w('```js')
    w("const kinds = new Set(meta.kind === 'attr' ? ['attr'] : ['block', 'text']);")
    w('```')
    w('')
    w('非 attr 条目同时写入 `block` 与 `text` 两个桶，因此这两桶互为兄弟桶，'
      '同一英文串在 `block` 命中即等价于在 `text` 也能命中，按 `reused` 处理；'
      '`attr` 与 `title` 按类型隔离，跨类型一律 `new`，不借译文。')
    w('')
    w('### 4.3 逐串判定')
    w('')
    w('`展示位置` 直接取自两个输入清单的原始列，桶由该元素周围的标签与句子形态推断。')
    w('')
    w('| # | round | bucket | en | 展示位置 | 页面 |')
    w('| --- | --- | --- | --- | --- | --- |')
    for i, (en, m) in enumerate(meta.items(), 1):
        w('| %d | %s | `%s` | %s | %s | %s |'
          % (i, m['round'], m['bucket'], esc(en), esc(m['where']), esc(m['pages'])))
    w('')
    w('桶分布：`text` %d 串、`block` %d 串、`attr` %d 串、`title` 0 串。'
      % (Counter(m['bucket'] for m in meta.values())['text'],
         Counter(m['bucket'] for m in meta.values())['block'],
         Counter(m['bucket'] for m in meta.values())['attr']))
    w('')
    w('## 5. 复用清单（%d 串 × 11 = %d 行）' % (len(reused), len(reused) * 11))
    w('')
    w('| # | 桶 | 命中桶 | en | ar 原值 |')
    w('| --- | --- | --- | --- | --- |')
    for i, (en, m) in enumerate(reused.items(), 1):
        w('| %d | `%s` | `%s` | %s | %s |'
          % (i, m['bucket'], m['hit_bucket'] or m['bucket'], esc(en), esc(m['value'])))
    w('')
    sib = [en for en, m in reused.items() if m['hit_bucket'] and m['hit_bucket'] != m['bucket']]
    w('其中 %d 条的 `命中桶` 与分配桶不同，全部是 `block` → `text` 的兄弟桶命中（%s）；'
      '其余 %d 条为同桶直接命中（`text` %d 条、`attr` %d 条）。'
      % (len(sib), '、'.join('`%s`' % s for s in sib),
         len(reused) - len(sib),
         sum(1 for m in reused.values() if m['bucket'] == 'text' and m['hit_bucket'] == 'text'),
         sum(1 for m in reused.values() if m['bucket'] == 'attr')))
    w('')
    w('## 6. 新译回译核对（`new` 共 %d 串）' % (len(meta) - len(reused)))
    w('')
    w('回译用于无法直接阅读原文的语种：把落盘的译文再读回中文，与英文原意对齐。'
      '`zh-CN` 一栏的记录的是中文值本身的可读语义，用于确认没有为了通顺把事实改掉。')
    w('')
    new_ens = [en for en, m in meta.items() if m['origin'] == 'new']
    for lang, label in (('ar', '阿拉伯语'), ('ja', '日语'), ('zh-CN', '简体中文')):
        w('### 6.%d %s（%s）' % ({'ar': 1, 'ja': 2, 'zh-CN': 3}[lang], label, lang))
        w('')
        w('| # | en | 落盘译文 | 回译 |')
        w('| --- | --- | --- | --- |')
        for i, en in enumerate(new_ens, 1):
            w('| %d | %s | %s | %s |'
              % (i, esc(en), esc(value[(en, lang)]), esc(BT[en][{'ar': 0, 'ja': 1, 'zh-CN': 2}[lang]])))
        w('')
    w('## 7. 抽样对照（10 条英文 × 11 语言）')
    w('')
    sample = [
        'Sourcing fee %s',
        'Product images',
        'Scrollable price table',
        'Illustrative flat layout',
        'Illustrative only; not a production dieline.',
        'Waived',
        'Enter dimensions from 2 to 120 cm (one decimal place) before viewing an estimate.',
        'Freight & duties quoted per destination.',
        'Ask us to combine several items into one shipment; we confirm whether it works on the written quote.',
        'Configure this box →',
    ]
    w('| en | ' + ' | '.join(LANGS) + ' |')
    w('| --- | ' + ' | '.join('---' for _ in LANGS) + ' |')
    for en in sample:
        w('| %s | ' % esc(en) + ' | '.join(esc(value[(en, l)]) for l in LANGS) + ' |')
    w('')
    w('## 8. 术语沿用与偏差')
    w('')
    w('### 8.1 32a 已定术语（直接沿用，未改）')
    w('')
    w('| en | zh-CN | zh-TW | ja | de |')
    w('| --- | --- | --- | --- | --- |')
    for en in ['Sourcing fee', 'DDP or FOB', 'MOQ', 'Dieline', 'die setup waived', 'print setup waived']:
        try:
            w('| %s | %s | %s | %s | %s |'
              % (esc(en), esc(value[(en, 'zh-CN')]), esc(value[(en, 'zh-TW')]),
                 esc(value[(en, 'ja')]), esc(value[(en, 'de')])))
        except KeyError:
            w('| %s | — | — | — | — |' % esc(en))
    w('')
    w('`Sourcing fee` 在 zh-CN / zh-TW 统一为「采购协调费 / 採購協調費」，与 32a 一致；'
      '`DDP`、`FOB`、`MOQ` 保留拉丁缩写，不展开注释；`EVA`、`PU` 同理。')
    w('')
    w('### 8.2 已记录的偏差')
    w('')
    w('1. **按钮 / 链接文字归 `text` 而非 `attr`。** 32a 词典的 `attr` 桶里只有属性值'
      '（`Close image`、`Product concept image`、`Choose language`、`you@example.com`、'
      '`e.g. United Kingdom`、`Next step`），而 CTA 文本（`Order a sample`、`Home`、`Explore`）'
      '落在 `block` / `text`。本轮按同一口径处理，证据：'
      '`packrivo-wp/packrivo-home-v4/inc/33a-category-cards.php:82` 的 `aria-label` 进 `attr`，'
      '同文件 68 行的按钮文案进 `text`。')
    w('2. **阿拉伯语箭头方向。** `Configure this box →` 在 ar 里写作 `اضبط هذا الصندوق ←`，'
      'RTL 排版下视觉指向一致；占位符与数字不受影响。')
    w('3. **日语小数提示。** 英文 `(one decimal place)` 若直译为「小数第1位」，'
      '会引入英文原串没有的数字 `1`，破坏数字不变量；ja 写作 `（小数点以下も可）`，'
      '保留「可输入小数」的语义而不新增数字。')
    w('4. **`%s` 位置不变。** 每种语言的占位符序列与英文逐字节相同（`%s`、`%1$s`、`%2$s` 顺序不变），'
      '`%` 字符计数相同，`&amp;` 等实体与内联标签原样保留。自检输出第 2 节已给出 0 差异。')
    w('')
    w('## 9. 状态')
    w('')
    w('- 词典 JSON：**未改动**（`git status` 只显示新增文件）。')
    w('- 容器 / 数据库：**未触碰**。')
    w('- 交付脚本：`dsh-out/33ab-tools/`（`probe.py`、`dict_probe.py`、`classify.py`、`plan.py`、'
      '`tr1..tr4.py`、`bt.py`、`build.py`、`report.py`）。')
    w('- 提交：待审查，本轮只提交上述新增文件，提交信息 '
      '`wip: dsh 33a/33b translations csv (not yet reviewed)`。')

    path = os.path.join(OUT, '33ab-i18n-report.md')
    with open(path, 'w', encoding='utf-8', newline='\n') as f:
        f.write('\n'.join(L) + '\n')
    print('report written: %s' % path)
    print('lines: %d' % len(L))
    print('dup across rounds: %d %s' % (len(dup), dup))
    print('meta rows: %d  reused: %d  new: %d' % (len(meta), len(reused), len(new_ens)))


if __name__ == '__main__':
    main()
