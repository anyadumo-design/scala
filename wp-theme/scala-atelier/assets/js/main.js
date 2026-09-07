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
      strip.setPointerCapture(e.pointerId);
    });

    strip.addEventListener('pointermove', function (e) {
      if (!down) return;
      var dx = e.clientX - startX;
      if (!strip.classList.contains('is-dragging') && Math.abs(dx) > 4) {
        strip.classList.add('is-dragging');
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
      if (moved > 4) { e.preventDefault(); e.stopPropagation(); moved = 0; }
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

    $$('[data-modal-close]', modal).forEach(function (b) {
      b.addEventListener('click', function () { modal.close(); });
    });

    // Клік по підкладці поза формою — теж закриття.
    modal.addEventListener('click', function (ev) {
      if (box && !box.contains(ev.target)) modal.close();
    });

    modal.addEventListener('close', function () {
      document.body.style.overflow = '';
      // Повертаємо фокус на кнопку, з якої вікно відкрили.
      if (opener && document.contains(opener)) opener.focus({ preventScroll: true });
    });
  }

  /* ------------------------------------------------------------------------
     8. Форма заявки
     ------------------------------------------------------------------------ */
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

        function done() {
          var box  = form.parentNode.querySelector('[data-lead-sent]');
          var slot = box ? box.querySelector('[data-thanks]') : null;
          if (slot) {
            slot.textContent =
              (name ? name + ', ' : '') +
              'ми зв’яжемось із вами за номером ' +
              (phone || 'вказаним у заявці') +
              ' у робочий час, щоб узгодити дату виїзду дизайнера.';
          }
          form.hidden = true;
          if (box) box.hidden = false;

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
     Старт
     ------------------------------------------------------------------------ */
  function init() {
    initHero();
    initCursor();
    initReveal();
    initParallax();
    initMenu();
    initStripDrag();
    initLeadModal();
    initScenarios();
    initCatalogFilter();
    initForms();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
