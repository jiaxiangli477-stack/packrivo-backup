# -*- coding: utf-8 -*-
"""29z：从仓库 mirror 打子主题 zip（固定时间戳、排序稳定、带目录项）。

源：packrivo-wp/packrivo-home-v4
出：deploy/packrivo-home-v4.zip（同时刷新 packrivo-wp/packrivo-home-v4.zip 这份 mirror 副本）
"""
import hashlib
import os
import sys
import zipfile

ROOT = r"E:\破甲工作区\packrivo-backup"
SRC = os.path.join(ROOT, "packrivo-wp", "packrivo-home-v4")
PAIRS = [
    (os.path.join(ROOT, "deploy", "packrivo-home-v4.zip")),
    (os.path.join(ROOT, "packrivo-wp", "packrivo-home-v4.zip")),
]
PREFIX = "packrivo-home-v4/"
STAMP = (2026, 10, 29, 0, 0, 0)


def collect():
    files = []
    dirs = set()
    for dp, dns, fns in os.walk(SRC):
        rel_dir = os.path.relpath(dp, SRC).replace(os.sep, "/")
        if rel_dir != ".":
            dirs.add(rel_dir)
        for fn in fns:
            rel = os.path.relpath(os.path.join(dp, fn), SRC).replace(os.sep, "/")
            files.append(rel)
    return sorted(files), sorted(dirs)


def main():
    files, dirs = collect()
    if not files:
        sys.exit("源目录为空: " + SRC)
    entries = [PREFIX + d + "/" for d in dirs] + [PREFIX + f for f in files]
    entries.sort(key=lambda n: (0 if n.endswith("/") else 1, n))
    for out in PAIRS:
        tmp = out + ".new"
        with zipfile.ZipFile(tmp, "w", zipfile.ZIP_DEFLATED) as z:
            for name in entries:
                info = zipfile.ZipInfo(name, date_time=STAMP)
                info.compress_type = zipfile.ZIP_DEFLATED
                if name.endswith("/"):
                    info.external_attr = 0o40755 << 16 | 0x10
                    z.writestr(info, b"")
                else:
                    info.external_attr = 0o644 << 16
                    with open(os.path.join(SRC, name[len(PREFIX):].replace("/", os.sep)), "rb") as fh:
                        z.writestr(info, fh.read())
        data = open(tmp, "rb").read()
        os.replace(tmp, out)
        print("%s\n  条目 %d（文件 %d + 目录 %d） 字节 %d sha256 %s"
              % (os.path.basename(out), len(entries), len(files), len(dirs), len(data),
                 hashlib.sha256(data).hexdigest()))


if __name__ == "__main__":
    main()
