import importlib.util,unittest
from pathlib import Path
p=Path(__file__).resolve().parents[1]/'deploy/r1-29y-report.py';s=importlib.util.spec_from_file_location('r129yr',p);m=importlib.util.module_from_spec(s);s.loader.exec_module(m)
class Tests(unittest.TestCase):
 def test_latin_and_cjk_rules(self):
  self.assertTrue(m.failed({'lang':'de','residual_count':6,'long_runs':0}));self.assertFalse(m.failed({'lang':'de','residual_count':5,'long_runs':2}));self.assertTrue(m.failed({'lang':'ja','residual_count':0,'long_runs':1}));self.assertFalse(m.failed({'lang':'ja','residual_count':200,'long_runs':0}))
if __name__=='__main__':unittest.main()
