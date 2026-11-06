import importlib.util,unittest
from pathlib import Path
p=Path(__file__).resolve().parents[1]/'deploy/r1-i18n-gate.py'
s=importlib.util.spec_from_file_location('gate',p); m=importlib.util.module_from_spec(s);s.loader.exec_module(m)
class GateTest(unittest.TestCase):
 def test_requires_nonempty_value(self):
  self.assertTrue(m.present({'text':{'x':'translated'}},'x'))
  self.assertFalse(m.present({'text':{'x':'  '}},'x'))
  self.assertFalse(m.present({'text':{}},'x'))
if __name__=='__main__':unittest.main()
