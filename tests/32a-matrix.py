from pathlib import Path
import subprocess, sys
root=Path(__file__).resolve().parent.parent
baseline=root/'.rehearsal-32a'/'baseline-pricing'
baseline.mkdir(parents=True,exist_ok=True)
for name in ('class-pkq-pricing.php','pricing.json'):
    data=subprocess.check_output(['git','-C',str(root),'show','d691e55:packrivo-wp/packrivo-quote/includes/'+name])
    (baseline/name).write_bytes(data)
def generate(pricing, new, target):
    cmd=['docker','run','--rm','-v',str(root)+':/work:ro','-e','PRICING_ROOT='+pricing,'-e','OUTPUT_GOODS='+str(int(new)),'php:8.2-cli','php','/work/tests/32a-matrix.php']
    with target.open('wb') as f: subprocess.run(cmd,check=True,stdout=f)
old=root/'.rehearsal-32a'/'matrix-before.csv'
new=root/'.rehearsal-32a'/'matrix-after.csv'
generate('/work/.rehearsal-32a/baseline-pricing',False,old)
generate('/work/packrivo-wp/packrivo-quote/includes',True,new)
before=old.read_text().splitlines();after=new.read_text().splitlines()
differences=[(i+1,a,b) for i,(a,b) in enumerate(zip(before,after)) if a!=b]
print('BASELINE_ROWS',len(before),'AFTER_ROWS',len(after),'DIFFERENCES',len(differences))
for line,a,b in differences[:10]: print('MISMATCH',line,a,b)
if len(before)!=len(after) or differences: sys.exit(1)
