# -*- coding: utf-8 -*-
"""29z §3b：从修好手的源图生成首页大图两档，覆盖子主题同名文件。

源图：deploy/hero-29z/hero-source-repaired.webp（1254×1254，sha256 aaac86a9…0a902）
输出：packrivo-wp/packrivo-home-v4/assets/img/hero.webp（1200）
      packrivo-wp/packrivo-home-v4/assets/img/hero-480.webp（480）
只做等比缩放（源图本身就是 1:1），不改构图、不加任何标注。
"""
import hashlib
import json
import os
import sys

from PIL import Image

ROOT = r"E:\破甲工作区\packrivo-backup"
SRC = os.path.join(ROOT, "deploy", "hero-29z", "hero-source-repaired.webp")
DST = os.path.join(ROOT, "packrivo-wp", "packrivo-home-v4", "assets", "img")
EVID = os.path.join(ROOT, "deploy", "verification-29z", "evidence")

TARGETS = [(1200, "hero.webp"), (480, "hero-480.webp")]


def sha256(path):
    with open(path, "rb") as fh:
        return hashlib.sha256(fh.read()).hexdigest()


def main():
    if not os.path.exists(SRC):
        sys.exit("源图不存在: " + SRC)
    os.makedirs(EVID, exist_ok=True)
    src_hash = sha256(SRC)
    with Image.open(SRC) as im:
        im.load()
        src_size = im.size
        im = im.convert("RGB") if im.mode not in ("RGB", "RGBA") else im
        rows = []
        for side, name in TARGETS:
            # 源图 1254 已经是方形；先按短边取中心方框，再缩到目标边长
            w, h = im.size
            edge = min(w, h)
            box = ((w - edge) // 2, (h - edge) // 2, (w - edge) // 2 + edge, (h - edge) // 2 + edge)
            out_im = im.crop(box).resize((side, side), Image.LANCZOS)
            out_path = os.path.join(DST, name)
            out_im.save(out_path, "WEBP", quality=82, method=6)
            with Image.open(out_path) as chk:
                got = chk.size
            rows.append({
                "file": name,
                "path": out_path,
                "size": list(got),
                "bytes": os.path.getsize(out_path),
                "sha256": sha256(out_path),
            })
            print(f"{name}: {got[0]}x{got[1]} {os.path.getsize(out_path)} B {rows[-1]['sha256'][:16]}")
    meta = {
        "source": SRC,
        "source_sha256": src_hash,
        "source_size": list(src_size),
        "quality": 82,
        "method": "center-square crop + LANCZOS",
        "outputs": rows,
    }
    out_json = os.path.join(EVID, "hero-29z-outputs.json")
    with open(out_json, "w", encoding="utf-8") as fh:
        json.dump(meta, fh, ensure_ascii=False, indent=1)
    print("源图 sha256 =", src_hash)
    print("清单 ->", out_json)


if __name__ == "__main__":
    main()
