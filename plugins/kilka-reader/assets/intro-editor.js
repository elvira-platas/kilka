(function () {
  'use strict';
  document.querySelectorAll('.kilka-intro-editor').forEach(function (editor) {
    var frame = editor.querySelector('iframe'), wrap = frame.parentElement;
    var field = function (key) { return editor.querySelector('[name="kilka_intro[' + key + ']"]'); };
    var fonts = {serif: 'Georgia, "Times New Roman", serif', sans: 'system-ui, -apple-system, "Segoe UI", sans-serif'};
    frame.srcdoc = '<!doctype html><html><head><meta charset="utf-8"><style>body{margin:0}.kilka-reader-intro{margin:0!important;width:100%!important;min-height:100vh!important}</style></head><body><section class="kilka-reader-intro"><picture class="kilka-reader-intro__picture"><img class="kilka-reader-intro__image" alt=""></picture><div class="kilka-reader-intro__copy"><h2 class="kilka-reader-intro__title"></h2><p class="kilka-reader-intro__author"></p><p class="kilka-reader-intro__line"></p></div></section></body></html>';
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
      ['ink','background','shade','title_size','text_size','align','title_font','text_font','desktop_x','desktop_y','tablet_x','tablet_y','mobile_x','mobile_y'].forEach(function (key) {
        var value = field(key).value, cssKey = key.replaceAll('_','-');
        if (key === 'background') cssKey = 'bg';
        if (key === 'shade') value = Number(value) / 100;
        if (key.endsWith('_size')) value += 'px';
        if (key.endsWith('_font')) value = fonts[value];
        if (key.endsWith('_x') || key.endsWith('_y')) value += '%';
        intro.style.setProperty('--intro-' + cssKey, value);
      });
      var title = field('title').value;
      if (!title) title = editor.dataset.pageTitle || '';
      if (!title && window.wp && wp.data) { var store = wp.data.select('core/editor'); if (store) title = store.getEditedPostAttribute('title'); }
      if (!title) title = document.querySelector('#title') ? document.querySelector('#title').value : '';
      intro.querySelector('h2').textContent = title;
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
