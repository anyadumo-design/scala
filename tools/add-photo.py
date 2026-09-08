#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Готує фото для сайту: одна команда замість ручного cwebp.

Покладіть файли в _source-photos/ і запустіть:

    python3 tools/add-photo.py                  # усі файли з теки
    python3 tools/add-photo.py request-photo    # і одразу дати ім'я

Кожне фото стає двома WebP у assets/img/ — повним і @0.5x для srcset —
і копіюється в тему. Вихідні файли в репозиторій не йдуть.
"""
import os, re, glob, shutil, subprocess, sys

ROOT  = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
SRC   = os.path.join(ROOT, '_source-photos')
OUT   = os.path.join(ROOT, 'assets', 'img')
THEME = os.path.join(ROOT, 'wp-theme', 'scala-atelier', 'assets', 'img')
MAX   = 1800   # більше на сайті не потрібно нікому

def sh(*a):
    subprocess.run(a, check=True, capture_output=True)

def dims(path):
    out = subprocess.run(['sips', '-g', 'pixelWidth', '-g', 'pixelHeight', path],
                         capture_output=True, text=True).stdout
    return (int(re.search(r'pixelWidth:\s*(\d+)', out).group(1)),
            int(re.search(r'pixelHeight:\s*(\d+)', out).group(1)))

def main(names):
    files = sorted(f for f in glob.glob(os.path.join(SRC, '*'))
                   if f.lower().endswith(('.png', '.jpg', '.jpeg', '.heic', '.webp')))

    if not files:
        print('У _source-photos/ порожньо.')
        return 1

    for i, src in enumerate(files):
        slug = names[i] if i < len(names) else re.sub(r'[^a-z0-9-]+', '-', os.path.splitext(os.path.basename(src))[0].lower()).strip('-')
        w, h = dims(src)
        full_w = min(MAX, w)
        half_w = full_w // 2

        # cwebp не читає HEIC, sips не пише WebP — тому проміжний PNG.
        tmp = os.path.join(OUT, slug + '.tmp.png')
        sh('sips', '-s', 'format', 'png', src, '--out', tmp)

        full = os.path.join(OUT, slug + '.webp')
        half = os.path.join(OUT, slug + '@0.5x.webp')
        sh('cwebp', '-q', '82', '-resize', str(full_w), '0', tmp, '-o', full)
        sh('cwebp', '-q', '80', '-resize', str(half_w), '0', tmp, '-o', half)
        os.remove(tmp)

        fw, fh = dims(full)
        shutil.copy2(full, os.path.join(THEME, os.path.basename(full)))

        print('  %-22s %sx%s → %sx%s  (%d КБ)  srcset: %sw / %sw'
              % (slug, w, h, fw, fh, os.path.getsize(full) // 1024, half_w, fw))
        print('     <img src="assets/img/%s.webp" srcset="assets/img/%s@0.5x.webp %dw, assets/img/%s.webp %dw" width="%d" height="%d" />'
              % (slug, slug, half_w, slug, fw, fw, fh))

    print('\nГотово. Не забудьте прописати нове фото в розмітці.')
    return 0

if __name__ == '__main__':
    sys.exit(main(sys.argv[1:]))
