import importlib.util,tempfile,unittest,zipfile,hashlib
from pathlib import Path
p=Path(__file__).resolve().parents[1]/'deploy/r1-stage-release.py';s=importlib.util.spec_from_file_location('r1stage',p);m=importlib.util.module_from_spec(s);s.loader.exec_module(m)
class Tests(unittest.TestCase):
 def test_manifest_checks_bytes_and_rejects_tamper(self):
  with tempfile.TemporaryDirectory(dir=r'E:\破甲工作区\.tmp') as tmp:
   p=Path(tmp);(p/'a.txt').write_bytes(b'abc');(p/'MANIFEST.sha256').write_text(hashlib.sha256(b'abc').hexdigest()+'  a.txt\n',encoding='utf8');self.assertEqual(m.verify_manifest(p),1);(p/'a.txt').write_bytes(b'bad')
   with self.assertRaises(ValueError):m.verify_manifest(p)
 def test_baseline_mismatch_stops(self):
  m.check_baseline('aa  ./a\n','aa  ./a\n','theme')
  with self.assertRaises(ValueError):m.check_baseline('aa  ./a\n','bb  ./a\n','theme')
 def test_archive_rejects_parent_traversal(self):
  with tempfile.TemporaryDirectory(dir=r'E:\破甲工作区\.tmp') as tmp:
   p=Path(tmp);z=p/'x.zip'
   with zipfile.ZipFile(z,'w') as f:f.writestr('../escape','bad')
   with self.assertRaises(ValueError):m.extract_zip(z,p/'out','component')
 def test_archive_accepts_expected_root(self):
  with tempfile.TemporaryDirectory(dir=r'E:\破甲工作区\.tmp') as tmp:
   p=Path(tmp);z=p/'x.zip'
   with zipfile.ZipFile(z,'w') as f:f.writestr('component/a.txt','good')
   self.assertEqual(m.extract_zip(z,p/'out','component'),1);self.assertEqual((p/'out/component/a.txt').read_text(),'good')
if __name__=='__main__':unittest.main()
