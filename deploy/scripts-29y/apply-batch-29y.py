"""29y 批次入口：把某几个语种的 delta/packets 落盘，然后**强制**跑 11 个词典的逐字节校验，
不通过就停在这里，不允许进下一批。

流程（每一步都打印 PASS/FAIL）：
  1) 逐语种 HEAD + delta + packets 重建 → write_dict 落盘（写完回读比对）
  2) 11 个词典逐字节校验（verify），必须 11/11 PASS
  3) 原有译文被改清单（report-dict-mods），逐条留档
  4) 可选 --sync：把词典同步到演练站上传目录（路径取环境变量 REHEARSAL_UPLOADS）

用法：
  python deploy/scripts-29y/apply-batch-29y.py it fr
  python deploy/scripts-29y/apply-batch-29y.py nl --sync
退出码：任一判据 FAIL 即为 1。
"""
import os, shutil, subprocess, sys, hashlib

HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, HERE)
import dict_io_29y as dio  # noqa: E402


def main():
    sys.stdout.reconfigure(encoding="utf-8")
    args = [a for a in sys.argv[1:] if not a.startswith("--")]
    sync = "--sync" in sys.argv
    langs = args or dio.LANGS
    repo = dio.repo_root()
    bad = [l for l in langs if l not in dio.LANGS]
    if bad:
        print(f"FAIL 未知语种：{bad}")
        return 1

    print(f"批次语种：{', '.join(langs)}")
    print("\n[1/3] 落盘")
    for lang in langs:
        try:
            st = dio.save_dict(repo, lang, with_packets=True)
        except dio.DictError as exc:
            print(f"  {lang:6} FAIL {exc}")
            return 1
        print(f"  {lang:6} PASS 总键 {st['total']:5} 行 {st['lines']:6} "
              f"add {st['add']:4} mod {st['mod']:3}")

    print("\n[2/3] 11 个词典逐字节校验")
    if not dio.report_verify(repo):
        print("FAIL 校验没过，本批次到此为止（不许进下一批）")
        return 1

    print("\n[3/3] 原有译文被改清单")
    r = subprocess.run([sys.executable, os.path.join(HERE, "report-dict-mods-29y.py")],
                       cwd=repo, capture_output=True)
    out = r.stdout.decode("utf-8", "replace")
    print("\n".join([l for l in out.splitlines() if l.startswith("按语种") or l.startswith("结论")]))

    if sync:
        up = os.environ.get("REHEARSAL_UPLOADS", "")
        if not up or not os.path.isdir(up):
            print(f"FAIL --sync 需要 REHEARSAL_UPLOADS 指向演练站上传目录，当前：{up!r}")
            return 1
        for lang in langs:
            src = dio.dict_path(repo, lang)
            dst = os.path.join(up, f"{lang}-dict.json")
            shutil.copyfile(src, dst)
            h1 = hashlib.md5(open(src, "rb").read()).hexdigest()
            h2 = hashlib.md5(open(dst, "rb").read()).hexdigest()
            print(f"  sync {lang:6} {'PASS' if h1 == h2 else 'FAIL'} md5 {h1[:12]}")
            if h1 != h2:
                return 1

    print("\n结论：PASS 本批次完成（11/11 一致）")
    return 0


if __name__ == "__main__":
    sys.exit(main())
