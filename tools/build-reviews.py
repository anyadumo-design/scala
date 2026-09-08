#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Збирає блок скріншотів відгуків.

Кладіть вихідні файли (PNG/JPG зі скріншотами переписок) у теку
_source-reviews/ — назва файлу задає порядок. Далі:

    python3 tools/build-reviews.py

Скрипт зробить із кожного два WebP — мініатюру для плитки й повний
кадр для перегляду, — перезбере розмітку в index.html і сформує
стартове наповнення для теми WordPress.

Вихідні файли в репозиторій не потрапляють (див. .gitignore).
"""
import os, re, subprocess, sys, glob, json

ROOT   = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
SRC    = os.path.join(ROOT, '_source-reviews')
OUT    = os.path.join(ROOT, 'assets', 'img', 'reviews')
TILES  = 6        # скільки плиток показуємо; решта — за плиткою «ще N»
THUMB  = 600      # ширина мініатюри
FULL   = 1200     # ширина повного кадру

def sh(*a):
    subprocess.run(a, check=True, capture_output=True)

def dims(path):
    out = subprocess.run(['sips', '-g', 'pixelWidth', '-g', 'pixelHeight', path],
                         capture_output=True, text=True).stdout
    w = int(re.search(r'pixelWidth:\s*(\d+)', out).group(1))
    h = int(re.search(r'pixelHeight:\s*(\d+)', out).group(1))
    return w, h

def build():
    files = sorted(
        f for f in glob.glob(os.path.join(SRC, '*'))
        if f.lower().endswith(('.png', '.jpg', '.jpeg', '.heic', '.webp'))
    )

    if not files:
        print('У _source-reviews/ порожньо. Покладіть туди скріншоти й запустіть ще раз.')
        return []

    os.makedirs(OUT, exist_ok=True)
    made = []

    for i, src in enumerate(files, 1):
        slug = 'review-%02d' % i
        w, h = dims(src)

        # Проміжний PNG: cwebp не читає HEIC, а sips не пише WebP.
        tmp = os.path.join(OUT, slug + '.png')
        sh('sips', '-s', 'format', 'png', src, '--out', tmp)

        full  = os.path.join(OUT, slug + '.webp')
        thumb = os.path.join(OUT, slug + '@thumb.webp')

        sh('cwebp', '-q', '82', '-resize', str(min(FULL, w)), '0', tmp, '-o', full)
        sh('cwebp', '-q', '80', '-resize', str(min(THUMB, w)), '0', tmp, '-o', thumb)
        os.remove(tmp)

        fw, fh = dims(full)
        tw, th = dims(thumb)
        made.append({'slug': slug, 'w': fw, 'h': fh, 'tw': tw, 'th': th,
                     'src': os.path.basename(src)})
        print('  %-12s %sx%s → %sx%s (повний) + %sx%s (плитка)' % (slug, w, h, fw, fh, tw, th))

    return made

def markup(items):
    """Розмітка плиток для index.html."""
    rows = []
    visible = items[:TILES]
    hidden  = items[TILES:]

    for n, it in enumerate(visible, 1):
        last = (n == len(visible)) and hidden
        cls  = 'shots__item shots__item--more' if last else 'shots__item'
        rows.append(
            '        <button type="button" class="%s"\n'
            '                data-shot="assets/img/reviews/%s.webp"\n'
            '                data-shot-alt="Відгук клієнта, скріншот переписки %d"\n'
            '                aria-label="Відгук %d — відкрити повністю">\n'
            '          <img src="assets/img/reviews/%s@thumb.webp" width="%d" height="%d"\n'
            '               alt="" loading="lazy" />\n'
            '%s'
            '        </button>' % (
                cls, it['slug'], n, n, it['slug'], it['tw'], it['th'],
                '          <span class="shots__more-badge">+%d</span>\n' % (len(hidden) + 1) if last else ''
            )
        )

    # Решта скрінів існує лише для перегляду — у сітці їх не видно.
    for n, it in enumerate(hidden, len(visible) + 1):
        rows.append(
            '        <button type="button" class="shots__item is-hidden" hidden\n'
            '                data-shot="assets/img/reviews/%s.webp"\n'
            '                data-shot-alt="Відгук клієнта, скріншот переписки %d"\n'
            '                aria-label="Відгук %d"></button>' % (it['slug'], n, n)
        )

    return '      <div class="shots">\n' + '\n'.join(rows) + '\n      </div>'

def patch_index(items):
    path = os.path.join(ROOT, 'index.html')
    s = open(path).read()
    new = markup(items)
    # Порівнювати рядки до і після не можна: якщо скріни не змінилися,
    # результат збігається з оригіналом — і це не помилка. Дивимось,
    # чи знайшлися межі, а не чи змінився текст.
    s2, hits = re.subn(
        r'(<!-- ПЛИТКИ ВІДГУКІВ:[^\n]*-->\n).*?(\n      <!-- /ПЛИТКИ ВІДГУКІВ -->)',
        lambda m: m.group(1) + new + m.group(2),
        s, flags=re.S)

    if not hits:
        print('  ! межі «ПЛИТКИ ВІДГУКІВ» в index.html не знайдено — розмітку не замінено')
        return
    open(path, 'w').write(s2)
    print('  index.html оновлено: %d плиток, %d у перегляді' % (min(TILES, len(items)), len(items)))

if __name__ == '__main__':
    items = build()
    if items:
        patch_index(items)
        json.dump(items, open(os.path.join(OUT, 'index.json'), 'w'),
                  ensure_ascii=False, indent=1)
        print('\nГотово. Скріншотів: %d' % len(items))
