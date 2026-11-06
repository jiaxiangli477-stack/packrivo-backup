import importlib.util,unittest
from pathlib import Path
p=Path(__file__).resolve().parents[1]/'deploy/r1-link-report.py';s=importlib.util.spec_from_file_location('r1links',p);m=importlib.util.module_from_spec(s);s.loader.exec_module(m)
class Tests(unittest.TestCase):
 def test_inbound_and_redirect(self):
  d={'records':{'/':{'status':200,'links':['/a/']},'/old/':{'status':301,'redirect':'https://www.packrivo.com/new/'}},'sitemap_bad':[],'orphans':[],'query_links':[],'errors':[]}
  targets={'version':1,'inbound_targets':['/a/'],'redirect':{'path':'/old/','status':301,'target':'/new/'}}
  self.assertEqual(m.assess(d,targets)['issues'],0);targets['inbound_targets'].append('/missing/');self.assertEqual(m.assess(d,targets)['issues'],1)
 def test_nonwhitelisted_standard_fails(self):
  d={'records':{'/x/':{'status':200,'links':[],'redlines':{'standard':1}},'/old/':{'status':301,'redirect':'https://www.packrivo.com/new/'}},'sitemap_bad':[],'orphans':[],'query_links':[],'errors':[]}; t={'version':1,'inbound_targets':[],'redirect':{'path':'/old/','status':301,'target':'/new/'}};self.assertGreater(m.assess(d,t)['issues'],0)
 def test_redirect_wrong(self):
  d={'records':{'/old/':{'status':301,'redirect':'https://www.packrivo.com/wrong/'}},'sitemap_bad':[],'orphans':[],'query_links':[],'errors':[]};targets={'version':1,'inbound_targets':[],'redirect':{'path':'/old/','status':301,'target':'/new/'}};self.assertEqual(m.assess(d,targets)['issues'],1)
if __name__=='__main__':unittest.main()
