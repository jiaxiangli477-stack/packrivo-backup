"""29y 验收：只用仓库内文件，从 HEAD 原文 + delta + packets 重建 11 个词典，
与工作树里的词典逐字节比对，打印 11 行 sha256 对照与逐条 PASS/FAIL。

用法（在仓库根目录）：
  python deploy/scripts-29y/verify-dict-rebuild-29y.py
"""
import hashlib, os, sys

HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, HERE)
import dict_io_29y as dio  # noqa: E402


def main():
    sys.stdout.reconfigure(encoding="utf-8")
    repo = dio.repo_root()
    print(f"仓库根：{repo}")
    print(f"delta ：{os.path.relpath(dio.delta_dir(repo), repo)}")
    print(f"packet：{os.path.relpath(dio.packet_dir(repo), repo)}")
    print()
    print(f"{'语种':6} {'重建 sha256':16} {'工作树 sha256':16} {'字节':>9}  结果")
    n_pass = n_fail = 0
    for lg in dio.LANGS:
        d, info = dio.build_dict(repo, lg, with_packets=True)
        built = dio.render(d)
        work = open(dio.dict_path(repo, lg), "rb").read()
        b1 = hashlib.sha256(built).hexdigest()
        b2 = hashlib.sha256(work).hexdigest()
        same = built == work
        if same:
            n_pass += 1
            res = "PASS 逐字节一致"
        else:
            n_fail += 1
            res = f"FAIL 不一致（重建 {len(built)} B / 工作树 {len(work)} B）"
            first = next((i for i in range(min(len(built), len(work))) if built[i] != work[i]), None)
            if first is not None:
                res += f"，首个差异字节偏移 {first}"
        print(f"{lg:6} {b1[:16]} {b2[:16]} {len(work):9}  {res}")
    print()
    print(f"汇总：PASS {n_pass} / FAIL {n_fail}（共 {len(dio.LANGS)} 个词典）")
    print("结论：" + ("PASS 仓库内文件可独立复现全部 11 个词典" if n_fail == 0 else "FAIL 见上"))
    return 0 if n_fail == 0 else 1


if __name__ == "__main__":
    sys.exit(main())
