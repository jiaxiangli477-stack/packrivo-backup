import importlib.util,unittest
from pathlib import Path
p=Path(__file__).resolve().parents[1]/'deploy/r1-dict-500to250.py'
s=importlib.util.spec_from_file_location('r1dict',p);m=importlib.util.module_from_spec(s);s.loader.exec_module(m)
class DictTests(unittest.TestCase):
 def test_adds_exact_number_change_in_each_existing_bucket(self):
  docs={'de':{'attr':{'old 500':'Alt 500 Stück'},'block':{'old 500':'Alt 500 Stück'}}}
  out,n=m.plan(docs,[('old 500','old 250')]);self.assertEqual(n,2);self.assertEqual(out['de']['attr']['old 250'],'Alt 250 Stück');self.assertEqual(out['de']['attr']['old 500'],'Alt 500 Stück')
 def test_refuses_non_number_english_change(self):
  with self.assertRaises(ValueError):m.plan({'de':{'text':{'old 500':'Alt 500'}}},[('old 500','other 250')])
 def test_refuses_translation_without_single_500(self):
  with self.assertRaises(ValueError):m.plan({'de':{'text':{'old 500':'Alt'}}},[('old 500','old 250')])
 def test_other_existing_250_is_unchanged(self):
  out,n=m.plan({'de':{'text':{'old 500':'früher 250, jetzt 500'}}},[('old 500','old 250')]);self.assertEqual(out['de']['text']['old 250'],'früher 250, jetzt 250')
 def test_conflict_refused(self):
  with self.assertRaises(ValueError):m.plan({'de':{'text':{'old 500':'Alt 500','old 250':'Different'}}},[('old 500','old 250')])
if __name__=='__main__':unittest.main()
