from pathlib import Path
import hashlib, json, subprocess
repo=Path('E:/破甲工作区/packrivo-backup')
baseline=json.loads((repo/'deploy/verification-27h/protected-after.json').read_text(encoding='utf-8-sig'))
subset={p:h for p,h in baseline.items() if 'packrivo-live' in p}
changed=[p for p,h in subset.items() if hashlib.sha256(Path(p).read_bytes()).hexdigest()!=h]
assert len(subset)==107 and not changed,changed
subprocess.run(['git','-C',str(repo),'diff','--exit-code','dc3cf19','--','deploy/i18n-merged'],check=True)
print('parent_plugin_MU_files=107 changed=0')
print('dictionary_diff_from_dc3cf19_exit=0')
