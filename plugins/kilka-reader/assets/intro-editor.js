(function () {
  'use strict';
  document.querySelectorAll('.kilka-intro-editor').forEach(function (editor) {
    var frame = editor.querySelector('iframe'), wrap = frame.parentElement;
    var field = function (key) { return editor.querySelector('[name="kilka_intro[' + key + ']"]'); };
    var fonts = kilkaIntroPreview.fonts;
    frame.srcdoc = '<!doctype html><html><head><meta charset="utf-8"><style>body{margin:0}.kilka-reader-intro{margin:0!important;width:100%!important;min-height:100vh!important}</style></head><body><section class="kilka-reader-intro"><picture class="kilka-reader-intro__picture"><img class="kilka-reader-intro__image" alt=""></picture><div class="kilka-reader-intro__copy"><h2 class="kilka-reader-intro__title"></h2><p class="kilka-reader-intro__author"></p><p class="kilka-reader-intro__line"></p></div></section></body></html>';
    var lastWords = null;
    function titleLayout(title, heading) {
      var words = title.split(/[ \t\r\n\f]+/).filter(Boolean), key = JSON.stringify(words);
      var enabled = field('phone_breaks_enabled').checked;
      var choices = field('phone_breaks');
      var panel = editor.querySelector('[data-intro-break-panel]');
      var buttons = editor.querySelector('[data-intro-break-buttons]');
      if (lastWords !== null && lastWords !== key) choices.value = '';
      var breaks = choices.value.split(',').map(Number).filter(function (n) { return n > 0 && n < words.length; });
      if (lastWords !== key) {
        buttons.replaceChildren();
        words.forEach(function (word, index) {
          var token = document.createElement('span'); token.textContent = word; buttons.append(token);
          if (index >= words.length - 1 || index >= 500) return;
          var button = document.createElement('button'); button.type = 'button';
          button.className = 'button'; button.dataset.introBreak = index + 1;
          button.textContent = '↵'; button.setAttribute('aria-label', kilkaIntroPreview.breakLabel.replace('%s', word));
          buttons.append(button);
        });
        lastWords = key;
      }
      panel.hidden = !enabled;
      buttons.querySelectorAll('button').forEach(function (button) { button.setAttribute('aria-pressed', String(breaks.includes(Number(button.dataset.introBreak)))); });
      if (!enabled) { heading.textContent = title; return; }
      var wide = heading.ownerDocument.createElement('span'), phone = heading.ownerDocument.createElement('span');
      wide.className = 'kilka-intro-title-wide'; wide.textContent = title;
      phone.className = 'kilka-intro-title-phone';
      phone.textContent = words.map(function (word, index) { return (index ? (breaks.includes(index) ? '\n' : ' ') : '') + word; }).join('');
      heading.replaceChildren(wide, phone);
    }
    function render() {
      var doc = frame.contentDocument, intro = doc && doc.querySelector('.kilka-reader-intro');
      if (!intro) return;
      var size = editor.querySelector('[data-intro-preview-size]').value;
      var dims = {desktop: [1440,900], tablet: [820,1100], phone: [390,844]}[size];
      var scale = Math.min(1, wrap.clientWidth / dims[0]);
      frame.style.width = dims[0] + 'px'; frame.style.height = dims[1] + 'px';
      frame.style.transform = 'scale(' + scale + ')'; wrap.style.height = dims[1] * scale + 'px';
      intro.dataset.position = field('position').value;
      intro.dataset.mobilePosition = field('mobile_position').value;
      intro.dataset.align = field('align').value;
      ['title', 'text'].forEach(function (group) {
        var font = fonts[field(group + '_font').value], weight = field(group + '_weight');
        var italic = field(group + '_italic');
        weight.disabled = font.min === font.max;
        italic.disabled = font.italic === false;
        if (italic.disabled) italic.checked = false;
        editor.querySelector('[data-intro-font-note="' + group + '"]').hidden = !weight.disabled;
        intro.style.setProperty('--intro-' + group + '-synthesis', font.italic === false ? 'none' : 'auto');
        var value = Number(weight.value);
        weight.min = font.min; weight.max = font.max; weight.step = font.step;
        weight.value = Math.max(font.min, Math.min(font.max, font.min + Math.round((value - font.min) / font.step) * font.step));
        intro.style.setProperty('--intro-' + group + '-weight', weight.value);
        intro.style.setProperty('--intro-' + group + '-style', field(group + '_italic').checked ? 'italic' : 'normal');
      });
      ['ink','background','shade','title_size','text_size','line_size','align','title_font','text_font','desktop_x','desktop_y','tablet_x','tablet_y','mobile_x','mobile_y'].forEach(function (key) {
        var value = field(key).value, cssKey = key.replaceAll('_','-');
        if (key === 'background') cssKey = 'bg';
        if (key === 'shade') value = Number(value) / 100;
        if (key.endsWith('_size')) value += 'px';
        if (key.endsWith('_font')) value = fonts[value].stack;
        if (key.endsWith('_x') || key.endsWith('_y')) value += '%';
        intro.style.setProperty('--intro-' + cssKey, value);
      });
      var title = field('title').value;
      if (!title) title = editor.dataset.pageTitle || '';
      if (!title && window.wp && wp.data) { var store = wp.data.select('core/editor'); if (store) title = store.getEditedPostAttribute('title'); }
      if (!title) title = document.querySelector('#title') ? document.querySelector('#title').value : '';
      titleLayout(title, intro.querySelector('h2'));
      ['author','line'].forEach(function (key) { var e = intro.querySelector('.kilka-reader-intro__' + key); e.textContent = field(key).value; e.hidden = !field(key).value; });
      intro.querySelector('.kilka-reader-intro__line').classList.toggle('hide-on-small', field('hide_line').checked);
      var img = intro.querySelector('img'), url = size === 'phone' && field('image').dataset.url && field('mobile_image').value !== '0' ? field('mobile_image').dataset.url : field('image').dataset.url;
      intro.querySelector('picture').hidden = !url;
      if (url && img.getAttribute('src') !== url) img.src = url;
      editor.querySelectorAll('input[type="range"]').forEach(function (e) { e.nextElementSibling.value = e.value; });
    }
    frame.addEventListener('load', function () { var css = frame.contentDocument.createElement('link'); css.rel = 'stylesheet'; css.href = kilkaIntroPreview.css; frame.contentDocument.head.append(css); render(); });
    editor.addEventListener('input', render); editor.addEventListener('change', render);
    editor.addEventListener('click', function (e) {
      var br = e.target.closest('[data-intro-break]');
      if (br) {
        var n = Number(br.dataset.introBreak), input = field('phone_breaks');
        var points = input.value.split(',').map(Number).filter(function (v) { return v > 0; });
        input.value = (points.includes(n) ? points.filter(function (v) { return v !== n; }) : points.concat(n)).sort(function (a,b) { return a-b; }).join(',');
        render(); return;
      }
      var choose = e.target.closest('[data-intro-media]'), remove = e.target.closest('[data-intro-remove]');
      if (remove) { var input = field(remove.dataset.introRemove); input.value = '0'; input.dataset.url = ''; render(); }
      if (!choose) return;
      var picker = wp.media({multiple: false, library: {type: 'image'}});
      picker.on('select', function () { var image = picker.state().get('selection').first().toJSON(), input = field(choose.dataset.introMedia); input.value = image.id; input.dataset.url = image.url; render(); });
      picker.open();
    });
    if (window.ResizeObserver) new ResizeObserver(render).observe(wrap);
    if (window.wp && wp.data) wp.data.subscribe(function () { render(); });
  });
}());
