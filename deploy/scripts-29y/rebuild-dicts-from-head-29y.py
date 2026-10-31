"""29y：从 HEAD 原文 + i18n/delta（+ 可选 i18n/packets）重建词典。

只读仓库内文件，不碰 ARTEX 目录、不碰 .tmp。
用法：
  python deploy/scripts-29y/rebuild-dicts-from-head-29y.py [--no-packets] [--dry-run] [lang ...]
    --dry-run  只算不写，打印行数/键数/sha256
"""
import hashlib, os, sys

HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, HERE)
import dict_io_29y as dio  # noqa: E402

NL = "\n".encode()


def main():
    sys.stdout.reconfigure(encoding="utf-8")
    args = [a for a in sys.argv[1:] if not a.startswith("--")]
    no_packets = "--no-packets" in sys.argv
    dry = "--dry-run" in sys.argv
    langs = args or dio.LANGS
    repo = dio.repo_root()
    for lg in langs:
        d, info = dio.build_dict(repo, lg, with_packets=not no_packets)
        raw = dio.render(d)
        lines = raw.count(NL)
        total = sum(len(v) for v in d.values())
        sha = hashlib.sha256(raw).hexdigest()
        act = "算" if dry else "写"
        if not dry:
            st = dio.write_dict(repo, lg, d)
            lines, total = st["lines"], st["total"]
        print(f"  {lg:6} {act} 追加 {info['add']:4} 改旧 {info['mod']:2} "
              f"包新增 {info.get('pkt_add', 0):3} 总键 {total:5} 行 {lines:6} sha256 {sha}")


if __name__ == "__main__":
    main()
