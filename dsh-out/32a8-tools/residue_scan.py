# -*- coding: utf-8 -*-
"""残留/残缺扫描：抓“译文里还有整段英文”“句末挂着冠词介词”“中日韩串拉丁字母”三类机械可检缺陷。

1) 尾部悬垂：值以冠词/介词/连词结尾（La / Le / El / Der / the / of ...）而原文不是这样
2) 英文整句泄漏：译文里存在与原文完全相同的连续 >=4 个英文词
3) 中日韩拉丁残留：ja/ko/zh-CN/zh-TW 值里出现不在白名单的英文词
"""
import csv, io, os, re, sys

CSV_P = r'E:\破甲工作区\packrivo-backup\dsh-out\32a8-i18n.csv'
OUT = r'E:\破甲工作区\.tmp\32a8\residue.txt'
LANGS_CJK = ('ja', 'ko', 'zh-CN', 'zh-TW')
ALLOW = set('''MOQ DDP FOB Packrivo WhatsApp SOS ISO ISTA FSC PET PP PE PVC ABS CMYK RGB
Pantone EU US USD EUR GBP mm cm m kg g pt pt. ml pcs pc pcs. kg. x XL XXL T T-shirt
Packrivo.com hello@packrivo.com www.packrivo.com'''.split())
DANGLE = set('''la le les el los las un una il lo gli i de het een der die das den dem des
the a an of to and or for with in on at by from как и в на не но это also'''.split())

rows = list(csv.reader(io.open(CSV_P, encoding='utf-8')))
hdr, body = rows[0], rows[1:]

dangle, leak, cjk = [], [], []
WORD = re.compile(r'[A-Za-z][A-Za-z\'’\-]+')
for r in body:
    en, lang, v = r[0], r[3], r[4]
    tail = v.rstrip().rstrip('.。！!?？')
    last = tail.rsplit(' ', 1)[-1].strip()
    en_last = en.rstrip().rstrip('.。!?').rsplit(' ', 1)[-1].strip()
    if last and last.lower() in DANGLE and last != en_last:
        dangle.append((lang, last, v[-60:]))

    ew = WORD.findall(en.lower())
    vw = WORD.findall(v.lower())
    if len(ew) >= 4:
        for n in (6, 5, 4):
            hit = None
            for k in range(0, len(ew) - n + 1):
                gram = ew[k:k + n]
                if all(len(w) > 1 for w in gram):
                    for j in range(0, len(vw) - n + 1):
                        if vw[j:j + n] == gram:
                            hit = ' '.join(gram)
                            break
                if hit:
                    break
            if hit:
                leak.append((lang, n, hit))
                break

    if lang in LANGS_CJK:
        bad = sorted({w for w in WORD.findall(v)
                      if w not in ALLOW and len(w) >= 3
                      and not re.fullmatch(r'[A-Z]{2,4}', w)})
        if bad:
            cjk.append((lang, bad, v[:70]))

io.open(OUT, 'w', encoding='utf-8', newline='\n').write('\n'.join(
    ['== 尾部悬垂 (%d) ==' % len(dangle)] + ['%s %r ... %s' % t for t in dangle] +
    ['', '== 英文整句泄漏 (%d) ==' % len(leak)] + ['%s %d-gram %r' % t for t in leak] +
    ['', '== 中日韩拉丁残留 (%d) ==' % len(cjk)] + ['%s %r | %s' % t for t in cjk]) + '\n')

print('dangle=%d leak=%d cjk=%d -> %s' % (len(dangle), len(leak), len(cjk), OUT))
for t in dangle[:10]:
    print('DANGLE', t[0], repr(t[1]), '...', t[2])
for t in leak[:10]:
    print('LEAK  ', t[0], t[1], repr(t[2]))
for t in cjk[:12]:
    print('CJK   ', t[0], t[1], '|', t[2])
