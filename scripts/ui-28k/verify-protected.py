from pathlib import Path
import hashlib,json
repo=Path('E:/破甲工作区/packrivo-backup')
saved=Path('E:/破甲工作区/.tmp/packrivo-28k/baseline/i18n-merged')
baseline=json.loads((repo/'deploy/verification-27h/protected-after.json').read_text(encoding='utf-8-sig'))
subset={p:h for p,h in baseline.items() if 'packrivo-live' in p}
changed=[p for p,h in subset.items() if hashlib.sha256(Path(p).read_bytes()).hexdigest()!=h]
assert len(subset)==107 and not changed,changed
assert (saved/'paths.json').read_bytes()==(repo/'deploy/i18n-merged/paths.json').read_bytes()
sha=hashlib.sha256((repo/'dsh-out/untranslated-27j.json').read_bytes()).hexdigest()
assert sha=='edcadc0b117fa3a564c18c25c5b52b96d1917cf72e9777a6f80cd831d2792b3f'
print('parent_plugin_MU_files=107 changed=0')
print('paths_unchanged=True approved_translation_sha256='+sha)
