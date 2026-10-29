# 29x 首页 Hero 大图候选（3 张）

意图 829 核验并交付。生成脚本：`E:/破甲工作区/.tmp/29x/make-hero-candidates.py`（中心方形裁切 + LANCZOS 缩放，q82 WebP，三档尺寸 1200/800/480）。
素材全部取自既有供应商实拍图库 `E:/破甲工作区/audit-parkrivo/codex-output/`（不修改字典、不修改 zip、不动数据库）。

## 候选清单

| # | 文件（1200 档，另附 800/480） | 尺寸/字节 | SHA256（1200 档前 16） | 素材来源 |
|---|---|---|---|---|
| 1 | `deploy/verification-29x/hero-candidates/hero-candidate-1-rigid-magnetic-1200.webp` | 1200x1200 / 101592B | 257023e098a6b100 | `audit-parkrivo/codex-output/12-other/jewelry-packaging-box-200835-1200.webp` |
| 2 | `deploy/verification-29x/hero-candidates/hero-candidate-2-magnetic-gift-1200.webp` | 1200x1200 / 70910B | e86bf8c68589a099 | `audit-parkrivo/codex-output/05-gift-box/gift-box-O1CN018jZH-1200.webp` |
| 3 | `deploy/verification-29x/hero-candidates/hero-candidate-3-rigid-lid-base-1200.webp` | 1200x1200 / 95096B | e4719c97ed77c64b | `audit-parkrivo/codex-output/09-rigid-lid-base/rigid-lid-and-base-box-212049-1200.webp` |

每张候选各有 3 档：`-1200.webp`、`-800.webp`、`-480.webp`（480 档 12.8–20.0KB，适合移动端 LCP）。9 个文件均为合法 WebP（RIFF/WEBP 头 + 尺寸校验通过），`hero-candidates/sources.csv` 中 9 条 SHA256 与磁盘文件逐一比对全部一致（9/9）。

## 推荐理由

1. **hero-candidate-1-rigid-magnetic（磁吸翻盖珠宝盒，12-other 系列）**：主推。磁吸硬盒是站内最高档产品线，与首页“定制珠宝包装”定位一致；素材细节最丰富（1200 档 101KB 为三张中最大），居中方形裁切后主体完整，适合左侧压字排版。
2. **hero-candidate-2-magnetic-gift（磁吸礼盒，05-gift-box 系列）**：备选轮换。礼盒带节日/礼品场景广度，视觉更“暖”，适合季节活动轮播；主体居中、边缘干净，压字区留白好。
3. **hero-candidate-3-rigid-lid-base（天地盖硬盒，09-rigid-lid-base 系列）**：备选。天地盖是硬盒经典结构，几何轮廓清晰、适合细边框+文案悬浮的版式；与现有产品页（lid/base 关键词类目）呼应。

## 素材来源说明（uploads/ 侧排查）

- 演练库 `wp-content/uploads/2026/10/` 仅有 3 张历史图（`packrivo-27c-drawer`、`packrivo-27c-lid-base`、`packrivo-27e-insert` 各 1200/800 档），属 27c/27e 时代旧素材，尺寸与主题不符，未采用。
- `uploads/packrivo-i18n/` 为 i18n 词典目录（意图 828 正在使用），不是图片素材，未触碰。
- 结论：首页 hero 采用 codex-output 供应商实拍图库，3 张候选已生成并通过完整性校验。

## 使用方式（留给统一收尾意图）

正式启用时把所选 1200 档复制进子主题 `assets/img/`（如 `hero.webp`）并在 front-page.php 引用；当前仅交付候选与理由，不落地前端、不重打包。
