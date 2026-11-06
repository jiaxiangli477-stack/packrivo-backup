# -*- coding: utf-8 -*-
"""术语校正：把本地化掉的 MOQ 统一回项目既有写法 `MOQ 250`（34a 参考表 22/22 全部保留 MOQ 原样），
并清掉 ja/ko/zh-CN 152/167 行残留的英文 `pieces per product/design`。

输入 .tmp/32a8/final.json（build 初稿），输出 .tmp/32a8/overrides.json（build 二次覆盖）+ patch 日志。
每处替换断言“恰好命中一次”，不命中即报错退出——不做模糊改写。
"""
import io, json, os, sys

TMP = r'E:\破甲工作区\.tmp'
BASE = os.path.join(TMP, '32a8')

# (idx, lang, 旧子串, 新子串, 说明)
PAIRS = [
    (26, 'ar', 'الحد الأدنى للعلب المطبوعة 250 قطعة لكل تصميم.',
     'MOQ 250 قطعة لكل تصميم للعلب المطبوعة.', 'MOQ 保留原样'),
    (26, 'de', 'Mindestmenge 250 Stück pro Design für bedruckte Schachteln.',
     'MOQ 250 Stück pro Design für bedruckte Schachteln.', 'MOQ 保留原样'),
    (26, 'es', 'Pedido mínimo de 250 unidades por diseño para cajas impresas.',
     'MOQ 250 unidades por diseño para cajas impresas.', 'MOQ 保留原样'),
    (26, 'fr', 'Minimum de 250 pièces par modèle pour les boîtes imprimées.',
     'MOQ 250 pièces par modèle pour les boîtes imprimées.', 'MOQ 保留原样'),
    (26, 'ja', 'デザインごとに最小250個です。', 'デザインごとにMOQ 250個です。', 'MOQ 保留原样'),
    (26, 'ko', '디자인당 최소 250개입니다.', '디자인당 MOQ 250개입니다.', 'MOQ 保留原样'),
    (26, 'pt', 'Pedido mínimo de 250 peças por design para caixas impressas.',
     'MOQ 250 peças por design para caixas impressas.', 'MOQ 保留原样'),
    (26, 'zh-CN', '每款设计最低起订量为 250 件。', '每款设计 MOQ 250 件。', 'MOQ 保留原样'),
    (26, 'zh-TW', '每款設計最低起訂量為 250 件。', '每款設計 MOQ 250 件。', 'MOQ 保留原样'),
    (152, 'de', 'Mindestbestellmenge: 250 Stück pro Produkt/Design.',
     'MOQ: 250 Stück pro Produkt/Design.', 'MOQ 保留原样'),
    (152, 'ja', '250 piecesです。', '250個です。', '残留英文 pieces'),
    (152, 'ko', 'MOQ: 250 pieces per product/design.', 'MOQ: 250개입니다.', '残留英文整句'),
    (167, 'ar', 'الحد الأدنى للطلب: 250 قطعة لكل منتج/تصميم.',
     'MOQ: 250 قطعة لكل منتج/تصميم.', 'MOQ 保留原样'),
    (167, 'de', 'Mindestbestellmenge: 250 Stück pro Produkt/Design.',
     'MOQ: 250 Stück pro Produkt/Design.', 'MOQ 保留原样'),
    (167, 'ja', 'MOQ：製品／デザインごとに250 pcs。', 'MOQ：製品／デザインごとに250個。', '残留英文 pcs'),
    (167, 'ko', 'MOQ: 제품/디자인당 250개', 'MOQ: 제품/디자인당 250개입니다.', '补句末终结'),
    (167, 'zh-CN', 'MOQ: 250 pieces per product/design.', 'MOQ：每款产品/设计 250 件。', '残留英文整句'),
]

final = {lang: {int(k): v for k, v in d.items()} for lang, d in
         json.load(io.open(os.path.join(BASE, 'final.json'), encoding='utf-8')).items()}
over = {}
log = []
bad = 0
for idx, lang, old, new, why in PAIRS:
    cur = final[lang].get(idx)
    if cur is None:
        log.append('!! %s row%d 不存在' % (lang, idx)); bad += 1; continue
    n = cur.count(old)
    if n != 1:
        log.append('!! %s row%d 命中 %d 次（要求 1）: %r' % (lang, idx, n, old)); bad += 1; continue
    over.setdefault(lang, {})[str(idx)] = cur.replace(old, new)
    log.append('%s row%d [%s] %r -> %r' % (lang, idx, why, old, new))

if bad:
    sys.stderr.write('patch 失败 %d 处\n%s\n' % (bad, '\n'.join(log)))
    sys.exit(1)

io.open(os.path.join(BASE, 'overrides.json'), 'w', encoding='utf-8', newline='\n').write(
    json.dumps(over, ensure_ascii=False, indent=1) + '\n')
io.open(os.path.join(BASE, 'moq-patch.log'), 'w', encoding='utf-8', newline='\n').write('\n'.join(log) + '\n')
print('overrides=%d 处，涉及 %d 语种' % (len(PAIRS), len(over)))
print('\n'.join(log))
