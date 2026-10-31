# -*- coding: utf-8 -*-
"""i963 fix (round 2): correct key for fr/product-folded-box-liner/ lead paragraph."""
import json, re, sys, html, hashlib, shutil, os
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

MERGED = r'E:\破甲工作区\packrivo-backup\deploy\i18n-merged'
UP = r'E:\破甲工作区\.tmp\28q\rehearsal\home\web\html\www.packrivo.com\wordpress\wp-content\uploads\packrivo-i18n'

def norm(s):
    return re.sub(r'\s+', ' ', s).strip()

def md5(p):
    return hashlib.md5(open(p, 'rb').read()).hexdigest()

p = os.path.join(MERGED, 'fr-dict.json')
d = json.load(open(p, encoding='utf-8'))

# remove the wrongly-encoded key added in round 1
bad = [k for k in d['block'] if 'folded box liner is an internal component' in k and '&#x27;' in k]
print('bad keys removed:', len(bad))
for k in bad:
    del d['block'][k]

h = open(r'E:\Ai\ARTEX-Portable\app\data\tasks\67\i963\fr-folded-box-liner.html', encoding='utf-8', errors='replace').read()
m = re.search(r'<p class="lead">(.*?)</p>', h, re.S)
key = norm(html.unescape(re.sub(r'<[^>]+>', '', m.group(1))))
print('KEY:', repr(key))

VALUE = ("Une doublure de boîte pliée est un composant interne formé contre les parois ou des "
         "surfaces sélectionnées d'une boîte. Envisagez cette orientation lorsque le cahier des "
         "charges doit décrire où le produit ou son emballage de vente rencontre l'enceinte "
         "environnante. Planifiez d'abord la couverture, les plis et la séquence de chargement de "
         "la doublure, puis décidez si un support séparé est nécessaire pour positionner le contenu.")

before = len(d['block'])
if key in d['block']:
    d['block'][key] = VALUE
    print('overwritten existing')
else:
    d['block'][key] = VALUE
    print('added new')
after = len(d['block'])
print('block count:', before, '->', after)

with open(p, 'w', encoding='utf-8', newline='\n') as fh:
    json.dump(d, fh, ensure_ascii=False, indent=2, sort_keys=False)
    fh.write('\n')
raw = open(p, 'rb').read()
assert not raw.startswith(b'\xef\xbb\xbf') and b'\r\n' not in raw, 'BOM/CRLF leak!'
d2 = json.load(open(p, encoding='utf-8'))
print('json.load OK; md5:', md5(p))
shutil.copyfile(p, os.path.join(UP, 'fr-dict.json'))
print('drill synced; drill md5:', md5(os.path.join(UP, 'fr-dict.json')))
