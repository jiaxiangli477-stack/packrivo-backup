import importlib.util,unittest
from pathlib import Path
p=Path(__file__).resolve().parents[1]/'deploy/r1-context-extract.py';s=importlib.util.spec_from_file_location('r1ctx',p);m=importlib.util.module_from_spec(s);s.loader.exec_module(m)
class Tests(unittest.TestCase):
 def test_contexts(self):
  data='a'*205+'MOQ 500'+'b'*205;r=m.contexts(data);self.assertEqual(len(r),2);self.assertEqual(r[0]['token'].upper(),'MOQ');self.assertEqual(len(r[0]['before']),200);self.assertEqual(len(r[1]['after']),200)
if __name__=='__main__':unittest.main()
