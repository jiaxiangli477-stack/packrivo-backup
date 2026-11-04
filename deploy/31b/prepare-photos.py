from pathlib import Path
from PIL import Image
source=Path(r'E:\Claude\packrivo-SEO体检-2026-11-02\供应商照片\已处理')
dest=Path(r'E:\破甲工作区\packrivo-backup-31b\packrivo-wp\packrivo-home-v4\assets\img\31b-process')
dest.mkdir(parents=True,exist_ok=True)
inputs={
 'brief':'01-brief-xiangfeng-showroom-X16-clean.png',
 'sample':'02-sample-xiangfeng-digital-proof-X4-clean.png',
 'produce':'03-produce-xiangfeng-offset-press-X3-clean.png',
 'deliver':'04-deliver-taiyi-cartons-T6-clean.png',
}
for key,name in inputs.items():
    with Image.open(source/name) as im:
        rgb=im.convert('RGB'); widths=sorted(set(min(n,rgb.width) for n in (480,960,1600)))
        for width in widths:
            size=(width,round(rgb.height*width/rgb.width))
            photo=rgb.resize(size,Image.Resampling.LANCZOS) if width!=rgb.width else rgb.copy()
            path=dest/f'{key}-{width}.webp';photo.save(path,'WEBP',quality=82,method=5,exif=b'')
            with Image.open(path) as check:
                if check.size!=size or check.getexif():raise RuntimeError('bad output '+str(path))
            print(key,width,size[1],path.stat().st_size,flush=True)
