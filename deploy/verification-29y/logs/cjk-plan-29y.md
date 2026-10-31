# i963 续接：ar/ja/ko/zh-CN/zh-TW 碎片键修复方案（dry-run 计划，待应用）

生成：意图 #963 会话（2026-10-30 14:5x 本地）。状态：方案文档 + 示例，未写入任何词典。

## 背景（与拉丁语系一致的机理）
残留拼接键在 ar/ja/ko/zh-CN/zh-TW 词典中【已存在译文】（preview-cjk.py 实测 67–68/68 命中），
页面仍留英文的根因与 fr 相同：walker 文本节点 ≠ 拼接键，插件 block 键永不命中。
修法同样为碎片键：EN 去掉前导前缀（The/A/品牌's），译文按语种 LEAD 规则切掉前导主语后作为碎片值。

## 逐语种译文观察（preview-cjk.py 输出）
- ar ：译文为完整干净句。例 "The distinguishes…" → "يميّز هذا بين الأداة المادية واستخدامها التقني والشروط التجارية الواجب تأكيدها. لا تقدّم هذه الصفحة أي تكلفة أدوات ثابتة أو جدول إنتاج."
- ja ：干净句，谓语在句末。例 → "これは物理的ツール、その技術的使用、および確認すべき商業条件を区別します。本ページには固定された工具料金や生産スケジュールは記載されていません。"
- ko ：译文内含字面 "The"（The는 …）。例 → "The는 이 별도 취급 질문을 설명합니다. 동일한 지지 요구 사항과 비교할 수 있는 다른 구조로 남아 있습니다."
- zh-CN：译文内含字面 "The "（"The 解释了这一单独处理问题…"）；或主语 "本页面"（"本页面区分实物工具…"）。
- zh-TW：译文干净，"此處區分實體工具…"、"此處說明這項獨立的取放問題…"。

## 每语种 LEAD 切除规则（草拟，应用时须逐条目 eyeball）
- ar ：strip r'^\s*(?:يميّز|يُبيّن|يوضّح|يشرح|يصف|يحدّد|يُحدّد)?\s*هذا\s+'（动词可保留，仅去 "هذا"）；
  更稳妥：strip r'^\s*يميّز\s+هذا\s+' → "بين الأداة المادية…"。
- ja ：strip r'^(?:これは|それは|この|その|こちらは|こちら)\s*(?:、)?\s*'
- ko ：strip r'^The\s*(?:는)?\s*'；以及 r'^이는\s*'。
- zh-CN：strip r'^(?:The|本页面|本页|此处)\s*'
- zh-TW：strip r'^此處\s*'

## molded-pulp 两段长文（Show the insert… / If the product has…）
按句号分割 EN 与译文（ja/zh 按 。、ar 按 。，ko 按 .），
前两句整句键；第三句 "The explains this separate handling question." 碎片键 = "explains this separate handling question."，
碎片值 = 第三句译文按 LEAD 规则去主语（ja "これは、"、ko "The는 "、zh-CN "The "、zh-TW "此處…" 若在句首、ar "هذا "）。
第四句 "remain a different construction…" 碎片键 = "remain a different construction to compare against the same support requirements."，
碎片值 = 第四句译文整句（ja/zh-TW 已为独立干净句）。

## guide-jewelry-insert-fit 71 词表单块
与 fr 同法：键/值做 ':' → ': ' 归一后整块添加（ar/ja/ko/zh 译文均已在词典）。

## compare 品牌标题（Packlane vs Packrivo 等 2 词）
查各语种词典是否已有译文；identity 则按 fr 模式补 manual 译文（品牌名保留，"vs"→语种对应词）。

## 应用步骤（下一会话，逐语种）
1. 备份 {lang}-dict.json → .pre-29y-frag.bak（MERGED 与演练各留对照）
2. 以【当前工作区】为底合并（严禁 git HEAD 重建；严禁全局短键 The/A）
3. json.load + 键数 + coverage + md5 前后对照
4. copyfile MERGED → 演练 UP（原子替换：临时文件 + os.replace）
5. i914/reset-opcache.bat
6. scan-residual.py --state 新文件 --langs {lang} --paths 受影响页 定向重扫 → 替换主 state/CSV 对应行
7. curl --noproxy "*" --resolve 固定演练地址 实测受影响页 ≤5 词（blog 除外），11 语种 about=2–4 / de/inquiry=3 / de/about=4 不回退

## 未解决项（no-translation，需 8787 网关 API 补译，通道已实测可用）
- es /product-reverse-tuck-retail-carton/："Review board grade and caliper independently of this end map. If a proposed insert changes…"
- it /services/："Start with your product, its use, the packed contents and the experience you want. List th…"
- nl /product-multi-tier-floor-gift-display/ ×3：'A comparison should use actual setup and refill work…'、'Ask the supplier to identify shelf and body joins…'、'Record visible observations after the agreed actions…'
- pt /product-double-wall-frame-tray-sleeve/："Label the tray's outside envelope separately from the visible frame opening…"
（完整清单在 apply 会话按最终主 CSV 重建）
