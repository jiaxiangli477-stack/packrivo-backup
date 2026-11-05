import json,subprocess,tempfile,unittest
from pathlib import Path
ROOT=Path(__file__).resolve().parent.parent;TMP=ROOT/'.rehearsal-32a/32a-4-test-tmp';TMP.mkdir(parents=True,exist_ok=True)
class TitleTest(unittest.TestCase):
 def setUp(self):
  self.temp=tempfile.TemporaryDirectory(dir=TMP);self.path=Path(self.temp.name).resolve();assert self.path.is_relative_to(TMP.resolve());(self.path/'packrivo-i18n').mkdir()
 def tearDown(self):self.temp.cleanup()
 def run_php(self):
  base='/work/'+self.path.relative_to(ROOT).as_posix()
  return subprocess.run(['docker','run','--rm','-v',str(ROOT)+':/work:ro','-e','TEST_UPLOAD_BASE='+base,'php:8.2-cli','php','/work/tests/32a-4-404-harness.php'],capture_output=True,text=True,encoding='utf8')
 def test_dictionary_title_and_escaping(self):
  (self.path/'packrivo-i18n/de-dict.json').write_text(json.dumps({'title':{'Page not found | Packrivo':'TEST <DE> & title'}}),encoding='utf8');r=self.run_php()
  self.assertEqual(r.returncode,0,r.stderr);self.assertIn('<title>TEST &lt;DE&gt; &amp; title</title>',r.stdout);self.assertIn('<p>unchanged</p>',r.stdout)
 def test_missing_dictionary_falls_back(self):
  r=self.run_php();self.assertEqual(r.returncode,0,r.stderr);self.assertIn('<title>Page not found | Packrivo</title>',r.stdout)
 def test_empty_or_malformed_dictionary_falls_back(self):
  (self.path/'packrivo-i18n/de-dict.json').write_text('{bad',encoding='utf8');r=self.run_php();self.assertEqual(r.returncode,0,r.stderr);self.assertIn('<title>Page not found | Packrivo</title>',r.stdout)
 def test_wrong_title_bucket_type_falls_back(self):
  (self.path/'packrivo-i18n/de-dict.json').write_text(json.dumps({'title':'not-an-object'}),encoding='utf8');r=self.run_php();self.assertEqual(r.returncode,0,r.stderr);self.assertIn('<title>Page not found | Packrivo</title>',r.stdout)
if __name__=='__main__':unittest.main()
