# -*- coding: utf-8 -*-
"""i963: generalized frag-key derivation for remaining Latin languages (es/it/nl/pt).

Mirrors the verified fr approach (i961/derive-fr3.py): every residual concatenated
sentence already has a translation in the language dict (orchestrator translated the
walker segments); the plugin only misses because the page text node ≠ concatenated
key. We derive fragment keys (strip EN leading prefix) with values cut at the
translated verb, plus explicit splits and manual entries.

DRY RUN: emits {lang}-plan.json + report only. No dict writes. The apply step
(separate script) merges on top of the current workspace dict, backs up .bak,
json.load/key-count/md5 checks, copyfile -> drill, reset-opcache, targeted rescan.
"""
import csv, json, re, sys, os
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

UP = r'E:\破甲工作区\.tmp\28q\rehearsal\home\web\html\www.packrivo.com\wordpress\wp-content\uploads\packrivo-i18n'
CSV = r'E:\Ai\ARTEX-Portable\app\data\tasks\67\i907\residual-after-29y.csv'

def norm(s):
    return re.sub(r'\s+', ' ', s).strip()

EN_PREFIXES = [
    'Packaging Corporation of America\'s ', 'PakFactory\'s ', 'Packrivo\'s ', 'Novolex\'s ',
    'The ', 'A ', 'An ', 'This ', 'That ', 'Review ',
]

LANG_CFG = {
    'es': {
        'verb': {
            'describes': ['describe', 'describen', 'alude', 'aluden'], 'establishes': ['establece', 'establecen'],
            'identifies': ['identifica', 'identifican'], 'names': ['denomina', 'denominan', 'nombra', 'nombran', 'designa', 'indica', 'especifica'],
            'distinguishes': ['distingue', 'distinguen'], 'adds': ['añade', 'añaden', 'agrega', 'agregan'],
            'shows': ['muestra', 'muestran'], 'confirms': ['confirma', 'confirman'],
            'illustrates': ['ilustra', 'ilustran', 'presenta', 'presentan', 'muestra'], 'covers': ['cubre', 'cubren'],
            'needs': ['requiere', 'requieren', 'necesita', 'necesitan'], 'places': ['coloca', 'colocan', 'sitúa', 'sitúan'],
            'supplies': ['proporciona', 'proporcionan', 'aporta', 'aportan', 'suministra', 'suministran'],
            'has': ['tiene', 'tienen', 'presenta', 'presentan', 'posee', 'poseen'],
            'is': ['es', 'son', 'está', 'están'], 'expands': ['amplía', 'amplían', 'extiende', 'extienden', 'desarrolla'],
            'addresses': ['aborda', 'abordan', 'trata', 'tratan'], 'should': ['debe', 'deben', 'debería', 'deberían'],
            'may': ['puede', 'pueden'], 'keep': ['conserve', 'conserven', 'mantenga', 'mantengan', 'guarde', 'guarden'],
            'review': ['revise', 'revise', 'revisa'],
        },
        'subj': r'^(?:El término|El texto|La referencia|Esta página|La descripción|La solución|La orientación|Esta sección|La ilustración|La denominación|La nomenclatura)\s+',
        'art': r'^(?:el|la|los|las|un|una|unos|unas|este|esta|estos|estas|esto)\s+',
        'brand': re.compile(r'^(?:PakFactory|Packrivo|Novolex|Packaging Corporation of America)(?:[\u2019\x27]s)?\s+', re.I),
    },
    'it': {
        'verb': {
            'describes': ['descrive', 'descrivono', 'illustra', 'illustrano', 'indica', 'indicano', 'riguarda'],
            'establishes': ['stabilisce', 'stabiliscono'], 'identifies': ['identifica', 'identificano', 'individua', 'individuano'],
            'names': ['denomina', 'denominano', 'nomina', 'nominano', 'indica', 'identifica'], 'distinguishes': ['distingue', 'distinguono'],
            'adds': ['aggiunge', 'aggiungono'], 'shows': ['mostra', 'mostrano'], 'confirms': ['conferma', 'confermano'],
            'illustrates': ['illustra', 'illustrano', 'presenta', 'presentano', 'mostra'], 'covers': ['copre', 'coprono'],
            'needs': ['richiede', 'richiedono', 'necessita', 'necessitano'], 'places': ['colloca', 'collocano', 'posiziona', 'posizionano'],
            'supplies': ['fornisce', 'forniscono', 'fornisce'], 'has': ['ha', 'hanno', 'presenta', 'presentano', 'possiede', 'possiedono'],
            'is': ['è', 'sono'], 'expands': ['amplia', 'ampliano', 'estende', 'estendono', 'sviluppa'],
            'addresses': ['affronta', 'affrontano', 'tratta', 'trattano'], 'should': ['deve', 'devono', 'dovrebbe', 'dovrebbero'],
            'may': ['può', 'possono'], 'keep': ['conservi', 'conservino', 'mantenga', 'mantengano', 'tenga', 'tengano'],
            'review': ['riveda', 'verifichi'],
        },
        'subj': r'^(?:Il termine|La descrizione|La definizione|La soluzione|Questa pagina|Questa sezione|L\'orientamento|L\'illustrazione|La nomenclatura(?: di PakFactory)?|Il |La )\s*',
        'art': r'^(?:il|lo|la|i|gli|le|un|uno|una|questo|questa|questi|queste|l[\u2019\x27])\s+',
        'brand': re.compile(r'^(?:PakFactory|Packrivo|Novolex|Packaging Corporation of America)(?:[\u2019\x27]s)?\s+', re.I),
    },
    'nl': {
        'verb': {
            'describes': ['beschrijft', 'beschrijven', 'behandelt'], 'establishes': ['legt', 'bevestigt', 'stelt'],
            'identifies': ['identificeert', 'identificeren', 'duidt', 'duiden', 'benoemt'], 'names': ['benoemt', 'benoemen', 'noemt', 'noemen'],
            'distinguishes': ['onderscheidt', 'onderscheiden'], 'adds': ['voegt', 'voegen'],
            'shows': ['toont', 'tonen', 'laat', 'laten'], 'confirms': ['bevestigt', 'bevestigen'],
            'illustrates': ['illustreert', 'illustreren', 'toont'], 'covers': ['dekt', 'dekken', 'beslaat', 'beslaan', 'omvat'],
            'needs': ['vereist', 'vereisen', 'heeft', 'hebben'], 'places': ['plaatst', 'plaatsen'],
            'supplies': ['levert', 'leveren', 'biedt', 'bieden'], 'has': ['heeft', 'hebben', 'beschikt'],
            'is': ['is', 'zijn'], 'expands': ['breidt', 'breiden', 'verruimt', 'staat'],
            'addresses': ['behandelt', 'behandelen', 'gaat', 'gaan'], 'should': ['moet', 'moeten', 'dient', 'dienen'],
            'may': ['kan', 'kunnen'], 'keep': ['bewaar', 'bewaren', 'houd', 'houden', 'behoud', 'behouden'],
            'review': ['beoordeel', 'controleer'],
        },
        'subj': r'^(?:Dit|Deze|Het|De|Dat)\s+',
        'art': r'^(?:de|het|een|dit|deze|dat)\s+',
        'brand': re.compile(r'^(?:PakFactory|Packrivo|Novolex|Packaging Corporation of America)(?:[\u2019\x27]s)?\s+', re.I),
    },
    'pt': {
        'verb': {
            'describes': ['descreve', 'descrevem'], 'establishes': ['estabelece', 'estabelecem'],
            'identifies': ['identifica', 'identificam'], 'names': ['designa', 'designam', 'nomeia', 'nomeiam', 'indica'],
            'distinguishes': ['distingue', 'distinguem'], 'adds': ['adiciona', 'adicionam', 'acrescenta', 'acrescentam'],
            'shows': ['mostra', 'mostram'], 'confirms': ['confirma', 'confirmam'],
            'illustrates': ['ilustra', 'ilustram', 'apresenta', 'apresentam'], 'covers': ['abrange', 'abrangem', 'cobre', 'cobrem'],
            'needs': ['requer', 'requerem', 'necessita', 'necessitam', 'precisa', 'precisam'],
            'places': ['coloca', 'colocam', 'posiciona', 'posicionam', 'integra'], 'supplies': ['fornece', 'fornecem', 'oferece', 'oferecem'],
            'has': ['tem', 'têm', 'apresenta', 'apresentam', 'possui', 'possuem'],
            'is': ['é', 'são', 'está', 'estão'], 'expands': ['amplia', 'ampliam', 'estende', 'estendem'],
            'addresses': ['aborda', 'abordam', 'trata', 'tratam'], 'should': ['deve', 'devem', 'deveria', 'deveriam'],
            'may': ['pode', 'podem'], 'keep': ['conserve', 'conservem', 'mantenha', 'mantenham', 'guarde', 'guardem'],
            'review': ['reveja', 'revise'],
        },
        'subj': r'^(?:O termo|A referência|A descrição|A solução|Esta página|Esta seção|Este |Esta )\s*',
        'art': r'^(?:o|a|os|as|um|uma|uns|umas|este|esta|estes|estas|isto)\s+',
        'brand': re.compile(r'^(?:PakFactory|Packrivo|Novolex|Packaging Corporation of America)(?:[\u2019\x27]s)?\s+', re.I),
    },
}

def cut_v(v, head_words, verb_map):
    for w in head_words[:6]:
        cands = verb_map.get(w)
        if not cands:
            continue
        for c in cands:
            m = re.search(r'\b' + re.escape(c) + r'\b', v, re.I)
            if m:
                return m.start()
    return -1

def build_plan(lang):
    cfg = LANG_CFG[lang]
    d = json.load(open(os.path.join(UP, lang + '-dict.json'), encoding='utf-8'))
    blk = {norm(k): v for k, v in d['block'].items()}
    txt = {norm(k): v for k, v in d['text'].items()}
    rows = list(csv.DictReader(open(CSV, encoding='utf-8-sig')))
    seen = {}
    for r in rows:
        if r['lang'] != lang or '/blog/' in r['url']:
            continue
        s = norm(r['sentence'])
        if int(r['en_words']) > 5:
            seen.setdefault(s, r['url'])
    # if no rows yet for this lang (pt), use shared sentences from es/fr/it/nl sets
    if not seen:
        for r in rows:
            if r['lang'] not in ('es', 'fr', 'it', 'nl') or '/blog/' in r['url']:
                continue
            s = norm(r['sentence'])
            if int(r['en_words']) > 5:
                seen.setdefault(s, r['url'])
    plan, problems, manual = [], [], []
    for s, url in seen.items():
        v = blk.get(s) or txt.get(s)
        if v is None:
            problems.append((s, None, url, 'no-translation'))
            continue
        v = norm(str(v))
        if v == s or v.lower() == s.lower():
            continue
        if url.endswith('/molded-pulp-insert/') and (s.startswith('Show the insert') or s.startswith('If the product has')):
            continue
        pre = next((p for p in EN_PREFIXES if s.startswith(p)), None)
        if pre is None:
            problems.append((s, v, url, 'no-en-prefix'))
            continue
        fk = norm(s[len(pre):])
        vv = cfg['brand'].sub('', v, count=1)
        vv = re.sub(cfg['subj'], '', vv, count=1)
        vv = re.sub(cfg['art'], '', vv, count=1)
        i = cut_v(vv, fk.split(), cfg['verb'])
        if i < 0:
            problems.append((s, v, url, 'no-verb-hit'))
            continue
        fv = norm(vv[i:])
        if fv == norm(s[len(pre):]) or fv.lower() == norm(s[len(pre):]).lower():
            problems.append((s, v, url, 'frag-value-still-english'))
            continue
        plan.append({'kind': 'frag', 'key': fk, 'value': fv, 'url': url, 'src': s[:60]})
    # molded-pulp explicit splits
    for s, url in seen.items():
        if not url.endswith('/molded-pulp-insert/'):
            continue
        v = norm(blk.get(s) or txt.get(s) or '')
        if not v or v == s:
            continue
        if s.startswith('Show the insert'):
            parts = re.split(r'(?<=\.)\s+', s)
            vp = re.split(r'(?<=\.)\s+', v)
            if len(parts) >= 3 and len(vp) >= 3:
                k1 = ' '.join(parts[:2]); v1 = ' '.join(vp[:2])
                mid = norm(parts[2])
                if mid.startswith('The '):
                    mid = norm(mid[4:])
                vmid = norm(re.sub(cfg['subj'], '', vp[2], count=1))
                plan.append({'kind': 'split', 'key': norm(k1), 'value': norm(v1), 'url': url, 'src': s[:50]})
                plan.append({'kind': 'split', 'key': mid, 'value': vmid, 'url': url, 'src': s[:50]})
                if len(parts) > 3:
                    plan.append({'kind': 'split', 'key': norm(' '.join(parts[3:])),
                                 'value': norm(re.sub(cfg['subj'], '', ' '.join(vp[3:]), count=1)), 'url': url, 'src': s[:50]})
            else:
                problems.append((s[:80], v[:80], url, 'show-split-shape'))
        elif s.startswith('If the product has'):
            parts = re.split(r'(?<=\.)\s+', s)
            vp = re.split(r'(?<=\.)\s+', v)
            if len(parts) >= 2 and len(vp) >= 2:
                k1, k2 = norm(' '.join(parts[:-1])), norm(parts[-1])
                v1 = norm(' '.join(vp[:-1]))
                v2 = norm(re.sub(cfg['subj'], '', vp[-1], count=1))
                plan.append({'kind': 'split', 'key': k1, 'value': v1, 'url': url, 'src': s[:50]})
                plan.append({'kind': 'split', 'key': k2, 'value': v2, 'url': url, 'src': s[:50]})
            else:
                problems.append((s[:80], v[:80], url, 'if-split-shape'))
    # manual: corrugated-book-wrap-mailer 'A is loaded into...' (leading anchor replacement)
    for s, url in seen.items():
        if s.startswith('A is loaded into'):
            v = norm(blk.get(s) or txt.get(s) or '')
            if v and v != s:
                vv = re.sub(r'^(?:A|Um produto|Un prodotto|Un)\s+', '', v, count=1)
                manual.append({'kind': 'manual', 'key': norm(s[2:]), 'value': norm(vv), 'url': url, 'src': s[:50]})
    # manual: compare brand titles (identity is fine? no — need translation; use 'vs' -> per lang)
    for s, url in seen.items():
        if '/compare/' in url or 'packrivo-vs-' in url or 'vs-pack' in url:
            if s.endswith('vs Packrivo'):
                v = norm(blk.get(s) or '')
                if v and v != s:
                    manual.append({'kind': 'manual', 'key': s, 'value': v, 'url': url, 'src': s[:50]})
    # manual: guide-jewelry form block (colon-space normalization, mirroring fr)
    for k, val in blk.items():
        if k.startswith('PROJECT / DATE / RECORD OWNER:PRODUCT REFERENCE') and ': ' not in k:
            manual.append({'kind': 'manual', 'key': k.replace(':', ': '), 'value': str(val).replace(':', ': '),
                           'url': 'guide-jewelry-insert-fit', 'src': k[:50]})
            break
    # manual: folded-box-liner exact key (page text has 'direction' + "liner's")
    for k, val in blk.items():
        if 'folded box liner is an internal component' in k and 'liners coverage' in k:
            nk = k.replace('liners coverage', "liner's coverage")
            manual.append({'kind': 'manual', 'key': nk, 'value': str(val), 'url': 'product-folded-box-liner', 'src': k[:50]})
            break
    return plan, manual, problems, len(seen)

if __name__ == '__main__':
    for lang in ['es', 'it', 'nl', 'pt']:
        plan, manual, problems, nseen = build_plan(lang)
        json.dump({'plan': plan, 'manual': manual},
                  open('%s-plan.json' % lang, 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
        print('===== %s: seen=%d frag=%d split=%d manual=%d problems=%d =====' %
              (lang, nseen, sum(1 for p in plan if p['kind'] == 'frag'),
               sum(1 for p in plan if p['kind'] == 'split'), len(manual), len(problems)))
        for s, v, url, why in problems[:10]:
            print('  PROBLEM[%s] %s' % (why, url.split('/%s' % lang)[-1]))
            print('    S:', (s or '')[:90])
            print('    V:', (v or '')[:90])
