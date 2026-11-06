import re
import unittest
import urllib.error
import urllib.request

BASE = 'http://localhost:8081'

class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None

def get(path):
    opener = urllib.request.build_opener(NoRedirect())
    try:
        response = opener.open(BASE + path, timeout=90)
    except urllib.error.HTTPError as exc:
        response = exc
    with response:
        return response.status, response.headers, response.read().decode('utf-8', 'replace')

class Routes(unittest.TestCase):
    def test_catalog_html_hrefs_are_lowercase(self):
        status, _, body = get('/catalog-gift-box/')
        self.assertEqual(status, 200)
        links = re.findall(r'<a\b[^>]*\bhref=["\']([^"\']+)', body, re.I)
        bad = [x for x in links if re.search(r'/catalog-[^/?#]*[A-Z]', x)]
        self.assertEqual(bad, [])

    def test_uppercase_preserves_query(self):
        status, headers, _ = get('/PRIVACY/?x=AbC&x=2')
        self.assertEqual((status, headers.get('Location')), (301, BASE + '/privacy/?x=AbC&x=2'))

    def test_uppercase_page_without_trailing_slash_redirects_once(self):
        status, headers, _ = get('/PRIVACY?x=AbC')
        self.assertEqual((status, headers.get('Location')), (301, BASE + '/privacy/?x=AbC'))

    def test_blog_pagination_redirect(self):
        status, headers, _ = get('/blog/page/2/')
        self.assertEqual((status, headers.get('Location')), (301, BASE + '/blog/?pg=2'))

    def test_blog_pagination_canonical(self):
        status, _, body = get('/blog/?pg=2')
        self.assertEqual(status, 200)
        self.assertIsNotNone(re.search(r'<link rel="canonical" href="http://localhost:8081/blog/\?pg=2"', body))

    def test_removed_redirect_from_sitemap(self):
        status, _, body = get('/page-sitemap.xml')
        self.assertEqual(status, 200)
        self.assertFalse('<loc>http://localhost:8081/collapsible-magnetic-gift-box/</loc>' in body)

    def test_collapsible_redirect(self):
        status, headers, _ = get('/collapsible-magnetic-gift-box/')
        self.assertEqual((status, headers.get('Location')), (301, BASE + '/product-collapsible-magnetic-gift-box/'))

    def test_existing_candle_words_link_to_candle_page(self):
        status, _, body = get('/foldable-gift-boxes/')
        self.assertEqual(status, 200)
        self.assertIn('A paperboard direction for <a href="http://localhost:8081/candle-gift-set-box/">candles</a> and small retail gifts.', body)

    def test_existing_thank_you_words_link_to_card_page(self):
        status, _, body = get('/inserts/')
        self.assertEqual(status, 200)
        self.assertIn('A printed layer for instructions, care or a <a href="http://localhost:8081/paper-insert-card/">thank-you message</a>.', body)

    def test_drawer_words_do_not_link_to_ribbon_example(self):
        status, _, body = get('/jewelry-boxes/')
        self.assertEqual(status, 200)
        self.assertNotIn('href="http://localhost:8081/drawer-box-with-ribbon/"', body)
        self.assertIn('Plan a custom drawer jewelry box around the complete jewelry set.', body)
        self.assertGreaterEqual(len(re.findall(r'<a\b[^>]*href=["\'][^"\']*/product-drawer-jewelry-box/', body, re.I)), 4)

    def test_journal_adjacent_pagination(self):
        status, _, first_page = get('/blog/')
        self.assertEqual(status, 200)
        page_numbers = [int(n) for n in re.findall(r'<a\b[^>]*href=["\']http://localhost:8081/blog/\?pg=(\d+)', first_page, re.I)]
        self.assertTrue(page_numbers, 'Journal pagination has no numbered links')
        last = max(page_numbers)
        self.assertGreater(last, 3, 'This test requires at least four journal pages')
        expected = {
            '/blog/': (None, BASE + '/blog/?pg=2'),
            '/blog/?pg=2': (BASE + '/blog/', BASE + '/blog/?pg=3'),
            '/blog/?pg=3': (BASE + '/blog/?pg=2', BASE + '/blog/?pg=4'),
            f'/blog/?pg={last}': (BASE + f'/blog/?pg={last-1}', None),
        }
        for path, (previous, following) in expected.items():
            with self.subTest(path=path):
                status, _, body = get(path)
                self.assertEqual(status, 200)
                found = re.findall(r'<link\s+rel="(prev|next)"\s+href="([^"]+)"', body)
                self.assertEqual([url for rel, url in found if rel == 'prev'], [previous] if previous else [])
                self.assertEqual([url for rel, url in found if rel == 'next'], [following] if following else [])

if __name__ == '__main__':
    unittest.main(verbosity=2)
