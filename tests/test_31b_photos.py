from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import urlsplit
from urllib.request import urlopen
from PIL import Image

assets=Path(__file__).resolve().parents[1]/'packrivo-wp'/'packrivo-home-v4'/'assets'/'img'/'31b-process'
class Figures(HTMLParser):
    def __init__(self):super().__init__();self.inside=False;self.images=[]
    def handle_starttag(self,tag,attrs):
        d=dict(attrs)
        if tag=='figure' and 'pk-step-photo' in d.get('class',''):self.inside=True
        if self.inside and tag=='img':self.images.append(d)
    def handle_endtag(self,tag):
        if tag=='figure':self.inside=False
with urlopen('http://localhost:8082/',timeout=120) as response:
    assert response.status==200
    parser=Figures();parser.feed(response.read().decode('utf8','replace'))
assert len(parser.images)==4
for img in parser.images:
    assert img.get('loading')=='lazy' and img.get('alt') and img.get('width') and img.get('height')
    print('ALT',img['alt'])
    print('SRCSET',img['srcset'])
    for item in img['srcset'].split(','):
        url,desc=item.strip().rsplit(' ',1);file=assets/Path(urlsplit(url).path).name
        with Image.open(file) as im:
            assert im.width==int(desc[:-1]) and not im.getexif()
            print('FILE',file.name,'BYTES',file.stat().st_size,'SIZE',f'{im.width}x{im.height}')
    print('WIDTH_HEIGHT',img['width'],img['height'],'LOADING',img['loading'])
print('PHOTOS 4 RESULT PASS')
