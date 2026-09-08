#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Переносить спільні файли зі статичної збірки в тему WordPress.

Стилі й скрипт у теми свої — тема має працювати сама по собі, без
зовнішніх залежностей. Але писати їх двічі неможливо: копії розходяться
непомітно, і в темі лишається дизайн тижневої давнини.

Тому джерело одне — assets/ у корені, а тема отримує копію звідси:

    python3 tools/sync-theme.py

Запускайте після будь-якої правки CSS чи JS.
"""
import os, shutil, filecmp, sys

ROOT  = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
THEME = os.path.join(ROOT, 'wp-theme', 'scala-atelier')

FILES = [
    ('assets/css/main.css', 'assets/css/main.css'),
    ('assets/js/main.js',   'assets/js/main.js'),
]

def main(check_only=False):
    stale = []

    for src_rel, dst_rel in FILES:
        src = os.path.join(ROOT, src_rel)
        dst = os.path.join(THEME, dst_rel)

        if not os.path.exists(src):
            print('  ! немає джерела: %s' % src_rel)
            continue

        same = os.path.exists(dst) and filecmp.cmp(src, dst, shallow=False)

        if same:
            print('  = %s' % dst_rel)
            continue

        stale.append(dst_rel)

        if check_only:
            print('  ! РОЗІЙШЛОСЯ: %s' % dst_rel)
            continue

        os.makedirs(os.path.dirname(dst), exist_ok=True)
        shutil.copy2(src, dst)
        print('  → %s (%d рядків)' % (dst_rel, sum(1 for _ in open(dst))))

    if check_only and stale:
        print('\nТема відстала. Запустіть: python3 tools/sync-theme.py')
        return 1

    print('\nТема синхронізована.' if not check_only else '\nТема синхронізована.')
    return 0

if __name__ == '__main__':
    sys.exit(main(check_only='--check' in sys.argv))
