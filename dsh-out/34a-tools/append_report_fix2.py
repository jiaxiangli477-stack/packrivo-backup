# -*- coding: utf-8 -*-
"""追加 34a-译文-2 章节到报告尾部（幂等：已存在则跳过）。"""
import io

REPORT = r'E:\破甲工作区\packrivo-backup-34a\dsh-out\34a-i18n-report.md'
HEAD = '## 10. 34a-译文-2（协调费新句 + 首饰袋口径）'

SEC = HEAD + u'''

本轮在分支 `34a`（基线 `a41c89f`）上做三件事，仍然只动 `dsh-out/` 下的 CSV、本文档与 `dsh-out/34a-tools/`；`deploy/i18n-merged/`、容器、数据库一律未碰。

复算后的交付标识：

| 项 | 值 |
| --- | --- |
| 字节 | 725361（上一版 726349，-988） |
| SHA-256 | `0a38ec55949af50deeb57827d6eff1ea4f58a9ea35de2731dc38faaf32640f5d` |
| 编码 | UTF-8 无 BOM（首字节 `65 6E 2C` = `en,`），`LF` 3708，`CR` 0 |
| 行数 / 键数 | 3707 = 337 × 11，仍是 337 个键 |

### 10.1 新增英文段 `8-15% coordination fee, agreed in writing before work starts.`

11 语言各插一条 SEG（`lang/<lang>.json` 的 `SEG` 176 → 177），插在旧段 `8-15% coordination fee on production only.` 之后：

| 语言 | 译文 |
| --- | --- |
| ar | `رسم تنسيق 8-15%، يُتفق عليه كتابيًا قبل بدء العمل.` |
| de | `8-15 % Koordinationsgebühr, vor Arbeitsbeginn schriftlich vereinbart.` |
| es | `Comisión de coordinación del 8-15 %, acordada por escrito antes de empezar el trabajo.` |
| fr | `8-15 % de frais de coordination, convenus par écrit avant le début des travaux.` |
| it | `Commissione di coordinamento dell'8-15%, concordata per iscritto prima dell'inizio dei lavori.` |
| ja | `コーディネーション費8-15%、着工前に書面で合意。` |
| ko | `조정 수수료 8-15%, 작업 시작 전에 서면으로 합의합니다.` |
| nl | `8-15% coördinatievergoeding, schriftelijk overeengekomen vóór aanvang van het werk.` |
| pt | `Taxa de coordenação de 8-15%, acordada por escrito antes do início dos trabalhos.` |
| zh-CN | `8-15% 采购协调费，开工前书面确认。` |
| zh-TW | `8-15% 採購協調費，開工前書面確認。` |

口径：`8-15` 一律 ASCII 连字符（`checktoken.py` 的 `BAD_DASH` 会拒 `〜 ～ – — ‒ −`）；数字集合 `8`、`15` 与英文一致；不含 `MOQ`/`DDP`/`FOB`/`Packrivo` 等硬令牌，所以不触发令牌校验；`de` 沿用 Sie 与 `8-15 %`（数字与百分号间留空格，与该语种既有词典一致），`es`/`fr` 同；`ar` 用 MSA 的 `رسم تنسيق`，与词典既有 `رسوم تنسيق 8-15%` 同词根；`zh-CN`/`zh-TW` 用「采购协调费 / 採購協調費」，与词典既有术语一致。

### 10.2 四条 meta 换成新句

旧键（各 11 行、共 44 行）整条删除，新键（各 11 行、共 44 行）以 `bucket=attr`、`origin=new` 写入。旧键里含有 `No retainer before scope.` 与 `8-15% coordination fee on production only.`，新键两条都不含。

| URL | 新英文键（head + 新中段 + 共享尾） |
| --- | --- |
| `/services/` | `Custom packaging sourcing. Then a quote.` + `8-15% coordination fee, agreed in writing before work starts.` + `Request a quote.` |
| `/guides/` | `Custom packaging guide.` + 新中段 + `Dieline with every quote. Request a quote.` |
| `/inquiry/` | `Custom packaging quote request.` + 新中段 + `Dieline with every quote. Request a quote.` |
| `/customization/` | `Custom packaging finishes.` + 新中段 + `Dieline with every quote. Request a quote.` |

head 与 tail 都是上一版就有的段（head 译文不变，tail 即第 5 节列过的一致性句尾），所以这 44 行的变化精确等于「旧中段换成新中段」这一处替换。落地样例（de 与 zh-CN 四条全列）：

- `/services/` de：`Beschaffung individueller Verpackungen. Dann ein Angebot. 8-15 % Koordinationsgebühr, vor Arbeitsbeginn schriftlich vereinbart. Fordern Sie ein Angebot an.`
- `/guides/` de：`Leitfaden für individuelle Verpackungen. 8-15 % Koordinationsgebühr, vor Arbeitsbeginn schriftlich vereinbart. Stanzkontur zu jedem Angebot. Fordern Sie ein Angebot an.`
- `/inquiry/` de：`Angebotsanfrage für individuelle Verpackungen. 8-15 % Koordinationsgebühr, vor Arbeitsbeginn schriftlich vereinbart. Stanzkontur zu jedem Angebot. Fordern Sie ein Angebot an.`
- `/customization/` de：`Veredelungen für individuelle Verpackungen. 8-15 % Koordinationsgebühr, vor Arbeitsbeginn schriftlich vereinbart. Stanzkontur zu jedem Angebot. Fordern Sie ein Angebot an.`
- `/services/` zh-CN：`定制包装采购。然后是报价。8-15% 采购协调费，开工前书面确认。申请报价。`
- `/guides/` zh-CN：`定制包装指南。8-15% 采购协调费，开工前书面确认。每份报价均附刀模图。申请报价。`
- `/inquiry/` zh-CN：`定制包装报价申请。8-15% 采购协调费，开工前书面确认。每份报价均附刀模图。申请报价。`
- `/customization/` zh-CN：`定制包装工艺。8-15% 采购协调费，开工前书面确认。每份报价均附刀模图。申请报价。`

新句（61 字符）比旧的两句（76 字符）短，四条 meta 整体缩短；`attr` 不参与标题长度门，`title` 桶 715 行一字未动，第 6 节长度结论仍成立。

### 10.3 zh-CN 首饰语境：`pouches` 用「首饰袋」

先用 `audit_pouch.py` / `audit_pouch2.py` 在旧 CSV 与两份 `lang/*.json` 里穷举 `收纳` / `收納` 与所有含 `pouch` 的条目，逐条判：

| 语言 | 命中 | 处置 |
| --- | --- | --- |
| zh-CN | `收纳袋` 共 3 行 | 3 行全改（下表） |
| zh-TW | `收納袋` 共 0 行 | 无需改动；zh-TW 侧同一批 pouch 条目本来就用「首飾袋」/「束口袋」，已一致 |

zh-CN 三处改动：

| 桶 | 英文键 | 改前 | 改后 |
| --- | --- | --- | --- |
| attr | `Custom jewelry boxes, pouches and gift packaging with your logo. …`（首页 meta） | `定制首饰盒、收纳袋与礼品包装，可印 Logo。…` | `定制首饰盒、首饰袋与礼品包装，可印 Logo。…` |
| attr | `Packaging for jewelry brands: rigid boxes, pouches, gift bags, mailers and inserts, printed with your logo. …`（`/products/` meta） | `面向首饰品牌的包装：精装盒、收纳袋、礼品袋、邮寄盒与内衬，可印 Logo。…` | `面向首饰品牌的包装：精装盒、首饰袋、礼品袋、邮寄盒与内衬，可印 Logo。…` |
| title | `Custom Bags with Logo: Paper, Cotton & Pouches \\| Packrivo` | `定制带 Logo 袋类：纸袋、棉袋与收纳袋 \\| Packrivo` | `定制带 Logo 袋类：纸袋、棉袋与首饰袋 \\| Packrivo` |

判定依据：三处的 `pouches` 都与 `jewelry` 同句出现，指的是绒布/无纺布首饰袋，不是收纳用途的袋子；站点其它已正确的同类条目（`Custom jewelry pouches.` → `定制首饰袋`、`Custom Jewelry Pouches, Textile` → `布艺定制首饰袋`、`/bags-pouches/` meta → `定制首饰袋、礼品袋与邮寄袋…`）用的就是「首饰袋」，同站同词要一致。非首饰用途的 pouch 保持原译不动：`开窗牛皮纸自立袋`、`牛皮纸自立袋`、`定制文件窗口袋`、`文件窗口袋，刀模图先行`、`拉链布艺袋`、`定制拉链布艺袋，用于首饰与礼品`。改后 zh-CN 全表 `收纳袋` 0 行、`首饰袋` 9 行；zh-TW `收納袋` 0 行、`首飾袋` 8 行。标题长度不变（`定制带 Logo 袋类：纸袋、棉袋与首饰袋 | Packrivo` 仍 32 字符）。

### 10.4 本轮自检（原始输出）

```
rows=3707
unique_keys=337
per_lang: ar=337 de=337 es=337 fr=337 it=337 ja=337 ko=337 nl=337 pt=337 zh-CN=337 zh-TW=337
langs_all_337=True
empty_values=0
digit_mismatch=0
en_contains_retainer=0
en_contains_production_only=0
en_contains_old_segment=0
dup_triples=0
buckets: Counter({'attr': 1529, 'text': 1452, 'title': 715, 'block': 11})
origins: Counter({'new': 3696, 'reused': 11})
zh-CN 收纳袋=0 首饰袋=9
zh-TW 收納袋=0 首飾袋=8
```

```
merged_rows=3707 auth_swapped=7 fix2_swapped=5 dropped_rows=3
A_real_csv csv_rows=350 unique_keys=339
A_real_csv GATE FAIL missing_entries=121 errors=0
B_auth_csv csv_rows=350 unique_keys=339
B_auth_csv GATE FAIL missing_entries=66 errors=0
B2_fix2_csv csv_rows=350 unique_keys=339
B2_fix2_csv GATE FAIL missing_entries=22 errors=0
C_fix2_scoped csv_rows=347 unique_keys=337
C_fix2_scoped GATE PASS missing_entries=0 errors=0
C_fix2_scoped per-lang ar=0 de=0 es=0 fr=0 it=0 ja=0 ko=0 nl=0 pt=0 zh-CN=0 zh-TW=0
```

```
##### ar  errors=0 warnings=0   ##### de  errors=0 warnings=0   ##### es  errors=0 warnings=0
##### fr  errors=0 warnings=0   ##### it  errors=0 warnings=0   ##### ja  errors=0 warnings=0
##### ko  errors=0 warnings=0   ##### nl  errors=0 warnings=0   ##### pt  errors=0 warnings=0
##### zh-CN  errors=0 warnings=0  ##### zh-TW  errors=0 warnings=0  total_errors=0
total_token_errors=0
```

C 组跑的就是「34a-3 会把 `34a-new-strings.csv` 改成的那版」（5 条权威 meta + 4 条新协调费 meta + 去掉本批不译的 2 处），`unique_keys=337`、每语言 `missing=0`、`GATE PASS`，与交付 CSV 的键集一一对应。

### 10.5 未做与边界（本轮）

- `deploy/i18n-merged/*-dict.json` 只读校验，未写；容器与数据库未碰。
- `dsh-out/34a-keyword-map.csv`、`dsh-out/34a-new-strings.csv` 未改（`34a-new-strings.csv` 的 4 条英文旧句要等 Codex 34a-3 换）。
- `lang/*.json` 里旧的两段 `No retainer before scope.` 与 `8-15% coordination fee on production only.` 保留未删：已确认 337 个键无一再引用（CSV 里 `retainer` / `production only` 命中 0），留着是为了让 `keys.json['segments']`（177 条）与 11 份 lang 文件保持同构、`verify_langs.py` 仍能覆盖它们；是否清理由 Codex 重建词典时一并决定。
- 首页 title `Custom Packaging for Jewelry, Candles & Gifts | Packrivo` 仍未进本批（指令要求保持原文）；11 份词典的 `title` 桶里都没有它的译文，部署后该行仍为空，这条缺口与上一版相同，等词典重建一起定。
- 本轮新增脚本：`dsh-out/34a-tools/fix2_apply.py`（插段 + 换键 + zh-CN 改词，幂等）、`check_fix2.py`（本节 10.4 的自检）、`audit_pouch.py` / `audit_pouch2.py`（首饰袋穷举）；`compose.py`、`gate_sim.py` 增加了 fix-2 覆盖逻辑，重跑命令不变。

工作台 #35
'''


def main():
    cur = io.open(REPORT, encoding='utf-8').read()
    if HEAD in cur:
        print('already appended')
        return 0
    io.open(REPORT, 'a', encoding='utf-8', newline='\n').write(SEC)
    print('appended, bytes now=%d' % len(io.open(REPORT, 'rb').read()))
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
