"""停手 10 分钟：前后各打一次 11 个词典的 mtime + sha256，两次必须一致。

期间不做任何词典写入。用法：python deploy/scripts-29y/hold-dicts-29y.py [秒数]
"""
import hashlib, os, sys, time

HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, HERE)
import dict_io_29y as dio  # noqa: E402


def snap(repo):
    rows = []
    for lg in dio.LANGS:
        p = dio.dict_path(repo, lg)
        st = os.stat(p)
        h = hashlib.sha256(open(p, "rb").read()).hexdigest()
        rows.append((lg, int(st.st_mtime), st.st_size, h))
    return rows


def show(title, rows):
    print(title)
    for lg, mt, size, h in rows:
        print("  %-6s mtime %s  %9d B  sha256 %s"
              % (lg, time.strftime("%Y-%m-%d %H:%M:%S", time.localtime(mt)), size, h))


def main():
    sys.stdout.reconfigure(encoding="utf-8")
    repo = dio.repo_root()
    wait = int(sys.argv[1]) if len(sys.argv) > 1 else 600
    a = snap(repo)
    show("第一次快照（%s）" % time.strftime("%Y-%m-%d %H:%M:%S"), a)
    print("…停手 %d 秒，不做任何词典写入…" % wait)
    sys.stdout.flush()
    time.sleep(wait)
    b = snap(repo)
    show("第二次快照（%s）" % time.strftime("%Y-%m-%d %H:%M:%S"), b)
    print()
    same = 0
    diff = 0
    for (lg, mt1, s1, h1), (_, mt2, s2, h2) in zip(a, b):
        if mt1 == mt2 and s1 == s2 and h1 == h2:
            same += 1
            print("  PASS %-6s mtime/sha256 未变" % lg)
        else:
            diff += 1
            print("  FAIL %-6s 变了：mtime %s→%s，sha256 %s→%s"
                  % (lg, mt1, mt2, h1[:16], h2[:16]))
    print("\n汇总：PASS %d / FAIL %d（共 %d 个词典）" % (same, diff, len(a)))
    print("结论：" + ("PASS 10 分钟内没有任何写入" if diff == 0 else "FAIL 仍有写入"))
    return 0 if diff == 0 else 1


if __name__ == "__main__":
    sys.exit(main())
