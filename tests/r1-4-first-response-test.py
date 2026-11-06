import importlib.util,unittest
from pathlib import Path
p=Path(__file__).resolve().parents[1]/'deploy/r1-first-response-check.py';s=importlib.util.spec_from_file_location('r1first',p);m=importlib.util.module_from_spec(s);s.loader.exec_module(m)
class Tests(unittest.TestCase):
 def test_og_and_title_detect(self):
  hits=m.inspect('<title>500件</title><meta property="og:description" content="500-piece">',False);self.assertEqual(len(hits),2)
 def test_body_price_does_not_block(self):self.assertEqual(m.inspect('<title>250</title><main>500 pieces</main>',False),[])
 def test_products_stale_registry_blocks(self):self.assertTrue(m.inspect('<script>{"text":"MOQ 500 pieces"}</script>',True))
 def test_property_checked_when_name_also_present(self):self.assertTrue(m.inspect('<meta name=other property="og:description" content="500">',False))
 def test_products_clean(self):self.assertEqual(m.inspect('<h1>MOQ 250</h1>',True),[])
if __name__=='__main__':unittest.main()
