/* ==========================================================================
   SCALA — інтерактив сайту
   Vanilla JS, без залежностей. Кожен модуль вмикається лише якщо
   відповідні вузли є на сторінці.
   ========================================================================== */
(function () {
  'use strict';

  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var $  = function (sel, root) { return (root || document).querySelector(sel); };
  var $$ = function (sel, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(sel));
  };

  /* ------------------------------------------------------------------------
     1. Hero — штори, керовані скролом
     ------------------------------------------------------------------------ */
  function initHero() {
    var sec = $('#top');
    if (!sec) return;

    var curtainL = $('.curtain--l', sec);
    var curtainR = $('.curtain--r', sec);
    var room     = $('.hero__room', sec);
    var text     = $('.hero__text', sec);
    var reveal   = $('.hero__reveal', sec);
    var header   = $('.hdr');
    var raf      = null;
    var solid    = null;

    function paint() {
      raf = null;

      // Дистанція скролу різна: 80vh на десктопі, 35vh на мобільному.
      // Прогрес рахується від неї, тож ефект відпрацьовує повністю
      // на обох, просто на телефоні — за коротший свайп.
      var total = sec.offsetHeight - window.innerHeight;
      var p = Math.min(1, Math.max(0, -sec.getBoundingClientRect().top / (total || 1)));
      // easeInOutQuad
      var e = p < 0.5 ? 2 * p * p : 1 - Math.pow(-2 * p + 2, 2) / 2;

      if (curtainL) {
        curtainL.style.transform =
          'translate3d(' + (-e * 101) + '%,0,0) scaleX(' + (1 - e * 0.12) + ')';
      }
      if (curtainR) {
        curtainR.style.transform =
          'translate3d(' + (e * 101) + '%,0,0) scaleX(' + (1 - e * 0.12) + ')';
      }
      if (room) room.style.transform = 'scale(' + (1.1 - e * 0.1) + ')';

      if (text) {
        text.style.opacity   = String(Math.max(0, 1 - p * 2.8));
        text.style.transform = 'translateY(' + (-p * 36) + 'px)';
      }

      if (reveal) {
        var t = Math.max(0, Math.min(1, (p - 0.5) / 0.3));
        reveal.style.opacity   = String(t);
        reveal.style.transform = 'translateY(' + (1 - t) * 28 + 'px)';
      }

      if (header) {
        var isSolid = window.scrollY > window.innerHeight * 0.75;
        if (isSolid !== solid) {
          solid = isSolid;
          header.classList.toggle('is-solid', isSolid);
        }
      }
    }

    function onScroll() {
      if (raf) return;
      raf = requestAnimationFrame(paint);
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll);
    paint();
  }

  /* ------------------------------------------------------------------------
     2. Кастомний курсор
     ------------------------------------------------------------------------ */
  function initCursor() {
    var cursor = $('#cursor');
    if (!cursor) return;
    if (!window.matchMedia('(hover: hover) and (pointer: fine)').matches) return;

    var label = $('span', cursor);

    window.addEventListener('mousemove', function (ev) {
      var zone = ev.target && ev.target.closest ? ev.target.closest('[data-cursor]') : null;
      cursor.style.transform =
        'translate(' + (ev.clientX - 37) + 'px,' + (ev.clientY - 37) + 'px) scale(' +
        (zone ? 1 : 0) + ')';
      if (zone && label) label.textContent = zone.getAttribute('data-cursor');
    }, { passive: true });
  }

  /* ------------------------------------------------------------------------
     3. Reveal при скролі + каскад по сусідах
     ------------------------------------------------------------------------ */
  function initReveal() {
    if (reduced || !('IntersectionObserver' in window)) return;

    var items = [];

    function mark(el, delay, isImg) {
      if (!el || el.dataset.rv) return;
      el.dataset.rv = '1';
      el.classList.add('rv');
      if (isImg) el.classList.add('rv--img');
      el.style.setProperty('--rv-delay', delay + 'ms');
      items.push(el);
    }

    $$('section:not(#top), .footer').forEach(function (sec) {
      Array.prototype.slice.call(sec.children).forEach(function (child) {
        var isGroup =
          child.classList.contains('four-col') ||
          child.classList.contains('two-col') ||
          child.classList.contains('types-grid') ||
          child.classList.contains('fab-grid') ||
          child.classList.contains('cat-grid') ||
          child.classList.contains('ig-grid') ||
          child.classList.contains('footer__grid');

        // Стрічку проєктів навмисно НЕ розбираємо на картки: reveal з
        // transform усередині scroll-snap-контейнера ламає позиції снапу,
        // а картки лишаються прозорими, доки їх не догортають. Замість
        // цього проявляємо стрічку цілком, одним блоком.
        var isStrip = child.classList.contains('proj__strip');

        if (isGroup) {
          Array.prototype.slice.call(child.children).forEach(function (c, i) {
            mark(c, 90 * i, !!c.querySelector('img'));
          });
        } else {
          mark(child, 0, !isStrip && child.tagName !== 'DETAILS' && !!child.querySelector('img'));
        }
      });

      $$('.two-col > div, .four-col > .cell', sec).forEach(function (c, i) {
        mark(c, 70 * (i % 6), !!c.querySelector('img'));
      });
    });

    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        en.target.classList.add('is-in');
        io.unobserve(en.target);
        setTimeout(function () { en.target.classList.add('is-done'); }, 1600);
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });

    items.forEach(function (el) { io.observe(el); });
  }

  /* ------------------------------------------------------------------------
     4. М'який паралакс на великих фото
     ------------------------------------------------------------------------ */
  function initParallax() {
    if (reduced) return;

    var imgs = $$('.parallax');
    if (!imgs.length) return;

    var raf = null;

    function paint() {
      raf = null;
      var vh = window.innerHeight;
      imgs.forEach(function (img) {
        var r = img.parentElement.getBoundingClientRect();
        if (r.bottom < 0 || r.top > vh) return;
        var t = (r.top + r.height / 2 - vh / 2) / vh;
        img.style.transform = 'translateY(' + (t * -26) + 'px) scale(1.08)';
      });
    }

    function onScroll() {
      if (raf) return;
      raf = requestAnimationFrame(paint);
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    paint();
  }

  /* ------------------------------------------------------------------------
     5. Мобільне меню
     ------------------------------------------------------------------------ */
  function initMenu() {
    var menu = $('#mmenu');
    if (!menu) return;

    function close() {
      menu.classList.remove('is-open');
      document.body.style.overflow = '';
    }
    function toggle() {
      var open = !menu.classList.contains('is-open');
      menu.classList.toggle('is-open', open);
      document.body.style.overflow = open ? 'hidden' : '';
    }

    $$('[data-menu-toggle]').forEach(function (b) {
      b.addEventListener('click', toggle);
    });
    $$('a', menu).forEach(function (a) {
      a.addEventListener('click', close);
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && menu.classList.contains('is-open')) close();
    });
  }

  /* ------------------------------------------------------------------------
     5b. Стрічка проєктів — перетягування мишею
     Тач і трекпад гортають самі; це для тих, хто зі звичайною мишею.
     ------------------------------------------------------------------------ */
  function initStripDrag() {
    var strip = $('.proj__strip');
    if (!strip) return;
    if (!window.matchMedia('(hover: hover) and (pointer: fine)').matches) return;

    strip.classList.add('is-draggable');

    var down = false, startX = 0, startLeft = 0, moved = 0;

    strip.addEventListener('pointerdown', function (e) {
      if (e.pointerType === 'touch' || e.button !== 0) return;
      down = true; moved = 0;
      startX = e.clientX;
      startLeft = strip.scrollLeft;
      /*
       * Захоплення вказівника ставимо не тут, а коли рух справді почався.
       * Інакше звичайний клік теж проходив через захоплення, і подія
       * click діставалась самій стрічці — кнопки звуку на картках не
       * спрацьовували взагалі.
       */
    });

    strip.addEventListener('pointermove', function (e) {
      if (!down) return;
      var dx = e.clientX - startX;
      if (!strip.classList.contains('is-dragging') && Math.abs(dx) > 4) {
        strip.classList.add('is-dragging');
        try { strip.setPointerCapture(e.pointerId); } catch (err) {}
      }
      if (strip.classList.contains('is-dragging')) {
        moved = Math.abs(dx);
        strip.scrollLeft = startLeft - dx;
        e.preventDefault();
      }
    });

    function end(e) {
      if (!down) return;
      down = false;
      strip.classList.remove('is-dragging');
      if (e.pointerId != null && strip.hasPointerCapture(e.pointerId)) {
        strip.releasePointerCapture(e.pointerId);
      }
    }
    strip.addEventListener('pointerup', end);
    strip.addEventListener('pointercancel', end);

    // Після перетягування не відкривати посилання під курсором
    strip.addEventListener('click', function (e) {
      var dragged = moved > 4;
      moved = 0;
      if (dragged) { e.preventDefault(); e.stopPropagation(); }
    }, true);
  }

  /* ------------------------------------------------------------------------
     6. 02 Сценарії світла
     ------------------------------------------------------------------------ */
  function initScenarios() {
    var stage = $('#scen-stage');
    if (!stage) return;

    var layers = $$('.scen__layer', stage);
    var btns   = $$('.scen__btn');
    var dim    = $('.scen__dim', stage);
    var label  = $('.scen__state-label', stage);
    var text   = $('.scen__state-text', stage);

    function select(i) {
      layers.forEach(function (l, k) { l.classList.toggle('is-active', k === i); });
      btns.forEach(function (b, k) {
        b.classList.toggle('is-active', k === i);
        b.setAttribute('aria-selected', k === i ? 'true' : 'false');
      });

      var src = layers[i];
      if (!src) return;
      if (dim) dim.classList.toggle('is-on', src.dataset.dim === '1');
      if (label) label.textContent = src.dataset.label || '';
      if (text)  text.textContent  = src.dataset.text  || '';
    }

    btns.forEach(function (b, i) {
      b.addEventListener('click', function () { select(i); });
    });

    select(0);
  }

  /* ------------------------------------------------------------------------
     7. Каталог — клієнтська фільтрація
     ------------------------------------------------------------------------ */
  function initCatalogFilter() {
    var chips = $$('[data-filter]');
    var cards = $$('[data-cat]');
    if (!chips.length || !cards.length) return;

    chips.forEach(function (chip) {
      chip.addEventListener('click', function () {
        var f = chip.getAttribute('data-filter');
        chips.forEach(function (c) {
          c.classList.toggle('is-active', c === chip);
          c.setAttribute('aria-pressed', c === chip ? 'true' : 'false');
        });
        cards.forEach(function (card) {
          card.hidden = !(f === '*' || card.getAttribute('data-cat') === f);
        });
      });
    });
  }

  /* ------------------------------------------------------------------------
     7b. Модальне вікно заявки
     Кнопка відкриває форму на місці й передає своє джерело, тож у
     заявці видно, з якого блоку прийшло звернення.
     ------------------------------------------------------------------------ */
  function initLeadModal() {
    var modal = $('#lead-modal');
    if (!modal || typeof modal.showModal !== 'function') return;

    var box    = $('.modal__box', modal);
    var label  = $('[data-source-label]', modal);
    var field  = $('[data-source-field]', modal);
    var form   = $('form[data-lead-form]', modal);
    var sent   = $('[data-lead-sent]', modal);
    var opener = null;

    function reset() {
      if (!form) return;
      form.hidden = false;
      if (sent) sent.hidden = true;
      $$('[data-lead-intro]', box || modal).forEach(function (el) { el.hidden = false; });
      modal.setAttribute('aria-labelledby', 'lead-modal-title');
      var err = $('.form__error', form);
      if (err) err.textContent = '';
      var btn = $('button[type="submit"]', form);
      if (btn) {
        btn.disabled = false;
        if (btn.dataset.label) btn.textContent = btn.dataset.label;
      }
    }

    document.addEventListener('click', function (ev) {
      var trigger = ev.target.closest ? ev.target.closest('[data-lead-open]') : null;
      if (!trigger) return;

      ev.preventDefault();
      opener = trigger;

      // Джерело — тільки в приховане поле: воно потрібне в адмінці й у листі,
      // а відвідувачу «Хедер · Головна» ні про що не каже.
      var source = trigger.getAttribute('data-lead-open') || '';
      if (field) field.value = source;
      if (label) label.textContent = source;

      reset();

      // Мобільне меню закриваємо, інакше воно лишиться під вікном.
      var menu = $('#mmenu');
      if (menu && menu.classList.contains('is-open')) {
        menu.classList.remove('is-open');
      }

      modal.showModal();
      document.body.style.overflow = 'hidden';

      var first = $('input[name="phone"]', modal);
      if (first) first.focus({ preventScroll: true });
    });

    // Прибирання окремо: подія close спрацьовує не в кожному браузері,
    // а без неї на body лишається overflow: hidden і сторінка не гортається.
    function shut() {
      document.body.style.overflow = '';
      // Повертаємо фокус на кнопку, з якої вікно відкрили.
      if (opener && document.contains(opener)) opener.focus({ preventScroll: true });
    }

    function hide() {
      modal.close();
      shut();
    }

    $$('[data-modal-close]', modal).forEach(function (b) {
      b.addEventListener('click', hide);
    });

    // Клік по підкладці поза формою — теж закриття.
    modal.addEventListener('click', function (ev) {
      if (box && !box.contains(ev.target)) hide();
    });

    // Esc закриває силами браузера — прибираємо за подією.
    modal.addEventListener('close', shut);
  }

  /* ------------------------------------------------------------------------
     7b. Маска номера телефону: +38 (0XX) XXX-XX-XX

     Людина набирає лише дев'ять цифр після коду, решту домальовуємо.
     Вставлений номер у будь-якому вигляді — 0971233330, 380971233330,
     +38 097 123 33 30 — зводиться до тих самих дев'яти.
     ------------------------------------------------------------------------ */
  var PHONE_PREFIX = '+38 (0';

  function phoneDigits(value) {
    var d = String(value || '').replace(/\D/g, '');
    if (d.indexOf('380') === 0) d = d.slice(3);
    else if (d.indexOf('38') === 0 && d.length >= 11) d = d.slice(2);
    else if (d.indexOf('0') === 0) d = d.slice(1);
    return d.slice(0, 9);
  }

  function phoneFormat(digits) {
    var n = digits;
    if (!n.length) return '';
    var out = PHONE_PREFIX + n.slice(0, 2);
    if (n.length > 2) out += ') ' + n.slice(2, 5);
    if (n.length > 5) out += '-' + n.slice(5, 7);
    if (n.length > 7) out += '-' + n.slice(7, 9);
    return out;
  }

  function initPhoneMask() {
    $$('form[data-lead-form] input[type="tel"]').forEach(function (input) {
      input.setAttribute('inputmode', 'tel');
      input.setAttribute('placeholder', '+38 (0__) ___-__-__');
      input.setAttribute('maxlength', '19');

      function apply() {
        var digits = phoneDigits(input.value);
        input.value = digits.length ? phoneFormat(digits) : (document.activeElement === input ? PHONE_PREFIX : '');
        // Курсор — у кінець: для маски це передбачуваніше за спроби його зберегти.
        var end = input.value.length;
        try { input.setSelectionRange(end, end); } catch (e) {}
      }

      input.addEventListener('focus', function () {
        if (!input.value) input.value = PHONE_PREFIX;
      });
      input.addEventListener('input', apply);
      input.addEventListener('paste', function () { setTimeout(apply, 0); });
      input.addEventListener('blur', function () {
        if (!phoneDigits(input.value).length) input.value = '';
      });
    });
  }

  /* ------------------------------------------------------------------------
     7a. Відео

     Браузери не дають автозапуску зі звуком, тож відео стартує без
     нього — інакше воно просто не запуститься. Грає лише те, що в
     кадрі: інакше на сторінці з кількома відео телефон витрачає
     трафік і батарею на те, чого ніхто не бачить.
     ------------------------------------------------------------------------ */
  function initVideo() {
    var videos = $$('video[data-scala-video]');
    if (!videos.length) return;

    var still = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function play(v) {
      if (v.preload === 'none') { v.preload = 'metadata'; }
      var p = v.play();
      if (p && p.catch) p.catch(function () {});
    }

    videos.forEach(function (v) {
      var frame = v.parentNode;
      var sound = $('[data-scala-video-sound]', frame);
      var start = $('[data-scala-video-play]', frame);

      v.byUser = false; // людина зупинила сама — не поновлювати за неї

      // Видимість кнопки задає сам плеєр, а не здогадка після play().
      v.addEventListener('play', function () { if (start) start.hidden = true; });
      v.addEventListener('pause', function () { if (start) start.hidden = false; });

      v.addEventListener('click', function () {
        if (v.paused) { v.byUser = false; play(v); }
        else { v.byUser = true; v.pause(); }
      });

      if (start) {
        start.hidden = false;

        start.addEventListener('click', function (ev) {
          ev.stopPropagation();
          v.byUser = false;
          play(v);
        });
      }

      if (sound) {
        sound.dataset.off = sound.textContent.trim();

        sound.addEventListener('click', function (ev) {
          ev.stopPropagation();

          // Звук — на одному відео за раз: інакше на стрічці заговорять
          // усі одразу.
          if (v.muted) {
            videos.forEach(function (other) {
              if (other === v || other.muted) return;
              other.muted = true;
              var b = $('[data-scala-video-sound]', other.parentNode);
              if (b) { b.classList.remove('is-on'); b.setAttribute('aria-pressed', 'false'); }
            });
          }

          v.muted = !v.muted;
          sound.classList.toggle('is-on', !v.muted);
          sound.setAttribute('aria-pressed', v.muted ? 'false' : 'true');

          if (sound.dataset.on) {
            sound.textContent = v.muted ? sound.dataset.off : sound.dataset.on;
          }

          if (v.paused) { v.byUser = false; play(v); }
        });
      }
    });

    /*
     * Стрічка проєктів: у кадр одночасно потрапляє кілька карток, і
     * запускати кожну не можна — заграє все разом. Грає та, що ближча
     * до середини стрічки; решта на паузі.
     */
    $$('.proj__strip').forEach(function (strip) {
      var list = $$('video[data-scala-video]', strip);
      if (!list.length) return;

      function pick() {
        var box = strip.getBoundingClientRect();
        var mid = box.left + box.width / 2;
        var best = null;
        var bestGap = Infinity;

        list.forEach(function (v) {
          var r = v.getBoundingClientRect();
          var inStrip = r.right > box.left + 24 && r.left < box.right - 24;
          var onScreen = r.bottom > 0 && r.top < (window.innerHeight || 0);

          if (!inStrip || !onScreen) return;

          var gap = Math.abs(r.left + r.width / 2 - mid);
          if (gap < bestGap) { bestGap = gap; best = v; }
        });

        list.forEach(function (v) {
          if (v === best) {
            if (v.paused && !v.byUser && !still) play(v);
          } else if (!v.paused) {
            v.pause();
          }
        });
      }

      var waiting = false;
      function schedule() {
        if (waiting) return;
        waiting = true;
        requestAnimationFrame(function () { waiting = false; pick(); });
      }

      strip.addEventListener('scroll', schedule, { passive: true });
      window.addEventListener('scroll', schedule, { passive: true });
      window.addEventListener('resize', schedule);
      schedule();
    });

    // Поодинокі відео поза стрічкою — за видимістю.
    var loose = videos.filter(function (v) { return !v.closest('.proj__strip'); });

    if (!loose.length) return;

    if (!('IntersectionObserver' in window)) {
      if (!still) loose.forEach(play);
      return;
    }

    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        var v = e.target;
        if (e.isIntersecting) {
          if (!still && !v.byUser) play(v);
        } else if (!v.paused) {
          v.pause();
        }
      });
    }, { threshold: 0.45 });

    loose.forEach(function (v) { io.observe(v); });
  }



  /* ------------------------------------------------------------------------
     8. Форма заявки

     Перед самою формою — памʼять про джерело. Мітки utm_* з адреси,
     перехід з іншого сайту й перша сторінка сеансу зберігаються на час
     візиту і йдуть разом із заявкою: інакше в листі видно тільки
     сторінку, з якої натиснули кнопку, а не рекламу, що привела людину.
     ------------------------------------------------------------------------ */
  var TRAFFIC_KEY  = 'scala_traffic';
  var TRAFFIC_TAGS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'gclid', 'fbclid'];

  // sessionStorage може бути недоступним (приватне вікно, заборона
  // даних сайту) — тоді просто працюємо без міток.
  function trafficRead() {
    try { return JSON.parse(sessionStorage.getItem(TRAFFIC_KEY) || 'null') || null; }
    catch (e) { return null; }
  }

  function trafficWrite(data) {
    try { sessionStorage.setItem(TRAFFIC_KEY, JSON.stringify(data)); } catch (e) {}
  }

  function queryParam(name) {
    var found = new RegExp('[?&]' + name + '=([^&#]*)').exec(location.search);
    if (!found) return '';
    try { return decodeURIComponent(found[1].replace(/\+/g, ' ')).trim().slice(0, 200); }
    catch (e) { return found[1].slice(0, 200); }
  }

  function initTraffic() {
    var saved = trafficRead() || {};
    var tags  = {};
    var fresh = false;

    TRAFFIC_TAGS.forEach(function (tag) {
      var value = queryParam(tag);
      if (value) { tags[tag] = value; fresh = true; }
    });

    var views = (saved.views || 0) + 1;

    /*
     * Нова кампанія перебиває попередню: якщо людина повернулась
     * через інше оголошення, заявку привело саме воно.
     */
    if (fresh || !saved.landing) {
      var ref = document.referrer || '';
      if (ref.indexOf(location.origin) === 0) ref = '';

      saved = tags;
      if (ref) saved.referrer = ref.slice(0, 300);
      saved.landing = (location.origin + location.pathname + location.search).slice(0, 300);
    }

    saved.views = views;
    trafficWrite(saved);
  }

  function initForms() {
    $$('form[data-lead-form]').forEach(function (form) {
      form.addEventListener('submit', function (ev) {
        ev.preventDefault();

        var btn = $('button[type="submit"]', form);
        var err = $('.form__error', form);
        if (err) err.textContent = '';

        var data = new FormData(form);
        var name  = (data.get('name')  || '').toString().trim();
        var phone = (data.get('phone') || '').toString().trim();

        if (!phone) {
          if (err) err.textContent = 'Вкажіть, будь ласка, номер телефону.';
          return;
        }

        if (phoneDigits(phone).length < 9) {
          if (err) err.textContent = 'У номері має бути дев\'ять цифр після +38 0.';
          var tel = $('input[type="tel"]', form);
          if (tel) tel.focus();
          return;
        }

        function done() {
          var pane = form.parentNode;
          var box  = pane.querySelector('[data-lead-sent]');
          var slot = box ? box.querySelector('[data-thanks]') : null;
          if (slot) {
            slot.textContent =
              (name ? name + ', ' : '') +
              'ми зв’яжемось із вами за номером ' +
              (phone || 'вказаним у заявці') +
              ' у робочий час, щоб узгодити дату виїзду дизайнера.';
          }

          /*
           * Ховаємо заголовок і вступ форми. Інакше над подякою лишається
           * запрошення заповнити форму — «менеджер звʼяжеться в робочий
           * час» двічі поспіль, майже слово в слово.
           */
          $$('[data-lead-intro]', pane).forEach(function (el) { el.hidden = true; });

          form.hidden = true;
          if (box) box.hidden = false;

          // Доки видно подяку, вікно зветься нею, а не схованим заголовком.
          var dialog = form.closest ? form.closest('dialog') : null;
          var title  = box ? box.querySelector('.form-sent__title') : null;
          if (dialog && title && title.id) dialog.setAttribute('aria-labelledby', title.id);

          /*
           * Очищаємо поля й повертаємо кнопку. Інакше наступне відкриття
           * показує форму з попередніми імʼям і номером — виглядає так,
           * ніби це та сама заявка і другий раз вона не піде.
           */
          form.reset();
          if (btn) { btn.disabled = false; btn.textContent = btn.dataset.label || 'Запросити дизайнера'; }

          // Подія конверсії — підхоплять GA4 / Meta Pixel через GTM.
          // form_source показує, яка саме кнопка привела заявку.
          if (window.dataLayer) {
            window.dataLayer.push({
              event: 'lead_submit',
              form_source: (data.get('source_label') || '').toString()
            });
          }
        }

        function fail(msg) {
          if (btn) { btn.disabled = false; btn.textContent = btn.dataset.label || 'Запросити дизайнера'; }
          if (err) err.textContent = msg || 'Не вдалося надіслати. Спробуйте ще раз або зателефонуйте нам.';
        }

        // Без бекенда (статичний перегляд) — показуємо підтвердження одразу
        if (!window.SCALA || !window.SCALA.ajaxUrl) { done(); return; }

        if (btn) {
          btn.dataset.label = btn.textContent;
          btn.disabled = true;
          btn.textContent = 'Надсилаємо…';
        }

        data.append('action', 'scala_lead');
        data.append('nonce', window.SCALA.nonce || '');

        var traffic = trafficRead() || {};
        Object.keys(traffic).forEach(function (key) {
          if (traffic[key] !== '' && traffic[key] != null) data.append(key, traffic[key]);
        });

        fetch(window.SCALA.ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (res) {
            if (res && res.success) done();
            else fail(res && res.data ? res.data : null);
          })
          .catch(function () { fail(); });
      });
    });
  }

  /* ------------------------------------------------------------------------
     9. Перегляд скріншотів відгуків на весь екран

     У плитці скрін переписки читається погано — це мініатюра. Клік
     відкриває його в повний розмір, зі стрілками між скрінами: людина
     гортає відгуки, не закриваючи вікно.
     ------------------------------------------------------------------------ */
  function initLightbox() {
    var modal = $('#shot-modal');
    if (!modal || typeof modal.showModal !== 'function') return;

    var img    = $('.shotbox__img', modal);
    var count  = $('.shotbox__count', modal);
    var prev   = $('[data-shot-prev]', modal);
    var next   = $('[data-shot-next]', modal);
    var shots  = [];
    var index  = 0;
    var opener = null;

    function collect() {
      shots = $$('[data-shot]').filter(function (el) {
        return !!el.getAttribute('data-shot');
      });
    }

    function preload(i) {
      var el = shots[i];
      if (!el) return;
      var pre = new Image();
      pre.src = el.getAttribute('data-shot');
    }

    function show(i) {
      if (!shots.length) return;

      // Гортання по колу: з останнього — на перший.
      index = (i + shots.length) % shots.length;

      var el  = shots[index];
      var src = el.getAttribute('data-shot');
      var alt = el.getAttribute('data-shot-alt') || '';

      img.src = src;
      img.alt = alt;

      if (count) count.textContent = (index + 1) + ' / ' + shots.length;

      var single = shots.length < 2;
      if (prev) prev.hidden = single;
      if (next) next.hidden = single;

      preload(index + 1);
      preload(index - 1);
    }

    document.addEventListener('click', function (ev) {
      var trigger = ev.target.closest ? ev.target.closest('[data-shot]') : null;
      if (!trigger) return;

      ev.preventDefault();
      opener = trigger;

      collect();
      show(shots.indexOf(trigger));

      modal.showModal();
      document.body.style.overflow = 'hidden';
    });

    if (prev) prev.addEventListener('click', function () { show(index - 1); });
    if (next) next.addEventListener('click', function () { show(index + 1); });

    /*
     * Прибирання винесене окремо і викликається і з події close, і напряму.
     * Подія close у частині браузерів не спрацьовує, а без прибирання на
     * body лишається overflow: hidden — сторінка перестає гортатися зовсім.
     * Виклик двічі нічого не ламає.
     */
    function shut() {
      document.body.style.overflow = '';
      // Порожній src не лишаємо: браузер вважає це запитом на сторінку.
      img.removeAttribute('src');
      if (opener && document.contains(opener)) opener.focus({ preventScroll: true });
    }

    function hide() {
      modal.close();
      shut();
    }

    $$('[data-shot-close]', modal).forEach(function (b) {
      b.addEventListener('click', hide);
    });

    // Клік повз саме зображення закриває — так поводяться всі перегляди фото.
    modal.addEventListener('click', function (ev) {
      if (ev.target === img) return;
      if (ev.target.closest && ev.target.closest('button')) return;
      hide();
    });

    modal.addEventListener('keydown', function (ev) {
      if (ev.key === 'ArrowLeft')  { ev.preventDefault(); show(index - 1); }
      if (ev.key === 'ArrowRight') { ev.preventDefault(); show(index + 1); }
    });

    // Свайп на телефоні.
    var startX = null;

    modal.addEventListener('touchstart', function (ev) {
      startX = ev.touches[0].clientX;
    }, { passive: true });

    modal.addEventListener('touchend', function (ev) {
      if (startX === null) return;
      var dx = ev.changedTouches[0].clientX - startX;
      startX = null;
      if (Math.abs(dx) > 50) show(dx < 0 ? index + 1 : index - 1);
    }, { passive: true });

    // Esc закриває вікно силами браузера — прибираємо за подією.
    modal.addEventListener('close', shut);
  }

  /* ------------------------------------------------------------------------
     Старт
     ------------------------------------------------------------------------ */
  function init() {
    initTraffic();
    initHero();
    initCursor();
    initReveal();
    initParallax();
    initMenu();
    initStripDrag();
    initLeadModal();
    initScenarios();
    initCatalogFilter();
    initVideo();
    initForms();
    initPhoneMask();
    initLightbox();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
