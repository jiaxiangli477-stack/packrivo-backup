"""扫描入口（29y）：只用仓库里的脚本与 JSON，复制进容器前后各算一次 sha256 并比对，再执行扫描。

用法：
  python run-scan-29y.py <列表文件> <输出名> [门禁 IP]
列表文件是 "<lang> <url>" 的行；输出写到容器 /tmp/<输出名>，再拷回 <输出名>.jsonl 所在目录。
"""
import hashlib
import os
import subprocess
import sys

REPO = r"E:\破甲工作区\packrivo-backup"
DOCKER = r"D:\Docker\App\resources\bin\docker.exe"
CONTAINER = "php"

import glob
FILES = [
    (os.path.join(REPO, r"deploy\scripts-29y\scan-main-english-29y.php"), "/tmp/scan.php"),
    (os.path.join(REPO, r"deploy\verification-29y\i18n\en-lexicon.json"), "/tmp/en-lexicon.json"),
    (os.path.join(REPO, r"deploy\verification-29y\i18n\local-stop.json"), "/tmp/local-stop.json"),
]
# 所有语种的豁免表都复制过去（扫描器按 <lang> 各自读取）
for p in sorted(glob.glob(os.path.join(REPO, r"deploy\verification-29y\i18n\allow-*.json"))):
    FILES.append((p, "/tmp/allow/" + os.path.basename(p)))


def sha_local(p):
    h = hashlib.sha256()
    with open(p, "rb") as fh:
        for chunk in iter(lambda: fh.read(1 << 20), b""):
            h.update(chunk)
    return h.hexdigest()


def sha_remote(p):
    out = subprocess.run([DOCKER, "exec", CONTAINER, "sha256sum", p],
                         capture_output=True).stdout.decode("utf-8", "replace").strip()
    return out.split()[0] if out else ""


def main():
    if len(sys.argv) < 3:
        print(__doc__)
        return 2
    listf, outname = sys.argv[1], sys.argv[2]
    gw = sys.argv[3] if len(sys.argv) > 3 else "172.19.0.5"
    subprocess.run([DOCKER, "exec", CONTAINER, "sh", "-lc", "mkdir -p /tmp/allow"], check=False)

    print("== 复制仓库文件到容器并核对 sha256 ==")
    ok = True
    for src, dst in FILES:
        local = sha_local(src)
        subprocess.run([DOCKER, "cp", src, f"{CONTAINER}:{dst}"], check=True)
        remote = sha_remote(dst)
        same = local == remote
        ok &= same
        print(f"  {os.path.basename(src):32} {local[:16]}  {remote[:16]}  {'SAME' if same else 'DIFF'}")
    if not ok:
        print("PASS 判定：FAIL（容器里的副本与仓库不一致，拒绝扫描）")
        return 1
    print("  PASS 四个文件与仓库逐字节一致（sha256 相同）")

    subprocess.run([DOCKER, "cp", listf, f"{CONTAINER}:/tmp/{outname}.list"], check=True)
    print(f"\n== 扫描 {listf} ==")
    cmd = (f"php /tmp/scan.php /tmp/{outname}.list /tmp/{outname}.jsonl {gw} "
           f"/tmp/local-stop.json /tmp/en-lexicon.json /tmp/allow")
    r = subprocess.run([DOCKER, "exec", CONTAINER, "sh", "-lc", cmd],
                       capture_output=True).stdout.decode("utf-8", "replace")
    print(r.strip()[-800:])
    dest = os.path.join(os.path.dirname(os.path.abspath(listf)), outname + ".jsonl")
    subprocess.run([DOCKER, "cp", f"{CONTAINER}:/tmp/{outname}.jsonl", dest], check=True)
    print(f"输出: {dest}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
