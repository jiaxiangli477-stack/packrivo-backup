import importlib.util,unittest
from pathlib import Path
p=Path(__file__).resolve().parents[1]/'deploy/r1-redline-report.py';s=importlib.util.spec_from_file_location('r1report',p);m=importlib.util.module_from_spec(s);s.loader.exec_module(m)
class Tests(unittest.TestCase):
 def test_permitted_catalog_only(self):
  self.assertTrue(m.is_r2_catalog('/catalog-paper-box-o1cn019mnvjf1i/'));self.assertFalse(m.is_r2_catalog('/catalog-anything-else/'))
 def test_id1094_whitelist_exact(self):
  self.assertTrue(m.is_1094('/2026/10/10/fsc-packaging-label-permission/'));self.assertFalse(m.is_1094('/other/'))
 def test_sitemap_noindex_fails(self):
  x={'path':'/','status':200,'noindex':False,'header_noindex':True,'fields':[],'body':[]};summary=m.assess([x],{'/'});self.assertEqual(summary['sitemap_bad'],1)
 def test_nonlisted_indexable_and_500_fail(self):
  rows=[{'path':'/','status':200,'noindex':False,'header_noindex':False,'fields':[],'body':[]},{'path':'/new/','status':200,'noindex':False,'header_noindex':False,'fields':[{'field':'h1','value':'500'}],'body':[]}];s=m.assess(rows,{'/'});self.assertEqual(s['field_hits'],1);self.assertEqual(s['indexable_omissions'],1)
if __name__=='__main__':unittest.main()
