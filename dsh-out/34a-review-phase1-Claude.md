# 34a 第一段复核结论（Claude，2026-11-04）

**结论：有条件通过。先修完下面 4 处，然后直接做第二段，不用再等复核。** 第二段做完再停下汇报。

## 我核实过的部分
- 词库共 7,629 个词，全部去重；分成 37 个簇；另外丢弃了 4,070 个词，每个都写了原因。很好。
- 有搜索量的词 573 个（7.5%）。其中 527 个和 Semrush 原文件逐条比对，只有 1 处不一致（见下面第 2 条）。
- near me、etsy、amazon、uline、DIY、家具橱柜抽屉这类词都已剔除。烟草、电子烟、CBD 的词已标成 N，处理正确。
- 问句表 407 条，可以用。

## 必须修的 4 处
1. **Semrush Topics 的数字不是搜索量。** 关键词魔术工具左侧"主题"栏的数字，是这个主题下有多少个关键词，不是每月搜索量（这是 Claude 原始文件没标清楚，现已补上说明）。
   所以，凡是"搜索量数据源 = Semrush Topics"的行（约 42 行，例如 packaging industry and suppliers 3900、wholesale gift boxes 3000、candle packaging and boxes 2200、kraft paper boxes 2200、rigid box manufacturing 1300），"搜索量"一律清空，备注里写"Semrush 主题内含 N 个关键词"。
2. **gable boxes**：945 是主题内的关键词数，真实搜索量是 1,900（来自 kmt-custom-jewelry-boxes-volume.tsv）。改成 1900，数据源写 Semrush KMT。
3. **意图要分出来源。** 新增一列"意图来源"，只能填 semrush 或 inferred。只有 Semrush 给了意图的行才填 semrush，其余一律 inferred。
   另外，inferred 的行不要轻易标 T（交易型）：现在有 4,845 个词标了 T，太多了。只有带 buy / order / price / cost / wholesale / bulk / for sale / supplier 这类词的才标 T；只是品类名的（例如 mailer boxes），标 C。
4. **noissue custom tissue paper、noissue dieline** 是竞品品牌词，移到 dropped，原因写"竞品品牌词"。

## 修完后直接做第二段
- 按 34a 指令的第 6、7 步来做。
- 主词优先用 00-机会清单-Claude.md 里的词；第一批先改 GSC 平均排名在 20 名以内的商业页。
- 选主词时，搜索量只能看 Semrush KMT 的真实数值，不能看主题数。
- 第二段做完后停下，汇报第二段的验收结果（title、meta 长度统计；主词去重；红线词 grep 结果）。
