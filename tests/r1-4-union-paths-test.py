import importlib.util,unittest
from pathlib import Path
p=Path(__file__).resolve().parents[1]/'deploy/r1-union-scan-paths.py';s=importlib.util.spec_from_file_location('r1union',p);m=importlib.util.module_from_spec(s);s.loader.exec_module(m)
class Tests(unittest.TestCase):
 def test_union(self):
  d={'sitemap_paths':['/','/blog/?pg=2'],'records':{'/product/?x=1':{'status':200},'/gone/':{'status':404}}};self.assertEqual(m.build(['/'],d),['/','/blog/?pg=2','/product/?x=1'])
 def test_unsafe_path(self):
  with self.assertRaises(ValueError):m.build(['/'],{'sitemap_paths':['//evil'],'records':{}})
if __name__=='__main__':unittest.main()
