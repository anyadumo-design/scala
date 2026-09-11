#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Перевірка бойового сайту після активації теми.

    python3 tools/check-live.py                      # scalahome.com.ua
    python3 tools/check-live.py https://test.site    # інша адреса

Дивиться ззовні, як звичайний відвідувач: чи піднялася тема, чи
відкриваються нові сторінки, чи ведуть старі адреси куди треба.
"""
import sys, urllib.request, urllib.error, re

BASE = (sys.argv[1] if len(sys.argv) > 1 else 'https://scalahome.com.ua').rstrip('/')
UA = {'User-Agent': 'Mozilla/5.0 (compatible; scala-check/1.0)'}

# Сторінки, які мають відкриватися
PAGES = [
    ('/', 'головна'),
    ('/katalog/', 'каталог'),
    ('/pro-brend/', 'про бренд'),
    ('/kontakty/', 'контакти'),
    ('/vydy-shtor/', 'архів видів'),
    ('/vydy-shtor/tyul/', 'тюль'),
    ('/vydy-shtor/portyery/', 'класичні штори'),
    ('/vydy-shtor/rymski-shtory/', 'римські'),
    ('/vydy-shtor/rulonni-shtory/', 'рулонні'),
    ('/vydy-shtor/zhalyuzi/', 'жалюзі'),
    ('/vydy-shtor/avstrijski-shtory/', 'австрійські'),
    ('/vydy-shtor/yaponski-paneli/', 'японські панелі'),
    ('/vydy-shtor/blekaut/', 'блекаут'),
    ('/novost-3/', 'стаття: тканини'),
    ('/yak-pravilno-vibrati-karniz/', 'стаття: карниз'),
    ('/yaki-vikonni-sistemi-ye-na-rinku/', 'стаття: віконні системи'),
    ('/elektrokarnyzy-suchasne-rishennya-dlya-keruvannya-shtoramy/', 'стаття: електрокарнизи'),
]

# Старі адреси → куди мають вести
REDIRECTS = [
    ('/avstrijski-shtory/', '/vydy-shtor/avstrijski-shtory/'),
    ('/yaponski-shtory/', '/vydy-shtor/yaponski-paneli/'),
    ('/rulonni-shtory/', '/vydy-shtor/rulonni-shtory/'),
    ('/galereya/', '/katalog/'),
    ('/vyyizd-dyzajnera/', '/pro-brend/'),
    ('/indyvidualnyj-pidbir-tkanyny/', '/katalog/'),
    ('/poshyttya-ta-montazh-pid-klyuch/', '/pro-brend/'),
    ('/spivpraczya-dlya-dyzajneriv/', '/pro-brend/'),
    ('/ru/', '/'),
    ('/ru/pro-brend-2/', '/pro-brend/'),
    ('/ru/kontakty-2/', '/kontakty/'),
    ('/ru/avstryjskye-shtory/', '/vydy-shtor/avstrijski-shtory/'),
    ('/ru/rulonnye-shtory/', '/vydy-shtor/rulonni-shtory/'),
    ('/ru/kak-pravylno-vybrat-karnyz/', '/yak-pravilno-vibrati-karniz/'),
    ('/ru/novost-4/', '/yaki-vikonni-sistemi-ye-na-rinku/'),
]


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *a, **kw):
        return None


def fetch(path, follow=True):
    url = BASE + path
    opener = urllib.request.build_opener() if follow else urllib.request.build_opener(NoRedirect)
    req = urllib.request.Request(url, headers=UA)
    try:
        r = opener.open(req, timeout=25)
        return r.status, r.headers.get('Location', ''), r.read().decode('utf-8', 'replace')
    except urllib.error.HTTPError as e:
        return e.code, e.headers.get('Location', ''), ''
    except Exception as e:
        return 0, str(e), ''


def main():
    print('Перевіряю %s\n' % BASE)
    bad = 0

    print('— СТОРІНКИ —')
    for path, name in PAGES:
        code, _, body = fetch(path)
        theme = 'scala-atelier' in body
        h1 = re.search(r'<h1[^>]*>(.*?)</h1>', body, re.S)
        h1 = re.sub(r'\s+', ' ', re.sub('<[^>]+>', '', h1.group(1))).strip()[:42] if h1 else '—'
        ok = code == 200 and theme
        if not ok:
            bad += 1
        print('  %s %-3s %-46s %s' % ('ok ' if ok else 'ЗБІЙ', code, path, h1))

    print('\n— РЕДІРЕКТИ —')
    for path, want in REDIRECTS:
        code, loc, _ = fetch(path, follow=False)
        got = loc.replace(BASE, '') or '—'
        ok = code in (301, 308) and got.rstrip('/') == want.rstrip('/')
        if not ok:
            bad += 1
        print('  %s %-3s %-52s → %s' % ('ok ' if ok else 'ЗБІЙ', code, path, got))

    print('\n— СЛУЖБОВЕ —')
    for path, name in [('/robots.txt', 'robots'), ('/sitemap_index.xml', 'карта сайту'),
                       ('/wp-sitemap.xml', 'карта сайту (вбудована)')]:
        code, _, _ = fetch(path)
        print('  %-3s %-24s %s' % (code, name, path))

    print('\nЗбоїв: %d' % bad)
    return 1 if bad else 0


if __name__ == '__main__':
    sys.exit(main())
