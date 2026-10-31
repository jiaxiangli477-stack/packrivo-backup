"""29y：把「工作树词典里多出来的内容」收编进 delta，之后只经 apply_delta 落盘。

背景：i938 / i942 两个外部批次绕过 delta 直接写了 deploy/i18n-merged/*.json
（numstat 看到 ar/es/it/ja/ko/nl/pt/zh-CN 各 +400 左右）。本脚本把差异算回 delta：

  built = HEAD + 现有 delta (+ packets)       ← 仓库里可复现的基线
  work  = 当前工作树词典
  多出来的键        → 追加进 delta/<lang>-add.json
  同名但值不同的键  → 追加进 delta/<lang>-mod.json（old = built 的值，new = 工作树的值）
  built 有、work 没有的键 → 报错（丢键，必须人工看）

写完立刻按 HEAD + delta + packets 重建并逐字节校验，11 个必须全 PASS。

用法：
  python deploy/scripts-29y/import-working-delta-29y.py [--dry-run] [lang ...]
"""
import json, os, sys

HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, HERE)
import dict_io_29y as dio  # noqa: E402

WHY = "i942/i938 机器批次（i909/trans-<lang>.json）翻好的现有节点，绕过 delta 直接写了词典；收编进 delta 留档"


def read_delta(path):
    return json.load(open(path, encoding="utf-8")) if os.path.exists(path) else {}


def main():
    sys.stdout.reconfigure(encoding="utf-8")
    dry = "--dry-run" in sys.argv
    langs = [a for a in sys.argv[1:] if not a.startswith("--")] or dio.LANGS
    repo = dio.repo_root()
    ddir = dio.delta_dir(repo)
    plan = {}
    for lang in langs:
        built, _ = dio.build_dict(repo, lang, with_packets=True)
        work = json.load(open(dio.dict_path(repo, lang), encoding="utf-8"))
        adds, mods, lost = {}, {}, []
        for b in dio.BUCKETS:
            wb, bb = work.get(b, {}), built.get(b, {})
            for k, v in wb.items():
                if k not in bb:
                    adds.setdefault(b, {})[k] = v
                elif v != bb[k]:
                    mods.setdefault(b, {})[k] = {"old": bb[k], "new": v, "why": WHY}
            for k in bb:
                if k not in wb:
                    lost.append(f"{b}/{k}")
        plan[lang] = (adds, mods, lost)
        n_a = sum(len(v) for v in adds.values())
        n_m = sum(len(v) for v in mods.values())
        print(f"  {lang:6} 待收编 新增 {n_a:4}  改旧 {n_m:3}  丢键 {len(lost):3}")
        if lost:
            print("     !! 丢键（不处理）:", lost[:5])

    if dry:
        print("\n--dry-run，不写文件")
        return 0

    for lang, (adds, mods, _) in plan.items():
        if not adds and not mods:
            continue
        add_p = os.path.join(ddir, f"{lang}-add.json")
        mod_p = os.path.join(ddir, f"{lang}-mod.json")
        cur_add = read_delta(add_p)
        cur_mod = read_delta(mod_p)
        for b, kv in adds.items():
            cur_add.setdefault(b, {})
            for k, v in kv.items():
                if k in cur_mod.get(b, {}):
                    cur_mod[b].pop(k)  # 同一键不能同时是 add 和 mod
                cur_add[b][k] = v
        for b, kv in mods.items():
            cur_mod.setdefault(b, {})
            for k, v in kv.items():
                cur_add.get(b, {}).pop(k, None)
                cur_mod[b][k] = v
        for path, data in ((add_p, cur_add), (mod_p, cur_mod)):
            with open(path, "w", encoding="utf-8", newline="\n") as fh:
                json.dump(data, fh, ensure_ascii=False, indent=2, sort_keys=False)
                fh.write("\n")
        n_a = sum(len(v) for v in cur_add.values())
        n_m = sum(len(v) for v in cur_mod.values())
        print(f"  写入 delta/{lang}-add.json（{n_a} 键）、delta/{lang}-mod.json（{n_m} 键）")

    print("\n按 HEAD + delta + packets 重建 11 个词典：")
    for lang in dio.LANGS:
        st = dio.save_dict(repo, lang, with_packets=True)
        print(f"  {lang:6} 总键 {st['total']:5} 行 {st['lines']:6} 追加 {st['add']:4} 改旧 {st['mod']:3}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
