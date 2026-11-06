import hashlib
import os
from pathlib import Path
import re
import subprocess
import tempfile
import unittest

ROOT=Path(__file__).resolve().parent.parent
TMP=Path(r'E:\破甲工作区\34a2-rehearsal')
BASH=r'C:\Program Files\Git\bin\bash.exe'
LANGS=['ar','de','es','fr','it','ja','ko','nl','pt','zh-CN','zh-TW']


class RunbookGateTest(unittest.TestCase):
    def test_dictionary_hash_check_detects_change(self):
        text=(ROOT/'deploy/RUNBOOK-launch.md').read_text(encoding='utf8').split('## §34a ·',1)[1]
        match=re.search(r'verify34a_package_dicts\(\) \{\n.*?\n\}',text,re.S)
        self.assertIsNotNone(match,'runbook dictionary verification function missing')
        with tempfile.TemporaryDirectory(dir=TMP) as folder:
            p=Path(folder);pkg=p/'pkg';dicts=pkg/'i18n-merged';dicts.mkdir(parents=True);lines=[]
            for lang in LANGS:
                data=('dictionary '+lang).encode('utf8');(dicts/(lang+'-dict.json')).write_bytes(data)
                lines.append(lang+' '+hashlib.sha256(data).hexdigest())
            checks=p/'hashes.txt';checks.write_text('\n'.join(lines)+'\n',encoding='utf8',newline='\n')
            script='PKG=$(cygpath -u "$TEST_PKG"); DICT34A_SHA_FILE=$(cygpath -u "$TEST_HASHES"); DICT34A_RECORD_SHA="$TEST_RECORD_SHA"\n'+match.group(0)+'\nverify34a_package_dicts\n'
            env=dict(os.environ,TEST_PKG=str(pkg),TEST_HASHES=str(checks),TEST_RECORD_SHA=hashlib.sha256(checks.read_bytes()).hexdigest())
            def run():return subprocess.run([BASH,'--noprofile','--norc','-c',script],env=env,capture_output=True,text=True)
            first=run();self.assertEqual(first.returncode,0,first.stderr)
            (dicts/'fr-dict.json').write_text('changed',encoding='utf8')
            second=run();self.assertNotEqual(second.returncode,0)
            changed=hashlib.sha256((dicts/'fr-dict.json').read_bytes()).hexdigest()
            checks.write_text('\n'.join('fr '+changed if line.startswith('fr ') else line for line in lines)+'\n',encoding='utf8',newline='\n')
            third=run();self.assertNotEqual(third.returncode,0,'changed hash ledger was accepted')

    def test_runtime_dictionary_must_match_gated_package(self):
        text=(ROOT/'deploy/RUNBOOK-launch.md').read_text(encoding='utf8').split('## §34a ·',1)[1]
        package=re.search(r'verify34a_package_dicts\(\) \{\n.*?\n\}',text,re.S)
        runtime=re.search(r'verify34a_runtime_dicts\(\) \{\n.*?\n\}',text,re.S)
        self.assertIsNotNone(package);self.assertIsNotNone(runtime)
        with tempfile.TemporaryDirectory(dir=TMP) as folder:
            p=Path(folder);pkg=p/'pkg';dicts=pkg/'i18n-merged';dicts.mkdir(parents=True);live=p/'live';live.mkdir();lines=[]
            for lang in LANGS:
                data=('dictionary '+lang).encode('utf8');name=lang+'-dict.json'
                (dicts/name).write_bytes(data);(live/name).write_bytes(data)
                lines.append(lang+' '+hashlib.sha256(data).hexdigest())
            checks=p/'hashes.txt';checks.write_text('\n'.join(lines)+'\n',encoding='utf8',newline='\n')
            script='PKG=$(cygpath -u "$TEST_PKG"); LIVE=$(cygpath -u "$TEST_LIVE"); DICT34A_SHA_FILE=$(cygpath -u "$TEST_HASHES"); DICT34A_RECORD_SHA="$TEST_RECORD_SHA"; WPC=/site\n'
            script+='docker() { sha256sum "$LIVE/$(basename "$4")"; }\n'+package.group(0)+'\n'+runtime.group(0)+'\nverify34a_runtime_dicts\n'
            env=dict(os.environ,TEST_PKG=str(pkg),TEST_LIVE=str(live),TEST_HASHES=str(checks),TEST_RECORD_SHA=hashlib.sha256(checks.read_bytes()).hexdigest())
            def run():return subprocess.run([BASH,'--noprofile','--norc','-c',script],env=env,capture_output=True,text=True)
            first=run();self.assertEqual(first.returncode,0,first.stderr)
            (live/'ja-dict.json').write_text('different runtime copy',encoding='utf8')
            second=run();self.assertNotEqual(second.returncode,0)


if __name__=='__main__':unittest.main()
