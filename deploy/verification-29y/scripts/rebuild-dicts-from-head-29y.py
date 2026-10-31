"""29y：以 HEAD 词典为底，叠加本轮增量（i18n/delta/<lang>-{add,mod}.json），重建 <lang>-dict.json。

为什么不就地改：本轮的批次脚本把文件重排过（indent=1 + sort_keys），i909 那边写的是单行
separators=(',',':')，整份文件都变了，git diff 看不出真正改了什么。这里固定成
「HEAD 原文顺序 + 桶尾追加新键 + 定点替换被改的旧键」，落盘统一走 dictio_29y.write_dict。

用法：
  python rebuild-dicts-from-head-29y.py <repo_root> [lang ...]
增量目录默认 <repo>/deploy/verification-29y/i18n/delta。
"""
import json, os, sys

HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, HERE)
from dictio_29y import head_dict, write_dict, apply_delta, BUCKETS  # noqa: E402


def main():
    sys.stdout.reconfigure(encoding="utf-8")
    repo = sys.argv[1]
    langs = sys.argv[2:] or ["ar", "de", "es", "fr", "it", "ja", "ko", "nl", "pt", "zh-CN", "zh-TW"]
    delta = os.environ.get("PKV29Y_DELTA") or os.path.join(
        repo, "deploy", "verification-29y", "i18n", "delta")
    for lg in langs:
        d = head_dict(repo, lg)
        for b in BUCKETS:
            d.setdefault(b, {})
        n_add, n_mod = apply_delta(d, delta, lg)
        st = write_dict(repo, lg, d)
        print(f"  {lg:6} 桶序 {list(d)}  追加 {n_add:4}  改旧 {n_mod:2}  总键 {st['total']}  行 {st['lines']}")


if __name__ == "__main__":
    main()
