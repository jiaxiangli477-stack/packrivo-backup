"""29y 验收：只用仓库内文件，从 HEAD 原文 + delta + packets 重建 11 个词典，
与工作树里的词典逐字节比对，打印 11 行 sha256 对照与逐条 PASS/FAIL。

delta 坏掉（键冲突 / 要改的键不在 HEAD）时**不再抛异常**，而是打成一行
`FAIL <原因>`，原因里带语种/桶/键，跑完仍输出完整汇总。

用法（在仓库根目录）：
  python deploy/scripts-29y/verify-dict-rebuild-29y.py [lang ...]
退出码：全部 PASS 才是 0。
"""
import os, sys

HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, HERE)
import dict_io_29y as dio  # noqa: E402


def main():
    sys.stdout.reconfigure(encoding="utf-8")
    langs = [a for a in sys.argv[1:] if not a.startswith("-")] or dio.LANGS
    repo = dio.repo_root()
    print(f"仓库根：{repo}")
    print(f"delta ：{os.path.relpath(dio.delta_dir(repo), repo)}")
    print(f"packet：{os.path.relpath(dio.packet_dir(repo), repo)}")
    ok = dio.report_verify(repo, langs)
    return 0 if ok else 1


if __name__ == "__main__":
    sys.exit(main())
