import importlib.util,unittest
from pathlib import Path
p=Path(__file__).resolve().parents[1]/'deploy/r1-target-report.py';s=importlib.util.spec_from_file_location('r1target',p);m=importlib.util.module_from_spec(s);s.loader.exec_module(m)
class Tests(unittest.TestCase):
 def test_target_pass(self):self.assertTrue(m.target_ok('TARGET / HTTP=200 MOQ250=1 MOQ500=0'))
 def test_target_bad(self):self.assertFalse(m.target_ok('TARGET / HTTP=200 MOQ250=1 MOQ500=1'))
 def test_target_wrong(self):self.assertFalse(m.target_ok('not a target'))
if __name__=='__main__':unittest.main()
