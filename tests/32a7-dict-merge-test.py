import importlib.util,unittest
from pathlib import Path
path=Path(__file__).resolve().parents[1]/'deploy/32a7-dict-merge.py'
spec=importlib.util.spec_from_file_location('dict_merge_32a7',path)
m=importlib.util.module_from_spec(spec);spec.loader.exec_module(m)
class MergeTests(unittest.TestCase):
 def test_merge_preserves_and_appends(self):
  base={'text':{'unchanged':'x','change':'old'}};main={'text':{'unchanged':'x','change':'old','main':'a'}};branch={'text':{'unchanged':'x','change':'new','branch':'b'}}
  merged,conflicts=m.merge_dicts(base,main,branch)
  self.assertEqual(conflicts,[]);self.assertEqual(merged,{'text':{'unchanged':'x','change':'new','main':'a','branch':'b'}})
  self.assertEqual(list(merged['text']),['unchanged','change','main','branch'])
 def test_conflict_stops(self):
  _,conflicts=m.merge_dicts({'text':{'x':'a'}},{'text':{'x':'b'}},{'text':{'x':'c'}})
  self.assertEqual(conflicts,[('text','x')])
 def test_deletion_is_rejected(self):
  _,conflicts=m.merge_dicts({'text':{'x':'a'}},{'text':{}},{'text':{'x':'a'}})
  self.assertTrue(conflicts)
 def test_duplicates_rejected(self):
  with self.assertRaises(ValueError):m.parse_json('{"text":{"x":"a","x":"b"}}')
if __name__=='__main__':unittest.main()
