import csv, json, os, subprocess, sys, tempfile, unittest
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
SCRIPT=ROOT/'deploy/r1-import-i18n-csv.py'
class ImportTests(unittest.TestCase):
 def run_case(self, conflict=False):
  with tempfile.TemporaryDirectory(dir=r'E:\破甲工作区\.tmp') as tmp:
   p=Path(tmp); d=p/'dict'; d.mkdir(); csvfile=p/'input.csv'
   for lang in ('ar','de','es','fr','it','ja','ko','nl','pt','zh-CN','zh-TW'):
    (d/(lang+'-dict.json')).write_text(json.dumps({'title':{},'attr':{},'block':{},'text':{'old':'keep',**({'new':'different'} if conflict and lang=='de' else {})}},ensure_ascii=False,indent=2)+'\n',encoding='utf8')
   with csvfile.open('w',encoding='utf8',newline='') as f:
    w=csv.writer(f);w.writerow(['en','bucket','lang','value','origin(new)']);w.writerow(['new','text','ar','translated','new']);w.writerow(['new','text','de','translated','new'])
   before={x.name:x.read_bytes() for x in d.glob('*.json')}
   q=subprocess.run([sys.executable,str(SCRIPT),'--csv',str(csvfile),'--dict-dir',str(d)],capture_output=True,text=True,encoding='utf8',errors='replace')
   after={x.name:x.read_bytes() for x in d.glob('*.json')}
   if conflict:
    self.assertNotEqual(q.returncode,0);self.assertIn('CONFLICT',q.stdout);self.assertEqual(before,after)
   else:
    self.assertEqual(q.returncode,0,q.stdout+q.stderr);self.assertEqual(json.loads(after['ar-dict.json'])['text'],{'old':'keep','new':'translated'});self.assertEqual(before['es-dict.json'],after['es-dict.json'])
 def test_import(self): self.run_case()
 def test_conflict_does_not_write_any_dictionary(self): self.run_case(True)
if __name__=='__main__': unittest.main()
