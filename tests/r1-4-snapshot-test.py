import importlib.util,tarfile,tempfile,unittest,io
from pathlib import Path
p=Path(__file__).resolve().parents[1]/'deploy/r1-snapshot.py';s=importlib.util.spec_from_file_location('r1snap',p);m=importlib.util.module_from_spec(s);s.loader.exec_module(m)
class Tests(unittest.TestCase):
 def test_db_name_guard(self):
  self.assertTrue(m.valid_db_name('www_packrivo_com'));self.assertFalse(m.valid_db_name('db; DROP'))
 def test_archive_rejects_escape_and_accepts_expected(self):
  with tempfile.TemporaryDirectory(dir=r'E:\破甲工作区\.tmp') as tmp:
   p=Path(tmp)/'x.tar'
   with tarfile.open(p,'w') as t:
    i=tarfile.TarInfo('wp-content/plugins/a/file.txt');i.size=3;t.addfile(i,io.BytesIO(b'abc'))
   self.assertEqual(m.verify_archive(p,'wp-content/plugins/a'),1)
   with tarfile.open(p,'w') as t:
    i=tarfile.TarInfo('../escape');i.size=1;t.addfile(i,io.BytesIO(b'x'))
   with self.assertRaises(ValueError):m.verify_archive(p,'wp-content/plugins/a')
 def test_archive_hashes_match_contents(self):
  import hashlib
  with tempfile.TemporaryDirectory(dir=r'E:\破甲工作区\.tmp') as tmp:
   p=Path(tmp)/'x.tar'
   with tarfile.open(p,'w') as t:
    i=tarfile.TarInfo('wp-content/plugins/a/file.txt');i.size=3;t.addfile(i,io.BytesIO(b'abc'))
   self.assertEqual(m.archive_hashes(p,'wp-content/plugins/a'),{'file.txt':hashlib.sha256(b'abc').hexdigest()})
 def test_archive_rejects_symlink(self):
  with tempfile.TemporaryDirectory(dir=r'E:\破甲工作区\.tmp') as tmp:
   p=Path(tmp)/'x.tar'
   with tarfile.open(p,'w') as t:
    i=tarfile.TarInfo('wp-content/plugins/a/link');i.type=tarfile.SYMTYPE;i.linkname='/etc';t.addfile(i)
   with self.assertRaises(ValueError):m.verify_archive(p,'wp-content/plugins/a')
if __name__=='__main__':unittest.main()
