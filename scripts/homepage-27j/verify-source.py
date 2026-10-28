from pathlib import Path
import subprocess, hashlib, json, zipfile
repo=Path('E:/破甲工作区/packrivo-backup')
work=Path('E:/破甲工作区/packrivo-wp/packrivo-home-v4')
relative='packrivo-wp/packrivo-home-v4/front-page.php'
before=subprocess.check_output(['git','-C',str(repo),'show','dc3cf19:'+relative])
# Git stores LF; the original Windows working copy uses CRLF. Preserve its
# bytes when checking the permitted replacements and newly inserted bridge.
before=before.replace(b'\n',b'\r\n')
current=(work/'front-page.php').read_bytes()
expected=before
changes=[('dielines included','a cutting template with every quote'),('Kraft E-flute','Kraft corrugated'),('White E-flute','White corrugated'),('Greyboard 2 mm','Rigid greyboard')]
for old,new in changes:
 assert expected.count(old.encode())==1
 expected=expected.replace(old.encode(),new.encode(),1)
oldh=b'<h1 class="pk-display">Custom packaging for <span class="pk-accent">jewelry, candles</span> &amp; gifts.</h1>'
newh='''<h1 class="pk-display"><?php
      $pk_home_h1_translation = function_exists('packrivo_lang') && packrivo_lang() !== null && function_exists('packrivo_i18n_lookup')
          ? packrivo_i18n_lookup('Custom packaging for jewelry, candles & gifts.', ['block', 'text']) : null;
      if ($pk_home_h1_translation !== null) :
          echo esc_html($pk_home_h1_translation);
      else : ?>Custom packaging for <span class="pk-accent">jewelry, candles</span> &amp; gifts.<?php endif; ?></h1>'''.encode()
assert expected.count(oldh)==1
expected=expected.replace(oldh,newh,1)
assert current==expected,'Unapproved source change'
assert (repo/relative).read_bytes()==current,'Backup source differs'
files=sorted(p for p in work.rglob('*') if p.is_file())
for p in files: assert p.read_bytes()==(repo/'packrivo-wp/packrivo-home-v4'/p.relative_to(work)).read_bytes(),str(p)
zip_path=repo/'deploy/packrivo-home-v4.zip'
with zipfile.ZipFile(zip_path) as z:
 assert set(z.namelist())=={'packrivo-home-v4/'+p.relative_to(work).as_posix() for p in files}
 for p in files: assert z.read('packrivo-home-v4/'+p.relative_to(work).as_posix())==p.read_bytes()
result={'approved_phrase_replacements':4,'H1_bridge':True,'other_source_bytes_unchanged':True,'synced_theme_files':len(files),'zip_matching_files':len(files),'zip_sha256':hashlib.sha256(zip_path.read_bytes()).hexdigest()}
(repo/'deploy/verification-27j/source-audit.json').write_text(json.dumps(result,indent=2)+'\n',encoding='utf8')
print(json.dumps(result))
print('PASS: only four approved phrases plus existing-dictionary H1 bridge; all other business copy unchanged; backup and ZIP match working child theme')
