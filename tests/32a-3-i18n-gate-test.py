import csv,json,os,re,subprocess,sys,tempfile,unittest
from pathlib import Path
ROOT=Path(__file__).resolve().parent.parent
GATE=ROOT/'deploy/32a-i18n-gate.py'
TMP=ROOT/'.rehearsal-32a/32a-3-test-tmp';TMP.mkdir(parents=True,exist_ok=True)
LANGS=['ar','de','es','fr','it','ja','ko','nl','pt','zh-CN','zh-TW']
QUOTE=["Under 250? Send the brief and we'll quote by hand.",
       'Goods {goods} · Sourcing fee {fee} ({percent}%, min. {minimum}) · Total {total}',
       'Instant quotes include the sourcing fee at {percent}%; your final rate is confirmed on the written quote.']
class GateTest(unittest.TestCase):
 def setUp(self):
  self.temp=tempfile.TemporaryDirectory(dir=TMP);self.path=Path(self.temp.name).resolve();assert self.path.is_relative_to(TMP.resolve());self.dicts=self.path/'dicts';self.quote=self.path/'quote';self.dicts.mkdir();self.quote.mkdir()
  self.csv=self.path/'keys.csv';self.keys=['One MOQ sentence']+QUOTE
  with self.csv.open('w',encoding='utf-8-sig',newline='') as f:
   w=csv.writer(f);w.writerow(['英文原文','source']);w.writerows([[s,'theme'] for s in self.keys]);w.writerow([self.keys[0],'db'])
  for lang in LANGS:
   (self.dicts/(lang+'-dict.json')).write_text(json.dumps({'title':{},'attr':{},'block':{k:'translated' for k in self.keys},'text':{}}),encoding='utf8')
   (self.quote/(lang+'.json')).write_text(json.dumps({k:'translated '+' '.join(re.findall(r'\{[A-Za-z_]\w*\}',k)) for k in QUOTE}),encoding='utf8')
 def tearDown(self):self.temp.cleanup()
 def gate(self):return subprocess.run([sys.executable,str(GATE),'--csv',str(self.csv),'--dict-dir',str(self.dicts),'--quote-dir',str(self.quote)],capture_output=True,text=True,encoding='utf8',env=dict(os.environ,PYTHONIOENCODING='utf-8'))
 def test_complete_deduplicates_and_passes(self):
  r=self.gate();self.assertEqual(r.returncode,0,r.stdout+r.stderr);self.assertIn('unique_keys=4',r.stdout);self.assertIn('de missing=0',r.stdout)
 def test_missing_site_key_fails(self):
  p=self.dicts/'de-dict.json';d=json.loads(p.read_text());del d['block'][self.keys[0]];p.write_text(json.dumps(d),encoding='utf8');r=self.gate();self.assertNotEqual(r.returncode,0);self.assertIn('de missing=1',r.stdout)
 def test_missing_quote_key_fails(self):
  p=self.quote/'ja.json';d=json.loads(p.read_text());del d[QUOTE[0]];p.write_text(json.dumps(d),encoding='utf8');r=self.gate();self.assertNotEqual(r.returncode,0);self.assertIn('ja missing=0 quote_missing=1',r.stdout)
 def test_quote_translation_must_preserve_placeholders(self):
  p=self.quote/'de.json';d=json.loads(p.read_text());d[QUOTE[1]]='translated but no placeholders';p.write_text(json.dumps(d),encoding='utf8');r=self.gate();self.assertNotEqual(r.returncode,0);self.assertIn('de missing=0 quote_missing=0 quote_invalid=1',r.stdout)
 def test_empty_or_invalid_dictionary_fails_closed(self):
  p=self.dicts/'fr-dict.json';p.write_text('{broken',encoding='utf8');r=self.gate();self.assertNotEqual(r.returncode,0);self.assertIn('fr ERROR',r.stdout)
if __name__=='__main__':unittest.main()
