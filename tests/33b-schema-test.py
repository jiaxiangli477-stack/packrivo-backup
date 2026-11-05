"""Validate three local 33b JSON-LD graphs against visible text and WP pricing.

The only network target is the explicitly configured localhost rehearsal.
Missing AggregateOffer is a failure even when it correctly avoids an invented
price, so the acceptance report cannot silently convert an incomplete case to a
pass. The program performs no database or site writes.
"""
import collections
import html
import json
import re
import subprocess
import sys
from html.parser import HTMLParser
from urllib.parse import urlsplit
from urllib.request import urlopen

BASE = 'http://127.0.0.1:8087'
SLUGS = [
    'product-magnetic-jewelry-box',
    'product-drawer-jewelry-box',
    'product-collapsible-magnetic-gift-box',
]


class Page(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.in_json = False
        self.json_buf = []
        self.scripts = []
        self.images = []
        self.in_nav = False
        self.nav_depth = 0
        self.nav_buf = []
        self.nav_links = []
        self.in_faq = False
        self.faq_depth = 0
        self.in_summary = False
        self.in_answer = False
        self.question_buf = []
        self.answer_buf = []
        self.questions = []

    def handle_starttag(self, tag, attrs):
        d = dict(attrs)
        if tag == 'script' and d.get('type') == 'application/ld+json':
            self.in_json = True
            self.json_buf = []
        if tag == 'img' and d.get('src'):
            self.images.append(d['src'])
        if tag == 'nav' and 'pk33b-breadcrumb' in d.get('class', '').split():
            self.in_nav, self.nav_depth = True, 1
        elif self.in_nav:
            self.nav_depth += 1
        if self.in_nav and tag == 'a' and d.get('href'):
            self.nav_links.append(d['href'])
        if tag == 'div' and 'pk33b-faq' in d.get('class', '').split():
            self.in_faq, self.faq_depth = True, 1
        elif self.in_faq:
            self.faq_depth += 1
        if self.in_faq and tag == 'summary':
            self.in_summary, self.question_buf = True, []
        if self.in_faq and tag == 'p':
            self.in_answer, self.answer_buf = True, []

    def handle_endtag(self, tag):
        if tag == 'script' and self.in_json:
            self.scripts.append(''.join(self.json_buf))
            self.in_json = False
        if self.in_faq and tag == 'summary':
            self.in_summary = False
        if self.in_faq and tag == 'p':
            self.in_answer = False
        if self.in_faq and tag == 'details':
            self.questions.append((norm(''.join(self.question_buf)), norm(''.join(self.answer_buf))))
        if self.in_nav:
            self.nav_depth -= 1
            if self.nav_depth == 0:
                self.in_nav = False
        if self.in_faq:
            self.faq_depth -= 1
            if self.faq_depth == 0:
                self.in_faq = False

    def handle_data(self, data):
        if self.in_json:
            self.json_buf.append(data)
        if self.in_nav:
            self.nav_buf.append(data)
        if self.in_summary:
            self.question_buf.append(data)
        if self.in_answer:
            self.answer_buf.append(data)


def norm(value):
    return re.sub(r'\s+', ' ', html.unescape(str(value))).strip()


def walk(obj, found):
    if isinstance(obj, dict):
        kinds = obj.get('@type', [])
        kinds = kinds if isinstance(kinds, list) else [kinds]
        for kind in kinds:
            if isinstance(kind, str):
                found[kind].append(obj)
        for value in obj.values():
            walk(value, found)
    elif isinstance(obj, list):
        for value in obj:
            walk(value, found)


def get_wp_facts():
    php = r'''$slugs=["product-magnetic-jewelry-box","product-drawer-jewelry-box","product-collapsible-magnetic-gift-box"];
foreach($slugs as $slug){$p=get_page_by_path($slug);$d=$p?pk33b_get_product($p->ID):null;
$hub=$d && $d["category_hub"]?get_page_by_path($d["category_hub"]):null;
echo wp_json_encode(["slug"=>$slug,"h1"=>$d["h1"]??null,"lede"=>$d["lede"]??null,
"hub"=>$hub?get_the_title($hub):null,"faq"=>$d["faq"]??[],"rows"=>$d?pk33b_price_rows($d,0):[]],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";}'''
    completed = subprocess.run(
        ['docker', 'exec', 'packrivo-33b-wp', 'php', '/tmp/wp', 'eval', php, '--allow-root'],
        check=True, capture_output=True, text=True, encoding='utf-8', timeout=45,
    )
    values = [json.loads(line) for line in completed.stdout.splitlines() if line.strip()]
    return {entry['slug']: entry for entry in values}


def main():
    facts = get_wp_facts()
    overall_errors = 0
    for slug in SLUGS:
        errors = []
        with urlopen(BASE + '/' + slug + '/', timeout=30) as response:
            status = response.status
            content = response.read().decode('utf-8')
        page = Page()
        page.feed(content)
        graphs = collections.defaultdict(list)
        for block in page.scripts:
            try:
                walk(json.loads(block), graphs)
            except (json.JSONDecodeError, TypeError) as exc:
                errors.append('invalid JSON-LD: ' + str(exc))
        for kind in ('Product', 'BreadcrumbList', 'FAQPage'):
            if len(graphs[kind]) != 1:
                errors.append(f'{kind} count={len(graphs[kind])}, expected 1')
        fact = facts.get(slug)
        if not fact or not fact['h1']:
            errors.append('WP product data unavailable')
            fact = {'h1': '', 'lede': '', 'hub': '', 'faq': [], 'rows': []}
        offer_detail = 'none'
        if graphs['Product']:
            product = graphs['Product'][0]
            brand = product.get('brand')
            if norm(product.get('name', '')) != norm(fact['h1']):
                errors.append('Product.name mismatch')
            if norm(product.get('description', '')) != norm(fact['lede']):
                errors.append('Product.description mismatch')
            if not isinstance(brand, dict) or brand.get('@type') != 'Brand' or brand.get('name') != 'Packrivo':
                errors.append('Product.brand mismatch')
            images = product.get('image', [])
            images = images if isinstance(images, list) else [images]
            if not images:
                errors.append('Product.image missing')
            for image in images:
                if not isinstance(image, str) or image not in page.images:
                    errors.append('Product.image is not present in visible gallery')
            for forbidden in ('sku', 'mpn', 'hasCertification', 'certification'):
                if forbidden in product:
                    errors.append('Product contains forbidden field ' + forbidden)
            if re.search(r'\b(?:ISO|ISTA|SGS|FSC|REACH|N52)\b|Prop\s*65|ΔE|±\s*0\.5\s*mm', json.dumps(product, ensure_ascii=False), re.I):
                errors.append('Product contains an unsupported certification or measurement claim')
            offer = product.get('offers')
            rows = list(fact['rows'].values()) if isinstance(fact['rows'], dict) else fact['rows']
            if not isinstance(offer, dict):
                errors.append('AggregateOffer missing; source dimensions/material may be incomplete')
            else:
                if offer.get('@type') != 'AggregateOffer' or offer.get('priceCurrency') != 'USD':
                    errors.append('AggregateOffer type/currency mismatch')
                if not rows:
                    errors.append('AggregateOffer exists without source-bound price rows')
                else:
                    units = [float(row['unit']) for row in rows]
                    try:
                        low, high, count = float(offer['lowPrice']), float(offer['highPrice']), int(offer['offerCount'])
                        offer_detail = f'USD low={low:.2f} high={high:.2f} expected={min(units):.2f}/{max(units):.2f} tiers={len(units)}'
                        if abs(low - min(units)) > 0.001:
                            errors.append('AggregateOffer.lowPrice mismatch')
                        if abs(high - max(units)) > 0.001:
                            errors.append('AggregateOffer.highPrice mismatch')
                        if count != len(units):
                            errors.append('AggregateOffer.offerCount mismatch')
                    except (KeyError, TypeError, ValueError):
                        errors.append('AggregateOffer price/count missing or invalid')
        if graphs['BreadcrumbList']:
            entries = graphs['BreadcrumbList'][0].get('itemListElement', [])
            names = [norm(entry.get('name', '')) for entry in entries]
            expected = ['Home'] + ([norm(fact['hub'])] if fact.get('hub') else []) + [norm(fact['h1'])]
            visible = norm(' '.join(page.nav_buf))
            if names != expected:
                errors.append('BreadcrumbList names mismatch: ' + repr(names))
            if any(name not in visible for name in expected):
                errors.append('BreadcrumbList differs from visible trail')
            if [entry.get('position') for entry in entries] != list(range(1, len(entries) + 1)):
                errors.append('BreadcrumbList positions invalid')
            if len(page.nav_links) != max(0, len(entries) - 1):
                errors.append('BreadcrumbList link count differs from visible trail')
            else:
                for entry, href in zip(entries[:-1], page.nav_links):
                    # 127.0.0.1 and localhost are the same isolated instance;
                    # compare actual path and query without contacting either URL.
                    schema_url = urlsplit(str(entry.get('item', '')))
                    visible_url = urlsplit(href)
                    if not schema_url.path or (schema_url.path, schema_url.query) != (visible_url.path, visible_url.query):
                        errors.append('BreadcrumbList URL differs from visible trail')
        if graphs['FAQPage']:
            qas = graphs['FAQPage'][0].get('mainEntity', [])
            schema_qas = [(norm(q.get('name', '')), norm(q.get('acceptedAnswer', {}).get('text', ''))) for q in qas]
            if schema_qas != page.questions:
                errors.append('FAQPage does not match visible questions and answers')
        overall_errors += len(errors)
        print(slug, 'HTTP', status, 'JSON_BLOCKS', len(page.scripts),
              'PRODUCT', len(graphs['Product']), 'BREADCRUMB', len(graphs['BreadcrumbList']),
              'FAQ', len(graphs['FAQPage']), 'SERVICE', len(graphs['Service']),
              'OFFER', len(graphs['AggregateOffer']), 'OFFER_DETAIL', offer_detail, 'ERRORS', len(errors))
        for error in errors:
            print('  FAIL', error)
    print('TOTAL_PAGES', len(SLUGS), 'ERRORS', overall_errors)
    return 1 if overall_errors else 0


if __name__ == '__main__':
    sys.exit(main())
