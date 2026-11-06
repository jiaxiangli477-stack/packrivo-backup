# -*- coding: utf-8 -*-
"""One-off patch for five translate values (kept as a file: inline -c quoting mangled them)."""
import io

P = r'E:\破甲工作区\packrivo-backup\dsh-out\31b-tools\translations.py'
FIXES = [
    ("'pt': 'passos ilustrativos'",
     "'pt': 'passo a passo ilustrativo'"),
    ("'pt': 'Caixa de transporte ondulada para garrafa de vinho'",
     "'pt': 'Caixa de transporte em cartão ondulado para garrafa de vinho'"),
    ("'nl': 'Eco kraft cosmeticadoos voor kleine oplagen'",
     "'nl': 'Eco-kraft cosmeticadoos voor kleine oplagen'"),
    ("'nl': 'Kraft zeepdoos met venster op maat'",
     "'nl': 'Kraft zeepdoos op maat met venster'"),
    ("'nl': 'Abonnementsmailerbox met inleg'",
     "'nl': 'Mailerbox voor abonnementen met inleg'"),
]

s = io.open(P, encoding='utf-8').read()
for old, new in FIXES:
    n = s.count(old)
    assert n == 1, 'expected 1 match, got %d for %r' % (n, old)
    s = s.replace(old, new)
io.open(P, 'w', encoding='utf-8', newline='\n').write(s)
print('patched %d strings' % len(FIXES))
