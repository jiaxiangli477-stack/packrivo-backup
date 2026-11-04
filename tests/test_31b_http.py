import html,html.parser,re,unittest,urllib.error,urllib.request
BASE='http://localhost:8082'
class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self,*args,**kwargs): return None
def get(path):
    try: r=urllib.request.build_opener(NoRedirect()).open(BASE+path,timeout=120)
    except urllib.error.HTTPError as e:r=e
    with r:return r.status,dict(r.headers),r.read().decode('utf8','replace')
class Test31b(unittest.TestCase):
    def test_language_slash(self):
        s,h,_=get('/de/about?x=AbC');self.assertEqual(s,301);self.assertEqual(h.get('Location'),'/de/about/?x=AbC')
    def test_language_links(self):
        s,_,t=get('/');self.assertEqual(s,200)
        for code in ('de','fr','es','it','nl','pt','ar','ja','ko','zh-CN','zh-TW'):
            self.assertRegex(t,r'<a\b[^>]*href="[^\"]*/'+re.escape(code)+r'/"[^>]*hreflang="'+re.escape(code)+'"')
        footer=re.search(r'<footer\b[^>]*>(.*?)</footer>',t,re.I|re.S)
        self.assertIsNotNone(footer)
        self.assertIn('pk-languages',footer.group(1))
    def test_localized_sitemap_omits_noindex_privacy(self):
        s,_,t=get('/de-sitemap.xml');self.assertEqual(s,200)
        self.assertNotIn('/de/privacy/',t);self.assertIn('/de/about/',t)
    def test_topic(self):
        s,_,t=get('/blog/topic/materials-print/');self.assertEqual(s,200);self.assertIn('Materials &amp; print',t)
    def test_topic_adjacent_links_keep_topic(self):
        s,_,t=get('/blog/?topic=materials-print&pg=2');self.assertEqual(s,200)
        links={rel:html.unescape(url) for rel,url in re.findall(r'<link\s+rel="(prev|next)"\s+href="([^"]+)"',t)}
        self.assertEqual(links['prev'],BASE+'/blog/?topic=materials-print')
        self.assertEqual(links['next'],BASE+'/blog/?topic=materials-print&pg=3')
    def test_noscript_quote(self):
        s,_,t=get('/get-a-quote/');self.assertEqual(s,200);self.assertRegex(t,r'<h1\b')
        blocks=re.findall(r'<noscript>(.*?)</noscript>',t,re.I|re.S)
        self.assertTrue(any('mailto:' in b and 'wa.me/' in b for b in blocks))
    def test_category_redirect(self):
        s,h,_=get('/category/uncategorized/');self.assertEqual(s,301);self.assertEqual(h.get('Location'),BASE+'/category/guides/')
    def test_new_orphans(self):
        s,_,t=get('/jewelry-boxes/');self.assertEqual(s,200);self.assertIn('/drawer-box-with-ribbon/',t)
    def test_related_reading_is_bounded_and_includes_products(self):
        s,_,t=get('/2026/10/30/stock-custom-molded-pulp-insert-choice/');self.assertEqual(s,200)
        match=re.search(r'<section\b[^>]*data-pk31b-related[^>]*>(.*?)</section>',t,re.I|re.S)
        self.assertIsNotNone(match)
        links=re.findall(r'<a\b[^>]*href="([^"]+)"',match.group(1),re.I)
        self.assertGreaterEqual(len(links),3);self.assertLessEqual(len(links),5)
        self.assertTrue(any('/product-' in link for link in links))
if __name__=='__main__':unittest.main(verbosity=2)
