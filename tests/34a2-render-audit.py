#!/usr/bin/env python3
"""Compare every fetched 34a page with the 141-row map and report redlines."""
import argparse
import csv
import html
from html.parser import HTMLParser
import json
import re
import sys
from pathlib import Path
from urllib.parse import urlsplit

FIELDS = (('title','新 title'), ('meta','新 meta'), ('h1','新 H1'))
HUB_PATHS = {'/','/products/','/jewelry-boxes/','/mailer-boxes/','/samples/','/blog/','/about/','/inquiry/'}
PATTERNS = {
    'moq_500': re.compile(r'\bMOQ\s*:?\s*500\b', re.I),
    'standards': re.compile(r'\b(?:ISO|ISTA|FSC)(?=$|[^A-Za-z])', re.I),
    'factory_any': re.compile(r'\bfactor(?:y|ies)\b', re.I),
    'factory_self_claim': re.compile(r'\b(?:we\s+(?:are|own|operate|run)|our|packrivo\s+(?:is|owns|operates|runs))\s+(?!not\b|no\b|neither\b|suppliers?\b|partners?\b)(?:(?:an?|the|own)\s+)?(?:[\w-]+\s+){0,3}factor(?:y|ies)\b', re.I),
}


def normalize(value):
    return re.sub(r'\s+', ' ', html.unescape(str(value or ''))).strip()


def redlines(value):
    text = normalize(value)
    return {key: len(pattern.findall(text)) for key,pattern in PATTERNS.items()}


def compare_page(page, row, probe=None):
    differences = []
    for field, column in FIELDS:
        expected = row[column]
        if field in ('title', 'meta'):
            expected = re.sub(r'(\bMOQ\s*:?\s*)500\b', r'\g<1>250', expected, flags=re.I)
        actual = page.get(field, '')
        if page.get('status') != 200 or normalize(actual) != normalize(expected):
            path = page['path']
            if page.get('status') != 200:
                reason = f"HTTP {page.get('status')}，{page.get('error') or '未获取可比页面'}"
            elif path == '/' and field == 'h1':
                reason = '首页 H1 按决定保留 & 和句号'
            elif path == '/samples/' and field == 'h1':
                reason = '父主题 H1 不在仓库，本轮保留'
            elif page.get('initial_status') in (301,302,303,307,308):
                reason = '原 URL 重定向，结果来自目标页'
            elif path == '/get-a-quote/':
                reason = '主题 inc/quote-page.php 的实际输出与词表不同'
            elif probe and path in probe:
                item=probe[path]; fields=item.get('fields',{})
                def meta(key):
                    values=fields.get(key,[])
                    return values[0].get('meta_value','') if values else ''
                if field=='title':
                    db_value=(meta('_packrivo_document_title') if path in HUB_PATHS else '') or meta('_yoast_wpseo_title')
                elif field=='meta':
                    db_value=(meta('_packrivo_description') if path in HUB_PATHS else '') or meta('_yoast_wpseo_metadesc')
                else:
                    match=re.search(r'<h1\b[^>]*>(.*?)</h1>',item.get('content',''),re.I|re.S)
                    db_value=re.sub(r'<[^>]+>',' ',match.group(1)) if match else ''
                if normalize(db_value)==normalize(expected):
                    reason='数据库对应字段已匹配，渲染由主题或插件覆盖'
                else:
                    reason='数据库有效字段也与词表不同，需核对冲突或字段来源'
            else:
                reason = '渲染值与词表不同，需核对实际输出来源'
            differences.append({'path':path,'field':field,'actual':normalize(actual),'expected':normalize(expected),'reason':reason})
    return differences


class VisibleText(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.hidden = 0
        self.parts = []

    def handle_starttag(self, tag, attrs):
        if tag in ('script','style','svg','template','noscript'):
            self.hidden += 1

    def handle_endtag(self, tag):
        if tag in ('script','style','svg','template','noscript') and self.hidden:
            self.hidden -= 1

    def handle_data(self, data):
        if not self.hidden:
            self.parts.append(data)


def main():
    p=argparse.ArgumentParser()
    p.add_argument('map',type=Path)
    p.add_argument('rendered',type=Path)
    p.add_argument('--out',type=Path,required=True)
    p.add_argument('--probe',type=Path,help='Optional post-migration field snapshot')
    args=p.parse_args()
    with args.map.open(encoding='utf-8-sig',newline='') as f:
        rows=list(csv.DictReader(f))
    pages=[json.loads(line) for line in args.rendered.read_text(encoding='utf8').splitlines() if line.strip()]
    lookup={p['path']:p for p in pages}
    probe=json.loads(args.probe.read_text(encoding='utf8')) if args.probe else None
    if len(rows)!=141 or len(pages)!=141 or len(lookup)!=141:
        print(f'INPUT_ERROR map={len(rows)} rendered={len(pages)} unique={len(lookup)}')
        return 2
    differences=[]; field_counts={k:0 for k in PATTERNS}; body_counts={k:0 for k in PATTERNS}; findings=[]
    for row in rows:
        path=urlsplit(row['url']).path or '/'
        if not path.endswith('/'):path+='/'
        page=lookup.get(path)
        if page is None:
            print('INPUT_ERROR missing',path);return 2
        differences.extend(compare_page(page,row,probe))
        field_text=' '.join(str(page.get(k,'')) for k,_ in FIELDS)
        visible=VisibleText();visible.feed(page.get('html',''))
        fields=redlines(field_text);body=redlines(' '.join(visible.parts))
        for key in PATTERNS:
            field_counts[key]+=fields[key];body_counts[key]+=body[key]
        if any(fields.values()) or any(body.values()):
            findings.append({'path':path,'fields':fields,'body':body})
    result={'pages':len(rows),'checked_fields':len(rows)*3,'mismatches':differences,
            'field_redlines':field_counts,'visible_page_redlines':body_counts,'findings':findings,
            'redirects':[{'path':p['path'],'status':p['initial_status'],'location':p.get('location',''),'local_target':p.get('final_url','')} for p in pages if p.get('initial_status') in (301,302,303,307,308)],
            'non_200':[{'path':p['path'],'status':p.get('status'),'error':p.get('error','')} for p in pages if p.get('status')!=200]}
    args.out.write_text(json.dumps(result,ensure_ascii=False,indent=2),encoding='utf8')
    print(f"pages={len(rows)} checked_fields={len(rows)*3} mismatch={len(differences)} redirects={len(result['redirects'])} non_200={len(result['non_200'])}")
    for x in result['redirects']:print('REDIRECT',json.dumps(x,ensure_ascii=False))
    for x in differences:print('MISMATCH',json.dumps(x,ensure_ascii=False))
    print('FIELD_REDLINE',json.dumps(field_counts,ensure_ascii=False))
    print('VISIBLE_PAGE_REDLINE',json.dumps(body_counts,ensure_ascii=False))
    for x in findings:print('REDLINE_PAGE',json.dumps(x,ensure_ascii=False))
    hard_redlines=('moq_500','standards','factory_self_claim')
    return 1 if differences or any(field_counts[k] for k in hard_redlines) or any(body_counts[k] for k in hard_redlines) or result['non_200'] else 0


if __name__ == '__main__':
    sys.exit(main())
