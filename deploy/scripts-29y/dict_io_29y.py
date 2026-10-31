"""29y 词典唯一读写模块（仓库版，只用仓库内相对路径）。

出处：原先散落在 E:\\Ai\\ARTEX-Portable\\app\\data\\tasks\\67\\{i909,i902} 下的批次脚本各自落盘
（apply 用 indent=1+sort_keys，dict-add 用 separators=(',',':')），谁最后写谁决定文件形态，
it-dict.json 一度被压成单行 1.5MB（git diff +1/-7742）。这里收成一个入口：

  head_dict(repo, lang)          读 HEAD 里的词典原文
  write_dict(repo, lang, data)   indent=2 / sort_keys=False / newline="\\n"，写完自检
  apply_delta(d, delta_dir, lang)  叠 i18n/delta/<lang>-{add,mod}.json
  apply_packets(d, packet_dir, lang) 叠 i18n/packets/<lang>-todo.json 里已填的译文
  build_dict(repo, lang, ...)    HEAD + delta + packets 一次算完（不落盘）
  save_dict(repo, lang, ...)     build_dict + write_dict

路径约定（相对仓库根，仓库根由本文件位置推出：<repo>/deploy/scripts-29y/dict_io_29y.py）：
  deploy/i18n-merged/<lang>-dict.json
  deploy/verification-29y/i18n/delta/<lang>-{add,mod}.json
  deploy/verification-29y/i18n/packets/<lang>-todo.json
  deploy/verification-29y/i18n/local-stop.json
不引用 ARTEX 目录，也不引用 .tmp。
"""
import hashlib, json, os, subprocess

BUCKETS = ("title", "attr", "block", "text")
LANGS = ["ar", "de", "es", "fr", "it", "ja", "ko", "nl", "pt", "zh-CN", "zh-TW"]


class DictError(RuntimeError):
    pass


def repo_root():
    return os.path.abspath(os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", ".."))


def dict_rel(lang):
    return os.path.join("deploy", "i18n-merged", f"{lang}-dict.json")


def dict_path(repo, lang):
    return os.path.join(repo, dict_rel(lang))


def delta_dir(repo):
    return os.path.join(repo, "deploy", "verification-29y", "i18n", "delta")


def packet_dir(repo):
    return os.path.join(repo, "deploy", "verification-29y", "i18n", "packets")


def local_stop_path(repo):
    return os.path.join(repo, "deploy", "verification-29y", "i18n", "local-stop.json")


def head_dict(repo, lang):
    r = subprocess.run(["git", "show", f"HEAD:{dict_rel(lang).replace(os.sep, '/')}"],
                       cwd=repo, capture_output=True)
    if r.returncode != 0:
        return {b: {} for b in BUCKETS}
    return json.loads(r.stdout.decode("utf-8"))


def head_bytes(repo, lang):
    r = subprocess.run(["git", "show", f"HEAD:{dict_rel(lang).replace(os.sep, '/')}"],
                       cwd=repo, capture_output=True)
    return r.stdout if r.returncode == 0 else b""


def apply_delta(d, ddir, lang):
    """新增键落桶尾，被改旧键原地替换。返回 (追加数, 改旧数, 改动明细)。"""
    add_f = os.path.join(ddir, f"{lang}-add.json")
    mod_f = os.path.join(ddir, f"{lang}-mod.json")
    add = json.load(open(add_f, encoding="utf-8")) if os.path.exists(add_f) else {}
    mod = json.load(open(mod_f, encoding="utf-8")) if os.path.exists(mod_f) else {}
    n_add = n_mod = 0
    mods = []
    for bucket in BUCKETS:
        if bucket not in d and (bucket in add or bucket in mod):
            d[bucket] = {}
        for k, v in mod.get(bucket, {}).items():
            if k not in d[bucket]:
                raise DictError(f"{lang}/{bucket} 要改的键不存在：{k[:50]!r}")
            old = d[bucket][k]
            d[bucket][k] = v["new"]
            n_mod += 1
            mods.append({"bucket": bucket, "key": k, "old": old, "new": v["new"],
                         "why": v.get("why", "")})
        for k, v in add.get(bucket, {}).items():
            if k in d[bucket]:
                raise DictError(f"{lang}/{bucket} 新增键已存在：{k[:50]!r}")
            d[bucket][k] = v
            n_add += 1
    return n_add, n_mod, mods


def load_packet(pdir, lang):
    p = os.path.join(pdir, f"{lang}-todo.json")
    if not os.path.exists(p):
        return []
    return json.load(open(p, encoding="utf-8"))


def apply_packets(d, pdir, lang, bucket="block"):
    """把翻译包里已填的译文并进指定桶。已存在且相同则跳过，已存在但不同视为冲突。"""
    items = load_packet(pdir, lang)
    n_add = n_skip = 0
    conflicts = []
    for it in items:
        node = it.get("node", "")
        tr = (it.get("translation") or "").strip()
        if not node or not tr:
            continue
        cur = d[bucket].get(node)
        if cur is None:
            d[bucket][node] = tr
            n_add += 1
        elif cur == tr:
            n_skip += 1
        else:
            conflicts.append((node, cur, tr))
    return n_add, n_skip, conflicts


def build_dict(repo, lang, with_packets=True, ddir=None, pdir=None):
    d = head_dict(repo, lang)
    for b in BUCKETS:
        d.setdefault(b, {})
    info = {}
    info["add"], info["mod"], info["mods"] = apply_delta(d, ddir or delta_dir(repo), lang)
    if with_packets:
        info["pkt_add"], info["pkt_skip"], info["conflicts"] = apply_packets(
            d, pdir or packet_dir(repo), lang)
    return d, info


def render(data):
    """按仓库约定序列化（不落盘），供逐字节比对用。"""
    ordered = {b: data[b] for b in BUCKETS if b in data}
    for b in data:
        if b not in ordered:
            ordered[b] = data[b]
    return (json.dumps(ordered, ensure_ascii=False, indent=2, sort_keys=False) + "\n").encode("utf-8")


def write_dict(repo, lang, data, expect_head_base=True):
    path = dict_path(repo, lang)
    raw = render(data)
    with open(path, "wb") as fh:
        fh.write(raw)
    problems = []
    if raw.startswith(b"\xef\xbb\xbf"):
        problems.append("写出后带 BOM")
    if b"\r\n" in raw:
        problems.append("写出后是 CRLF")
    lines = raw.count(b"\n")
    if expect_head_base:
        hb = head_bytes(repo, lang)
        hl = hb.count(b"\n")
        if hl and lines < hl:
            problems.append(f"行数 {lines} 少于 HEAD 的 {hl}（疑似被压成单行）")
        hk = sum(len(v) for v in json.loads(hb.decode("utf-8")).values()) if hb else 0
        nk = sum(len(v) for v in data.values())
        if hk and nk < hk:
            problems.append(f"键数 {nk} 少于 HEAD 的 {hk}（丢键）")
    if problems:
        raise DictError(f"{lang}: " + "；".join(problems))
    back = open(path, "rb").read()
    if back != raw:
        raise DictError(f"{lang}: 写出后回读不一致（磁盘 {len(back)} B / 期望 {len(raw)} B），"
                        f"可能有别的进程同时在写")
    return {"lang": lang, "lines": lines, "bytes": len(raw),
            "keys": {b: len(data.get(b, {})) for b in BUCKETS}, "total": sum(len(v) for v in data.values())}


def save_dict(repo, lang, with_packets=True):
    d, info = build_dict(repo, lang, with_packets)
    st = write_dict(repo, lang, d)
    st.update(info)
    return st


def load_local_stop(repo):
    raw = json.load(open(local_stop_path(repo), encoding="utf-8"))
    return {k: set(v) for k, v in raw.items() if not k.startswith("_")}


def _sha(raw):
    return hashlib.sha256(raw).hexdigest() if raw else ""


def verify_all(repo, langs=None):
    """逐语种重建并和工作树比对；任何一步出问题都折成一行 FAIL，不抛异常。"""
    rows = []
    for lang in (langs or LANGS):
        path = dict_path(repo, lang)
        work = open(path, "rb").read() if os.path.exists(path) else b""
        try:
            d, info = build_dict(repo, lang, with_packets=True)
        except DictError as exc:
            rows.append({"lang": lang, "ok": False, "reason": str(exc),
                         "rebuild_sha": "", "work_sha": _sha(work), "bytes": len(work),
                         "add": 0, "mod": 0})
            continue
        except Exception as exc:  # noqa: BLE001 —— 损坏的 delta 也要落成 FAIL 行
            rows.append({"lang": lang, "ok": False, "reason": f"{type(exc).__name__}: {exc}",
                         "rebuild_sha": "", "work_sha": _sha(work), "bytes": len(work),
                         "add": 0, "mod": 0})
            continue
        rebuilt = render(d)
        ok = rebuilt == work
        rows.append({"lang": lang, "ok": ok,
                     "reason": "" if ok else _first_diff(rebuilt, work),
                     "rebuild_sha": _sha(rebuilt), "work_sha": _sha(work),
                     "bytes": len(work), "add": info.get("add", 0), "mod": info.get("mod", 0)})
    return rows


def _first_diff(a, b):
    """给出第一处不同的大致位置，便于人工定位。"""
    if len(a) != len(b):
        return f"字节数不同：重建 {len(a)} / 工作树 {len(b)}"
    for i in range(len(a)):
        if a[i] != b[i]:
            line = a[:i].count(b"\n") + 1
            return f"第 {line} 行附近不一致（偏移 {i}）"
    return "内容不同但定位失败"


def report_verify(repo, langs=None, title="按 HEAD + delta + packets 重建并逐字节比对"):
    print(f"\n{title}")
    rows = verify_all(repo, langs)
    print(f"{'语种':7}{'重建 sha256':19}{'工作树 sha256':19}{'字节':>9}  结果")
    for r in rows:
        head = ("PASS 逐字节一致" if r["ok"] else f"FAIL {r['reason']}")
        print(f"{r['lang']:7}{r['rebuild_sha'][:16]:19}{r['work_sha'][:16]:19}{r['bytes']:>9}  {head}")
    npass = sum(1 for r in rows if r["ok"])
    print(f"汇总：PASS {npass} / FAIL {len(rows) - npass}（共 {len(rows)} 个词典）")
    if npass != len(rows):
        print("结论：FAIL 有词典无法从 HEAD + delta + packets 复现，先修 delta 再继续")
        return False
    print("结论：PASS 仓库内文件可独立复现全部词典")
    return True

