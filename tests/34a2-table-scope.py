#!/usr/bin/env python3
"""Compare table checksums outside the three tables 34a is allowed to modify."""
import argparse
import json
from pathlib import Path
import re
import subprocess
import sys

ALLOWED = {'wp_posts','wp_postmeta','wp_yoast_indexable'}
MYSQL = ['docker','exec','-i','packrivo-34a-db','sh','-c',
         'export MYSQL_PWD="$MYSQL_ROOT_PASSWORD"; exec mysql -uroot --batch --raw --skip-column-names www_packrivo_com']


def query(sql):
    p=subprocess.run(MYSQL,input=sql.encode('utf8'),capture_output=True,timeout=120,check=True)
    return p.stdout.decode('utf8').splitlines()


def snapshot():
    names=[line.split('\t')[0] for line in query('SHOW FULL TABLES WHERE Table_type="BASE TABLE";')]
    if not names or any(not re.fullmatch(r'[A-Za-z0-9_]+',name) for name in names):
        raise ValueError('invalid or empty table list')
    result=query('CHECKSUM TABLE '+','.join('`'+name+'`' for name in names)+';')
    values={line.split('\t')[0].rsplit('.',1)[-1]:line.split('\t')[1] for line in result}
    if set(values)!=set(names) or any(v=='NULL' for v in values.values()):
        raise ValueError('incomplete table checksums')
    return values


def main():
    p=argparse.ArgumentParser();p.add_argument('mode',choices=['capture','compare']);p.add_argument('baseline',type=Path);args=p.parse_args()
    current=snapshot()
    if args.mode=='capture':
        if args.baseline.exists():raise ValueError('refusing to overwrite baseline')
        args.baseline.write_text(json.dumps(current,sort_keys=True,indent=2)+'\n',encoding='utf8')
        print(f'TABLE_BASELINE total={len(current)}');return 0
    old=json.loads(args.baseline.read_text(encoding='utf8'))
    if set(old)!=set(current):raise ValueError('table set changed')
    changed=[name for name in sorted(current) if name not in ALLOWED and current[name]!=old[name]]
    print(f'TABLE_SCOPE total={len(current)} protected={len(current)-len(ALLOWED)} unexpected_changed={len(changed)}')
    for name in changed:print('UNEXPECTED_TABLE',name,'before='+old[name],'after='+current[name])
    return 1 if changed else 0


if __name__=='__main__':
    sys.exit(main())
