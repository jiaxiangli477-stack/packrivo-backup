import csv
import json
import os
import subprocess
import sys
import tempfile
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
GATE = ROOT / 'deploy/34a-i18n-gate.py'
TMP = Path(r'E:\破甲工作区\34a2-rehearsal')
LANGS = ['ar','de','es','fr','it','ja','ko','nl','pt','zh-CN','zh-TW']


class TranslationGateTest(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory(dir=TMP)
        self.root = Path(self.temp.name)
        self.dicts = self.root / 'dicts'
        self.dicts.mkdir()
        self.csv = self.root / 'keys.csv'
        with self.csv.open('w', encoding='utf-8-sig', newline='') as f:
            writer = csv.writer(f)
            writer.writerow(['source','url','old_en','new_en'])
            writer.writerow(['yoast_title','/get-a-quote/','old','New title'])
            writer.writerow(['h1','/about/','old','New body'])
        for lang in LANGS:
            (self.dicts / (lang + '-dict.json')).write_text(json.dumps({
                'title': {'New title':'translated'}, 'attr':{},
                'block': {'New body':'translated'}, 'text':{}
            }), encoding='utf8')

    def tearDown(self):
        self.temp.cleanup()

    def gate(self):
        return subprocess.run([sys.executable, str(GATE), '--csv', str(self.csv), '--dict-dir', str(self.dicts)], capture_output=True, text=True, encoding='utf8', env=dict(os.environ, PYTHONUTF8='1'))

    def test_complete_passes(self):
        result = self.gate()
        self.assertEqual(result.returncode, 0, result.stdout + result.stderr)
        self.assertIn('GATE PASS', result.stdout)

    def test_missing_and_empty_fail(self):
        path = self.dicts / 'ja-dict.json'
        data = json.loads(path.read_text(encoding='utf8'))
        data['block']['New body'] = ''
        path.write_text(json.dumps(data), encoding='utf8')
        result = self.gate()
        self.assertNotEqual(result.returncode, 0)
        self.assertIn('ja missing=1', result.stdout)

    def test_missing_language_fails(self):
        (self.dicts / 'ar-dict.json').unlink()
        result = self.gate()
        self.assertNotEqual(result.returncode, 0)
        self.assertIn('ar ERROR', result.stdout)

    def test_title_in_wrong_bucket_fails(self):
        path = self.dicts / 'de-dict.json'
        data = json.loads(path.read_text(encoding='utf8'))
        del data['title']['New title']
        data['block']['New title'] = 'translated but not available to title lookup'
        path.write_text(json.dumps(data), encoding='utf8')
        result = self.gate()
        self.assertNotEqual(result.returncode, 0)
        self.assertIn('de missing=1', result.stdout)

    def test_quote_theme_title_requires_title_bucket(self):
        key='Get a Custom Packaging Quote | Packrivo'
        with self.csv.open('a',encoding='utf8',newline='') as f:
            csv.writer(f).writerow(['theme','/get-a-quote/','old',key])
        for lang in LANGS:
            path=self.dicts/(lang+'-dict.json');data=json.loads(path.read_text(encoding='utf8'))
            data['title'][key]='translated';path.write_text(json.dumps(data),encoding='utf8')
        path=self.dicts/'fr-dict.json';data=json.loads(path.read_text(encoding='utf8'))
        del data['title'][key];data['text'][key]='translated in wrong bucket';path.write_text(json.dumps(data),encoding='utf8')
        result=self.gate()
        self.assertNotEqual(result.returncode,0)
        self.assertIn('fr missing=1',result.stdout)


if __name__ == '__main__':
    unittest.main()
