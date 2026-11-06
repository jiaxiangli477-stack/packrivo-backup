# -*- coding: utf-8 -*-
"""通用覆写：python set_override.py <lang> <idx> "<value>"
把一条覆写并入 .tmp\\32a8\\overrides.json（构建时最高优先级）。
"""
import io, json, os, sys

OVP = r'E:\破甲工作区\.tmp\32a8\overrides.json'


def main():
    if len(sys.argv) != 4:
        print('usage: set_override.py <lang> <idx> <value>')
        return 2
    lang, idx, value = sys.argv[1], int(sys.argv[2]), sys.argv[3]
    cur = {}
    if os.path.exists(OVP):
        cur = json.load(io.open(OVP, encoding='utf-8'))
    cur.setdefault(lang, {})[str(idx)] = value
    with io.open(OVP, 'w', encoding='utf-8', newline='\n') as f:
        f.write(json.dumps(cur, ensure_ascii=False, indent=0, sort_keys=True))
    print('set %s[%d] = %r | 总条数=%d' % (lang, idx, value, sum(len(d) for d in cur.values())))
    return 0


if __name__ == '__main__':
    sys.exit(main())
