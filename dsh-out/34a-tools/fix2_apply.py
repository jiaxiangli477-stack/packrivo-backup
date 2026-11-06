# -*- coding: utf-8 -*-
"""34a-译文-2: insert the new coordination-fee segment, swap the 4 metas,
and fix the zh-CN jewelry-pouch wording. Writes only dsh-out/34a-tools/**
and is idempotent: re-running with the same input changes nothing.
"""
import io, json, re, sys

ROOT = r'E:\破甲工作区\packrivo-backup-34a'
TOOLS = ROOT + r'\dsh-out\34a-tools'
LANGDIR = TOOLS + r'\lang'
KEYSJSON = TOOLS + r'\keys.json'
LANGS = ['ar','de','es','fr','it','ja','ko','nl','pt','zh-CN','zh-TW']

OLD_MID = '8-15% coordination fee on production only.'
NEW_MID = '8-15% coordination fee, agreed in writing before work starts.'

# 新句译文（术语沿用 deploy/i18n-merged/*-dict.json 的既有措辞）
NEW_SEG = {
 'ar':    'رسم تنسيق 8-15%، يُتفق عليه كتابيًا قبل بدء العمل.',
 'de':    '8-15 % Koordinationsgebühr, vor Arbeitsbeginn schriftlich vereinbart.',
 'es':    'Comisión de coordinación del 8-15 %, acordada por escrito antes de empezar el trabajo.',
 'fr':    '8-15 % de frais de coordination, convenus par écrit avant le début des travaux.',
 'it':    "Commissione di coordinamento dell'8-15%, concordata per iscritto prima dell'inizio dei lavori.",
 'ja':    'コーディネーション費8-15%、着工前に書面で合意。',
 'ko':    '조정 수수료 8-15%, 작업 시작 전에 서면으로 합의합니다.',
 'nl':    '8-15% coördinatievergoeding, schriftelijk overeengekomen vóór aanvang van het werk.',
 'pt':    'Taxa de coordenação de 8-15%, acordada por escrito antes do início dos trabalhos.',
 'zh-CN': '8-15% 采购协调费，开工前书面确认。',
 'zh-TW': '8-15% 採購協調費，開工前書面確認。',
}

# 4 条 meta 换新英文（url -> 新英文）
REPLACE = [
 ('/services/',
  'Custom packaging sourcing. Then a quote. No retainer before scope. 8-15% coordination fee on production only. Request a quote.',
  'Custom packaging sourcing. Then a quote. ' + NEW_MID + ' Request a quote.'),
 ('/guides/',
  'Custom packaging guide. No retainer before scope. 8-15% coordination fee on production only. Dieline with every quote. Request a quote.',
  'Custom packaging guide. ' + NEW_MID + ' Dieline with every quote. Request a quote.'),
 ('/inquiry/',
  'Custom packaging quote request. No retainer before scope. 8-15% coordination fee on production only. Dieline with every quote. Request a quote.',
  'Custom packaging quote request. ' + NEW_MID + ' Dieline with every quote. Request a quote.'),
 ('/customization/',
  'Custom packaging finishes. No retainer before scope. 8-15% coordination fee on production only. Dieline with every quote. Request a quote.',
  'Custom packaging finishes. ' + NEW_MID + ' Dieline with every quote. Request a quote.'),
]

# zh-CN 首饰语境：pouches → 首饰袋
ZH_FIX = [
 ('SEG', 'Custom jewelry boxes, pouches and gift packaging with your logo.',
  '定制首饰盒、收纳袋与礼品包装，可印 Logo。',
  '定制首饰盒、首饰袋与礼品包装，可印 Logo。'),
 ('SEG', 'Packaging for jewelry brands: rigid boxes, pouches, gift bags, mailers and inserts, printed with your logo.',
  '面向首饰品牌的包装：精装盒、收纳袋、礼品袋、邮寄盒与内衬，可印 Logo。',
  '面向首饰品牌的包装：精装盒、首饰袋、礼品袋、邮寄盒与内衬，可印 Logo。'),
 ('TITLE', 'Custom Bags with Logo: Paper, Cotton & Pouches | Packrivo',
  '定制带 Logo 袋类：纸袋、棉袋与收纳袋 | Packrivo',
  '定制带 Logo 袋类：纸袋、棉袋与首饰袋 | Packrivo'),
]


def load(p):
    return json.loads(io.open(p, encoding='utf-8').read())


def dump(p, obj):
    io.open(p, 'w', encoding='utf-8', newline='\n').write(
        json.dumps(obj, ensure_ascii=False, indent=1))


def main():
    log = []
    # ---- 1. lang JSON: new SEG entry, inserted after the old one ----
    for lang in LANGS:
        p = LANGDIR + '\\%s.json' % lang
        d = load(p)
        seg = d['SEG']
        if NEW_MID in seg:
            log.append('%s SEG already has new segment (skip add)' % lang)
        else:
            if OLD_MID not in seg:
                raise SystemExit('%s: anchor segment missing' % lang)
            out, placed = {}, False
            for k, v in seg.items():
                out[k] = v
                if k == OLD_MID:
                    out[NEW_MID] = NEW_SEG[lang]
                    placed = True
            if not placed:
                raise SystemExit('%s: insert failed' % lang)
            d['SEG'] = out
            log.append('%s SEG +1 (%d -> %d)' % (lang, len(seg), len(out)))
        dump(p, d)

    # ---- 2. zh-CN pouch wording ----
    pzh = LANGDIR + '\\zh-CN.json'
    dzh = load(pzh)
    for sec, en, old, new in ZH_FIX:
        cur = dzh[sec].get(en)
        if cur is None:
            raise SystemExit('zh-CN %s key missing: %s' % (sec, en[:60]))
        if cur == new:
            log.append('zh-CN %s already fixed: %s' % (sec, en[:50]))
            continue
        if cur != old:
            raise SystemExit('zh-CN %s unexpected current value: %r' % (sec, cur))
        dzh[sec][en] = new
        log.append('zh-CN %s fixed: %s' % (sec, en[:50]))
    dump(pzh, dzh)

    # ---- 3. keys.json: 4 key swaps + new segment ----
    kj = load(KEYSJSON)
    keys = kj['keys']
    changed = 0
    for url, old_en, new_en in REPLACE:
        if new_en in keys:
            log.append('keys.json already has %s' % url)
            continue
        if old_en not in keys:
            raise SystemExit('keys.json missing old key for %s' % url)
        i = keys.index(old_en)
        keys[i] = new_en
        kj['req'][new_en] = kj['req'].pop(old_en)
        if old_en in kj['where']:
            kj['where'][new_en] = kj['where'].pop(old_en)
        changed += 1
        log.append('keys.json swap %s: req=%r' % (url, kj['req'][new_en]))
    segs = kj['segments']
    if NEW_MID not in segs:
        if isinstance(segs, list):
            segs.append(NEW_MID)
        else:
            segs[NEW_MID] = 1
        log.append('keys.json segments +1')
    dump(KEYSJSON, kj)
    log.append('keys.json keys=%d swapped=%d req=%d' % (len(kj['keys']), changed, len(kj['req'])))

    io.open(TOOLS + r'\fix2-apply.log', 'w', encoding='utf-8', newline='\n').write(
        '\n'.join(log) + '\n')
    print('\n'.join(log))
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
