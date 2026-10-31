"""29y：把 delta 重整成「严格等于 工作树 − HEAD」。

外部批次（i938/i942）绕开 delta 直接写词典，delta 与工作树脱节。
本脚本以 HEAD 为基准逐键重算 delta：
  工作树有、HEAD 没有        → add（原样收录，包含翻译包已灌的键）
  两边都有但值不同           → mod（old 取 HEAD 值，new 取工作树值；why 沿用旧 delta，没有则记机器批次）
  HEAD 有、工作树没有        → 报错（丢键，必须人工看）

注意：mod 必须在 add 之前生效（apply_delta 的顺序），所以按 HEAD 归属分类是唯一正确划分 ——
由 add 引入的键即使后来被改，也只能改 add 里的值，不能挪进 mod（HEAD 里没有这个键）。

用法：
  python deploy/scripts-29y/rebuild-delta-from-work-29y.py [--dry-run] [lang ...]
"""
import json, os, sys

HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, HERE)
import dict_io_29y as dio  # noqa: E402

MACHINE_WHY = "i938/i942 机器批次翻好的现有节点，绕过 delta 直接写了词典，本次收编进 delta 留档"


def load(p):
    return json.load(open(p, encoding="utf-8")) if os.path.exists(p) else {}


def main():
    sys.stdout.reconfigure(encoding="utf-8")
    dry = "--dry-run" in sys.argv
    langs = [a for a in sys.argv[1:] if not a.startswith("--")] or dio.LANGS
    repo = dio.repo_root()
    ddir = dio.delta_dir(repo)
    total = {"add": 0, "mod": 0}
    for lang in langs:
        head = dio.head_dict(repo, lang)
        for b in dio.BUCKETS:
            head.setdefault(b, {})
        work = json.load(open(dio.dict_path(repo, lang), encoding="utf-8"))
        old_mod = load(os.path.join(ddir, f"{lang}-mod.json"))
        add, mod, lost = {}, {}, []
        for b in dio.BUCKETS:
            hb, wb = head[b], work.get(b, {})
            for k, v in wb.items():
                if k not in hb:
                    add.setdefault(b, {})[k] = v
                elif v != hb[k]:
                    why = old_mod.get(b, {}).get(k, {}).get("why") or MACHINE_WHY
                    mod.setdefault(b, {})[k] = {"old": hb[k], "new": v, "why": why}
            for k in hb:
                if k not in wb:
                    lost.append(f"{b}/{k[:40]}")
        n_a = sum(len(v) for v in add.values())
        n_m = sum(len(v) for v in mod.values())
        total["add"] += n_a
        total["mod"] += n_m
        print(f"  {lang:6} add {n_a:4}  mod {n_m:3}  丢键 {len(lost):3}")
        if lost:
            print("     !! 丢键:", lost[:4])
        if dry:
            continue
        for name, data in ((f"{lang}-add.json", add), (f"{lang}-mod.json", mod)):
            p = os.path.join(ddir, name)
            with open(p, "w", encoding="utf-8", newline="\n") as fh:
                json.dump(data, fh, ensure_ascii=False, indent=2, sort_keys=False)
                fh.write("\n")
    print(f"合计 add {total['add']} 键、mod {total['mod']} 键" + ("（dry-run 未写）" if dry else ""))
    if dry:
        return 0
    print("\n按 HEAD + delta + packets 重建 11 个词典：")
    for lang in dio.LANGS:
        st = dio.save_dict(repo, lang, with_packets=True)
        print(f"  {lang:6} 总键 {st['total']:5} 行 {st['lines']:6} add {st['add']:4} mod {st['mod']:3}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
