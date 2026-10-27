import unittest
from pathlib import Path
from tempfile import TemporaryDirectory
import catalog_refresh as c

class SafetyTests(unittest.TestCase):
    def test_exception_is_not_approved(self):
        row={'slug':'catalog-ring-box-O1CN01gdgp','分类目录':'x','交付文件名':'main.webp'}
        with TemporaryDirectory() as d:
            r = c.classify(row, Path(d), Path(d), None)
            self.assertEqual(r['conclusion'], '不通过')
    def test_unreviewed_image_is_never_approved(self):
        with TemporaryDirectory() as d:
            root=Path(d); (root/'x').mkdir(); (root/'x'/'main.webp').write_bytes(b'new'); (root/'catalog-a.webp').write_bytes(b'old')
            row={'slug':'catalog-a','分类目录':'x','交付文件名':'main.webp'}
            self.assertEqual(c.classify(row,root,root,None)['conclusion'], '需人工看')
    def test_changed_file_invalidates_review(self):
        with TemporaryDirectory() as d:
            root=Path(d); (root/'x').mkdir(); (root/'x'/'main.webp').write_bytes(b'new'); (root/'catalog-a.webp').write_bytes(b'old')
            row={'slug':'catalog-a','分类目录':'x','交付文件名':'main.webp'}
            old=c.classify(row,root,root,None); old['conclusion']='通过'; old['structure']='pass'; old['markings']='pass'; old['branding']='pass'; old['defects']='pass'
            (root/'x'/'main.webp').write_bytes(b'changed')
            self.assertEqual(c.classify(row,root,root,old)['conclusion'], '需人工看')
if __name__ == '__main__': unittest.main()
