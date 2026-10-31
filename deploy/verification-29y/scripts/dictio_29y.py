"""29y 词典唯一写入口。

以前词典有三处各写各的：apply-translation-packets 写 indent=2、rebuild 写 indent=2、
i909/dict-add-29y-v2 写 separators=(',',':')（单行）。最后写win的那次决定文件形态，
于是 it-dict.json 被写成单行 1.5MB、git diff 变成 +1/-7742，评审看不了真实改动。

现在所有落盘都走这里：
  - 以 HEAD 里的原文为底（桶序 title→attr→block→text 不被重排）
  - indent=2 / sort_keys=False / newline="\\n"
  - 写完自检：无 BOM、无 CRLF、行数不少于 HEAD、键数不少于 HEAD

对外三个函数：
  head_dict(repo, lang)                 读 HEAD 版本（不存在则返回空骨架）
  write_dict(repo, lang, data)          直接写（data 由调用方给出，通常来自 head_dict）
  mutate_and_write(repo, lang, mutator) 以 HEAD 为底，交给 mutator 改，再写

用法示例：
  from dictio_29y import mutate_and_write
  stats = mutate_and_write(repo, "it", lambda d: d["block"].update({"...": "..."}))
"""
import json, os, subprocess

BUCKETS = ("title", "attr", "block", "text")
REL = "deploy/i18n-merged/%s-dict.json"


class DictWriteError(RuntimeError):
    pass


def _rel(lang):
    return REL % lang


def head_dict(repo, lang):
    rel = _rel(lang)
    r = subprocess.run(["git", "show", f"HEAD:{rel}"], cwd=repo, capture_output=True)
    if r.returncode != 0:
        return {b: {} for b in BUCKETS}
    return json.loads(r.stdout.decode("utf-8"))


def head_line_count(repo, lang):
    rel = _rel(lang)
    r = subprocess.run(["git", "show", f"HEAD:{rel}"], cwd=repo, capture_output=True)
    return 0 if r.returncode != 0 else r.stdout.count(b"\n")


def write_dict(repo, lang, data, expect_head_base=True):
    """写词典。expect_head_base=True 时校验「行数/键数不少于 HEAD」，防单行重写。"""
    path = os.path.join(repo, _rel(lang))
    for b in BUCKETS:
        data.setdefault(b, {})
    ordered = {b: data[b] for b in BUCKETS if b in data}
    for b in data:
        if b not in ordered:
            ordered[b] = data[b]
    with open(path, "w", encoding="utf-8", newline="\n") as fh:
        json.dump(ordered, fh, ensure_ascii=False, indent=2, sort_keys=False)
        fh.write("\n")

    raw = open(path, "rb").read()
    problems = []
    if raw.startswith(b"\xef\xbb\xbf"):
        problems.append("写出后带 BOM")
    if b"\r\n" in raw:
        problems.append("写出后是 CRLF")
    lines = raw.count(b"\n")
    if expect_head_base:
        hl = head_line_count(repo, lang)
        if hl and lines < hl:
            problems.append(f"行数 {lines} 少于 HEAD 的 {hl}（疑似被压成单行）")
        released = json.loads(raw.decode("utf-8"))
        head_keys = sum(len(v) for v in head_dict(repo, lang).values())
        now_keys = sum(len(v) for v in released.values())
        if head_keys and now_keys < head_keys:
            problems.append(f"键数 {now_keys} 少于 HEAD 的 {head_keys}（丢键）")
    if problems:
        raise DictWriteError(f"{lang}: " + "；".join(problems))
    return {"lang": lang, "lines": lines, "bytes": len(raw),
            "keys": {b: len(ordered[b]) for b in ordered},
            "total": sum(len(v) for v in ordered.values())}


def mutate_and_write(repo, lang, mutator):
    d = head_dict(repo, lang)
    for b in BUCKETS:
        d.setdefault(b, {})
    mutator(d)
    return write_dict(repo, lang, d)


def apply_delta(d, delta_dir, lang):
    """把 deploy/verification-29y/i18n/delta/<lang>-{add,mod}.json 叠到词典上。

    新增键追加到桶尾（不重排已有顺序），被改旧键原地替换。
    返回 (追加数, 改旧数)。
    """
    add_f = os.path.join(delta_dir, f"{lang}-add.json")
    mod_f = os.path.join(delta_dir, f"{lang}-mod.json")
    add = json.load(open(add_f, encoding="utf-8")) if os.path.exists(add_f) else {}
    mod = json.load(open(mod_f, encoding="utf-8")) if os.path.exists(mod_f) else {}
    n_add = n_mod = 0
    for bucket in BUCKETS:
        if bucket not in d and (bucket in add or bucket in mod):
            d[bucket] = {}
        for k, v in mod.get(bucket, {}).items():
            if k not in d[bucket]:
                raise DictWriteError(f"{lang}/{bucket} 要改的键不存在：{k[:50]!r}")
            d[bucket][k] = v["new"]
            n_mod += 1
        for k, v in add.get(bucket, {}).items():
            if k in d[bucket]:
                raise DictWriteError(f"{lang}/{bucket} 新增键已存在：{k[:50]!r}")
            d[bucket][k] = v
            n_add += 1
    return n_add, n_mod


if __name__ == "__main__":
    import sys
    sys.stdout.reconfigure(encoding="utf-8")
    repo = sys.argv[1] if len(sys.argv) > 1 else r"E:\破甲工作区\packrivo-backup"
    for lg in ("ar", "de", "es", "fr", "it", "ja", "ko", "nl", "pt", "zh-CN", "zh-TW"):
        st = write_dict(repo, lg, head_dict(repo, lg))
        print(f"  {lg:6} 行 {st['lines']:6} 字节 {st['bytes']:8} 总键 {st['total']}")
