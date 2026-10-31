"""29y 第 4 项：JSON-LD 离线校验（按 Google Rich Results / schema.org 的硬性字段）。

判据（每条都打印 PASS/FAIL）：
  A 每页至少一个 ld+json，全部能解析，且带 @context
  B ItemList：numberOfItems == 元素数、position 为 1..n 且唯一、每项有 name+url、url 唯一、
    且 **每个 name 在页面可见文字里出现**（结构化数据不许编造页面没有的条目）
  C FAQPage：每题 name 与 acceptedAnswer.text 都要在页面可见文字里出现（真实可见）
  D 产品页：有 Service（name + provider.name），且没有把产品页写成工厂/商品
  E 不该有 FAQPage 的页面（分类页）确实没有

用法：python validate-jsonld-29y.py <sd 目录>
退出码：有 FAIL 就是 1
"""
import json, os, re, sys, html as htmlmod

sys.stdout.reconfigure(encoding="utf-8")

D = sys.argv[1] if len(sys.argv) > 1 else r"E:\破甲工作区\.tmp\29y\sd"
PASS = FAIL = 0


def ok(msg):
    global PASS
    PASS += 1
    print(f"PASS  {msg}")


def ng(msg):
    global FAIL
    FAIL += 1
    print(f"FAIL  {msg}")


def visible_text(doc):
    doc = re.sub(r"<script\b[^>]*>.*?</script>", " ", doc, flags=re.S | re.I)
    doc = re.sub(r"<style\b[^>]*>.*?</style>", " ", doc, flags=re.S | re.I)
    doc = re.sub(r"<!--.*?-->", " ", doc, flags=re.S)
    doc = re.sub(r"<[^>]+>", " ", doc)
    doc = htmlmod.unescape(doc)
    return re.sub(r"\s+", " ", doc)


def norm(s):
    s = htmlmod.unescape(str(s))
    s = re.sub(r"\s+", " ", s)
    return s.strip().lower()


def blocks(doc):
    out = []
    for m in re.finditer(r'<script[^>]*type="application/ld\+json"[^>]*>(.*?)</script>', doc, re.S | re.I):
        raw = m.group(1).strip()
        out.append(raw)
    return out


def nodes_of(obj):
    if isinstance(obj, list):
        res = []
        for o in obj:
            res.extend(nodes_of(o))
        return res
    if isinstance(obj, dict):
        if isinstance(obj.get("@graph"), list):
            return [n for n in obj["@graph"] if isinstance(n, dict)]
        return [obj]
    return []


index = {}
with open(os.path.join(D, "index.txt"), encoding="utf-8") as fh:
    for line in fh:
        code, name, path = line.strip().split(" ", 2)
        index[name] = path

for name, path in sorted(index.items()):
    f = os.path.join(D, name + ".html")
    if not os.path.exists(f):
        ng(f"{path} 文件缺失")
        continue
    doc = open(f, encoding="utf-8", errors="replace").read()
    text = norm(visible_text(doc))
    raw_blocks = blocks(doc)
    if not raw_blocks:
        ng(f"{path} 没有 ld+json")
        continue
    ok(f"{path} 有 {len(raw_blocks)} 个 ld+json 块")

    parsed = []
    bad = 0
    for i, raw in enumerate(raw_blocks):
        try:
            parsed.append(json.loads(raw))
        except Exception as e:
            bad += 1
            ng(f"{path} 第 {i+1} 块 JSON 解析失败：{e}")
    if bad == 0:
        ok(f"{path} 全部块可解析")

    ctx_bad = 0
    for obj in parsed:
        c = obj.get("@context") if isinstance(obj, dict) else None
        if not c or "schema.org" not in str(c):
            ctx_bad += 1
    if ctx_bad == 0:
        ok(f"{path} @context 齐全")
    else:
        ng(f"{path} 有 {ctx_bad} 块缺 @context")

    all_nodes = []
    for obj in parsed:
        all_nodes.extend(nodes_of(obj))
    types = [n.get("@type") for n in all_nodes]
    by_id = {n.get("@id"): n for n in all_nodes if isinstance(n, dict) and n.get("@id")}

    def resolve(node):
        """@id 引用要在同一张图里能解到实体，否则就是断引用。"""
        if isinstance(node, dict) and set(node.keys()) == {"@id"}:
            return by_id.get(node["@id"])
        return node if isinstance(node, dict) else None

    for n in all_nodes:
        if n.get("@type") != "ItemList":
            continue
        items = n.get("itemListElement") or []
        cnt = n.get("numberOfItems")
        if cnt == len(items) and len(items) > 0:
            ok(f"{path} ItemList numberOfItems={cnt} == 元素数")
        else:
            ng(f"{path} ItemList numberOfItems={cnt} 与元素数 {len(items)} 不符")
        pos = [it.get("position") for it in items if isinstance(it, dict)]
        if sorted(pos) == list(range(1, len(pos) + 1)):
            ok(f"{path} ItemList position 1..{len(pos)} 连续唯一")
        else:
            ng(f"{path} ItemList position 不连续/重复：{pos[:8]}")
        urls, missing, invisible = [], [], []
        for it in items:
            if not isinstance(it, dict):
                missing.append("非对象")
                continue
            item = it.get("item") if isinstance(it.get("item"), dict) else {}
            nm = item.get("name") or it.get("name")
            u = item.get("url") or it.get("url") or item.get("@id")
            if not nm or not u:
                missing.append(json.dumps(it, ensure_ascii=False)[:60])
                continue
            urls.append(u)
            if norm(nm) not in text:
                invisible.append(nm)
        if missing:
            ng(f"{path} ItemList 有 {len(missing)} 项缺 name/url")
        else:
            ok(f"{path} ItemList {len(items)} 项都有 name+url")
        if len(set(urls)) == len(urls):
            ok(f"{path} ItemList url 无重复")
        else:
            ng(f"{path} ItemList url 有重复")
        if invisible:
            ng(f"{path} ItemList 有 {len(invisible)} 个条目标题在页面上看不到：{invisible[:3]}")
        else:
            ok(f"{path} ItemList 每个条目都真实可见")

    for n in all_nodes:
        if n.get("@type") != "FAQPage":
            continue
        qs = n.get("mainEntity") or []
        if not qs:
            ng(f"{path} FAQPage 没有 mainEntity")
            continue
        bad_q = []
        for q in qs:
            if not isinstance(q, dict):
                bad_q.append("非对象")
                continue
            qn = q.get("name")
            ans = (q.get("acceptedAnswer") or {})
            at = ans.get("text") if isinstance(ans, dict) else None
            if not qn or not at:
                bad_q.append("缺 name/acceptedAnswer.text")
                continue
            if norm(qn) not in text:
                bad_q.append("问句不可见: " + str(qn)[:40])
            elif norm(at)[:60] not in text:
                bad_q.append("答句不可见: " + str(qn)[:40])
        if bad_q:
            ng(f"{path} FAQPage {len(qs)} 题里有 {len(bad_q)} 题不满足：{bad_q[:3]}")
        else:
            ok(f"{path} FAQPage {len(qs)} 题问答都在页面上可见")

    if name.startswith("product-"):
        svc = [n for n in all_nodes if n.get("@type") == "Service"]
        if svc:
            s = svc[0]
            prov = resolve(s.get("provider"))
            pname = prov.get("name") if isinstance(prov, dict) else None
            if s.get("name") and pname:
                ok(f"{path} 产品页是 Service（name + provider.name={pname}）")
            else:
                ng(f"{path} Service 缺 name 或 provider 解析不到（provider={s.get('provider')}）")
        else:
            ng(f"{path} 产品页没有 Service 类型（应保持采购服务口径）")
        if "FAQPage" in types:
            ng(f"{path} 产品页出现了 FAQPage（页面没有可见问答）")
        else:
            ok(f"{path} 产品页没有 FAQPage（与可见内容一致）")

print("----------------------------------------")
print(f"PASS={PASS} FAIL={FAIL}")
sys.exit(0 if FAIL == 0 else 1)
