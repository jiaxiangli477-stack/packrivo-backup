import importlib.util,unittest
from pathlib import Path
p=Path(__file__).resolve().parents[1]/'deploy/r1-29y-list.py';s=importlib.util.spec_from_file_location('r129y',p);m=importlib.util.module_from_spec(s);s.loader.exec_module(m)
class Tests(unittest.TestCase):
 def test_build(self):
  r=m.build(['/','/about/'],'http://packrivo-r1-proxy',['de','fr']);self.assertEqual(len(r),4);self.assertIn('de http://packrivo-r1-proxy/de/about/',r)
 def test_reject_unsafe(self):
  with self.assertRaises(ValueError):m.build(['//evil'],'http://packrivo-r1-proxy',['de'])
  with self.assertRaises(ValueError):m.build(['/'],'http://x/path',['de'])
if __name__=='__main__':unittest.main()
