# RUNBOOK · 29y / 31a / 31b 合并代码阶段（R1）

本文件是 R1 附录 E 的代码阶段；历史单机演练说明可从 Git 历史读取，不得执行旧的固定路径、`php` 容器名或 `git checkout`。R1 包的主题同时包含 29y、31a、31b 及 32a 代码；因此代码一次安装，31b 分类与 32a 数据迁移随后按附录 E 明确顺序执行。维护窗口内禁止对外开放。

**生产宿主，站长在单独授权后执行。** E.0 指纹、包 `MANIFEST.sha256`、11 语言闸门及 E.1 完整备份必须已通过；32a 工作区单字段备份须在替换任何插件前完成并确认宿主与容器哈希一致。`PKG`、`BACKUP`、`WP_CONTAINER`、`DB_CONTAINER`、`WPC`、`DB_NAME` 已由站长当天核对，`MANIFEST_SHA` 为独立渠道确认的清单哈希，不能从未验证包自行决定。`BACKUP_SHA`、`WORKSPACE_SHA` 分别是本轮备份 `backup.json` 和工作区 CSV 的哈希。

```sh
python3 "$PKG/r1-stage-release.py" \
  --pkg "$PKG" --stage "$BACKUP/r1-release-stage" \
  --wp-container "$WP_CONTAINER" --db-container "$DB_CONTAINER" \
  --wp-root "$WPC" --db-name "$DB_NAME" \
  --backup-dir "$BACKUP/r1-snapshot" --backup-sha256 "$BACKUP_SHA" \
  --workspace-backup "$BACKUP/32a-workspace-moq.csv" --workspace-sha256 "$WORKSPACE_SHA" \
  --manifest-sha256 "$MANIFEST_SHA" || { echo 'STOP stage failed'; exit 1; }
```

⛳ 必须看到全部 `BASELINE_MATCH`、三包 `STAGED_ZIP`、三组件安装、`INSTALLED_DICTIONARIES 11` 和 `STAGE_RELEASE_PASS`。脚本在写入前核对包及备份哈希、维护模式、S0 文件指纹、29y i18n 路由器哈希，并检查 ZIP 路径；任何冲突停止。它用经 Git blob 出包的 4.0.7 子主题、1.0.2 报价插件、1.0.10 语言选择器，复制 29y privacy、31b route、32a 404 mu 文件及 11 份字典；不覆盖 `paths.json`，不运行供应商图片生成或写其他 uploads。

本阶段局部失败或需要回滚：保持上游封闭，使用附录 E 的 `r1-snapshot.py restore` 恢复 E.1 全库及文件，再核对 S0；不得仅删除新主题或插件。代码安装后按 E 的 31b 分类、32a 旧文件守卫、工作区、选项、页面、博客、R1 三字段及 llms 顺序继续。首次与第二次页面响应以及多语言、sitemap、红线验收均通过前不得离开维护窗口。
