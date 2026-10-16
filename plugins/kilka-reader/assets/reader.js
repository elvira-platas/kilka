/* Reading settings live only in this document; no storage or network calls. */
(function () {
  'use strict';
  document.querySelectorAll('[data-kilka-reader]').forEach(function (reader) {
    var controls = reader.querySelector('.kilka-reader-size');
    var body = reader.querySelector('.kilka-reader-body');
    if (!controls || !body) return;
    var blocks = Array.from(body.querySelectorAll('p, li, h2, h3, h4, h5, h6, figcaption, blockquote, pre'));
    var originals = blocks.map(function (element) {
      return {element: element, value: element.style.getPropertyValue('font-size'), priority: element.style.getPropertyPriority('font-size'), pixels: 0};
    });
    var scale = 100;
    function restore() {
      originals.forEach(function (item) {
        if (item.value) item.element.style.setProperty('font-size', item.value, item.priority);
        else item.element.style.removeProperty('font-size');
      });
    }
    function render() {
      restore();
      // Read every baseline before changing parents, so nested lists do not compound.
      originals.forEach(function (item) { item.pixels = parseFloat(getComputedStyle(item.element).fontSize); });
      if (scale !== 100) originals.forEach(function (item) {
        item.element.style.setProperty('font-size', (item.pixels * scale / 100) + 'px', 'important');
      });
      controls.querySelector('[data-reader-size="reset"]').textContent = scale + '%';
      controls.querySelector('[data-reader-size="decrease"]').disabled = scale === 80;
      controls.querySelector('[data-reader-size="increase"]').disabled = scale === 160;
    }
    controls.addEventListener('click', function (event) {
      var button = event.target.closest('button[data-reader-size]');
      if (!button) return;
      var action = button.dataset.readerSize;
      scale = action === 'reset' ? 100 : Math.max(80, Math.min(160, scale + (action === 'increase' ? 10 : -10)));
      var place = pagination && pagination.capture();
      render();
      if (pagination) pagination.reflow(place);
      var status = controls.querySelector('.kilka-reader-status');
      status.textContent = status.dataset.label + ': ' + scale + '%';
    });
    var frame;
    window.addEventListener('resize', function () {
      cancelAnimationFrame(frame);
      frame = requestAnimationFrame(function () { render(); if (pagination) pagination.reflow(); });
    });
    var alignment = reader.querySelector('.kilka-reader-alignment');
    if (alignment) {
      alignment.addEventListener('click', function (event) {
        var button = event.target.closest('button[data-reader-alignment]');
        if (!button) return;
        var place = pagination && pagination.capture();
        reader.dataset.readerAlign = button.dataset.readerAlignment;
        if (pagination) pagination.reflow(place);
        alignment.querySelectorAll('button').forEach(function (item) {
          item.setAttribute('aria-pressed', String(item === button));
        });
      });
      alignment.hidden = false;
    }
    var colors = reader.querySelector('.kilka-reader-colors');
    var surface = reader.closest('.kilka-reading') || reader;
    var pagination = window.kilkaReaderPagination ? window.kilkaReaderPagination(reader, surface, body) : null;
    if (colors) {
      surface.dataset.readerColor = 'light';
      colors.addEventListener('click', function (event) {
        var button = event.target.closest('button[data-reader-color-option]');
        if (!button) return;
        surface.dataset.readerColor = button.dataset.readerColorOption;
        colors.querySelectorAll('button').forEach(function (item) {
          item.setAttribute('aria-pressed', String(item === button));
        });
      });
      colors.hidden = false;
    }
    var toggle = reader.querySelector('.kilka-reader-settings-toggle');
    var panel = reader.querySelector('#kilka-reader-settings');
    if (toggle && panel) {
      function closeSettings(restoreFocus) {
        panel.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
        if (restoreFocus) toggle.focus({preventScroll: true});
      }
      // Keep references to the original headings, independent of page numbers.
      var contents = panel.querySelector('.kilka-reader-contents');
      if (contents) {
        var list = contents.querySelector('ol');
        var chapters = [];
        var contentsFrame;
        body.querySelectorAll('h2, h3').forEach(function (heading) {
          var label = heading.textContent.replace(/\s+/g, ' ').trim();
          if (!label) return;
          var item = document.createElement('li');
          var button = document.createElement('button');
          if (heading.tagName === 'H3') item.className = 'kilka-reader-contents-subchapter';
          button.type = 'button';
          button.textContent = label;
          button.addEventListener('click', function () {
            var paged = pagination && pagination.active();
            // Reveal the text before moving focus away from the cover/menu.
            if (paged && !pagination.goToElement(heading)) return;
            closeSettings(false);
            if (!heading.hasAttribute('tabindex')) {
              heading.setAttribute('tabindex', '-1');
              heading.addEventListener('blur', function () { heading.removeAttribute('tabindex'); }, {once: true});
            }
            heading.focus({preventScroll: true});
            if (!paged) surface.scrollTop += heading.getBoundingClientRect().top - surface.getBoundingClientRect().top - 24;
          });
          item.append(button);
          list.append(item);
          chapters.push({heading: heading, button: button});
        });
        contents.hidden = !list.children.length;
        function updateChapter() {
          var current = null;
          var paged = pagination && pagination.active();
          var viewport = paged ? body.parentElement : surface;
          var box = viewport.getBoundingClientRect();
          if (!surface.hasAttribute('data-reader-intro-active')) {
            for (var i = 0; i < chapters.length; i++) {
              var rect = chapters[i].heading.getClientRects()[0];
              if (!rect) continue;
              if (paged) {
                if (rect.left >= box.right - 1) break;
                current = chapters[i];
                // Name the first chapter starting on this page, if there is one.
                if (rect.left >= box.left - 1) break;
              } else {
                if (rect.top > box.top + 32) break;
                current = chapters[i];
              }
            }
          }
          chapters.forEach(function (chapter) {
            if (chapter === current) chapter.button.setAttribute('aria-current', 'location');
            else chapter.button.removeAttribute('aria-current');
          });
        }
        function scheduleChapter() {
          cancelAnimationFrame(contentsFrame);
          contentsFrame = requestAnimationFrame(updateChapter);
        }
        surface.addEventListener('scroll', scheduleChapter, {passive: true});
        reader.addEventListener('kilka-reader-location', scheduleChapter);
        toggle.addEventListener('click', updateChapter);
        window.addEventListener('resize', scheduleChapter);
        if (window.ResizeObserver) new ResizeObserver(scheduleChapter).observe(body);
        scheduleChapter();
      }
      var selectSection = null;
      if (contents && !contents.hidden) {
        var settings = document.createElement('div');
        settings.className = 'kilka-reader-options';
        Array.from(panel.children).forEach(function (child) {
          if (child !== contents) settings.append(child);
        });
        panel.append(settings);
        var tabs = document.createElement('div');
        tabs.className = 'kilka-reader-tabs';
        tabs.setAttribute('role', 'tablist');
        tabs.setAttribute('aria-label', panel.getAttribute('aria-label'));
        var sections = [contents, settings];
        var labels = [contents.getAttribute('aria-label'), panel.getAttribute('aria-label')];
        var tabButtons = sections.map(function (section, index) {
          var tab = document.createElement('button');
          tab.type = 'button';
          tab.id = panel.id + '-tab-' + index;
          section.id = panel.id + '-section-' + index;
          section.setAttribute('role', 'tabpanel');
          section.setAttribute('aria-labelledby', tab.id);
          tab.setAttribute('role', 'tab');
          tab.setAttribute('aria-controls', section.id);
          tab.setAttribute('aria-label', labels[index]);
          tab.title = labels[index];
          var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
          svg.setAttribute('width', '24');
          svg.setAttribute('height', '24');
          svg.setAttribute('viewBox', '0 0 24 24');
          svg.setAttribute('aria-hidden', 'true');
          svg.setAttribute('focusable', 'false');
          svg.setAttribute('fill', 'none');
          svg.setAttribute('stroke', 'currentColor');
          svg.setAttribute('stroke-width', '1.8');
          svg.setAttribute('stroke-linecap', 'round');
          var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
          path.setAttribute('d', index === 0
            ? 'M4 6h1m4 0h11M4 12h1m4 0h11M4 18h1m4 0h11'
            : 'M4 6h16M4 12h16M4 18h16M9 3v6M15 9v6M8 15v6');
          svg.append(path);
          tab.append(svg);
          tab.addEventListener('click', function () { selectSection(index); });
          tabs.append(tab);
          return tab;
        });
        selectSection = function (index) {
          sections.forEach(function (section, i) {
            section.hidden = i !== index;
            tabButtons[i].setAttribute('aria-selected', String(i === index));
            tabButtons[i].tabIndex = i === index ? 0 : -1;
          });
          panel.scrollTop = 0;
        };
        tabs.addEventListener('keydown', function (event) {
          var index = tabButtons.indexOf(event.target);
          if (index < 0) return;
          var next = {ArrowRight: (index + 1) % 2, ArrowLeft: (index + 1) % 2, Home: 0, End: 1}[event.key];
          if (next === undefined) return;
          event.preventDefault();
          selectSection(next);
          tabButtons[next].focus();
        });
        panel.prepend(tabs);
        var title = contents.querySelector('h2');
        if (title) title.hidden = true;
        var icon = toggle.querySelector('path');
        if (icon) icon.setAttribute('d', 'M4 6h1m4 0h11M4 12h1m4 0h11M4 18h1m4 0h11');
        toggle.setAttribute('aria-label', labels.join(' / '));
        selectSection(0);
      }
      toggle.addEventListener('click', function () {
        var opening = panel.hidden;
        if (opening && selectSection) selectSection(0);
        panel.hidden = !opening;
        toggle.setAttribute('aria-expanded', String(opening));
        if (opening) panel.querySelector('button:not(:disabled)').focus({preventScroll: true});
      });
      document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !panel.hidden) {
          if (!document.fullscreenElement) event.preventDefault();
          closeSettings(true);
        }
      });
      document.addEventListener('pointerdown', function (event) {
        if (!panel.hidden && !panel.contains(event.target) && !toggle.contains(event.target)) {
          closeSettings(panel.contains(document.activeElement));
        }
      });
      document.addEventListener('focusin', function (event) {
        if (!panel.hidden && !panel.contains(event.target) && !toggle.contains(event.target)) closeSettings(false);
      });
      toggle.hidden = false;
    }
    var fullscreen = reader.querySelector('.kilka-reader-fullscreen');
    var fullscreenStatus = reader.querySelector('.kilka-reader-fullscreen-status');
    var root = document.documentElement;
    if (fullscreen && document.fullscreenEnabled && root.requestFullscreen && document.exitFullscreen) {
      var dock = reader.querySelector('.kilka-reader-dock');
      var hint = reader.querySelector('.kilka-reader-hint');
      var coverFullscreen = null;
      var phone = window.matchMedia('(pointer: coarse)').matches && Math.min(screen.width, screen.height) < 600;
      if (phone && reader.querySelector('.kilka-reader-intro')) {
        coverFullscreen = document.createElement('button');
        coverFullscreen.type = 'button';
        coverFullscreen.className = 'kilka-reader-cover-fullscreen';
        coverFullscreen.textContent = fullscreen.dataset.enterLabel;
        coverFullscreen.lang = panel.lang || document.documentElement.lang;
        coverFullscreen.addEventListener('click', function () { fullscreen.click(); });
        dock.insertBefore(coverFullscreen, toggle);
      }
      var hintTimer;
      var hintShown = false;
      var wasFullscreen = false;
      var anchorFrame;
      function dismissHint() {
        clearTimeout(hintTimer);
        if (hint) hint.textContent = '';
      }
      function setChrome(hidden) {
        // Preserve the visible fragment relative to the reading viewport.
        var top = surface.getBoundingClientRect().top;
        var anchor = blocks.find(function (item) { return item.getBoundingClientRect().bottom > top + 1; });
        var offset = anchor ? anchor.getBoundingClientRect().top - top : 0;
        cancelAnimationFrame(anchorFrame);
        root.dataset.readerChrome = hidden ? 'hidden' : 'shown';
        dock.hidden = hidden;
        if (hidden) {
          panel.hidden = true;
          toggle.setAttribute('aria-expanded', 'false');
          if (dock.contains(document.activeElement) || panel.contains(document.activeElement)) surface.focus({preventScroll: true});
        } else dismissHint();
        anchorFrame = requestAnimationFrame(function () {
          if (pagination && pagination.active()) { pagination.reflow(); return; }
          if (anchor) surface.scrollTop += anchor.getBoundingClientRect().top - surface.getBoundingClientRect().top - offset;
        });
      }
      var pointer = null;
      var tapTimer;
      function hasSelection() {
        var selection = window.getSelection();
        return selection && !selection.isCollapsed;
      }
      reader.addEventListener('pointerdown', function (event) {
        clearTimeout(tapTimer);
        pointer = {x: event.clientX, y: event.clientY, scroll: surface.scrollTop, time: Date.now(), moved: false, selected: hasSelection()};
      });
      reader.addEventListener('pointermove', function (event) {
        if (pointer && Math.hypot(event.clientX - pointer.x, event.clientY - pointer.y) > 8) pointer.moved = true;
      });
      reader.addEventListener('pointercancel', function () { if (pointer) pointer.moved = true; });
      reader.addEventListener('dblclick', function () { clearTimeout(tapTimer); });
      surface.addEventListener('scroll', function () { clearTimeout(tapTimer); }, {passive: true});
      reader.addEventListener('click', function (event) {
        if (document.fullscreenElement !== root || event.detail > 1 || hasSelection()) return;
        if (event.target.closest('a, button, input, textarea, select, summary, [role="button"], [contenteditable="true"]')) return;
        var gesture = pointer;
        pointer = null;
        if (gesture && (gesture.moved || gesture.selected || Date.now() - gesture.time > 600 || Math.abs(surface.scrollTop - gesture.scroll) > 4)) return;
        clearTimeout(tapTimer);
        tapTimer = setTimeout(function () {
          if (document.fullscreenElement === root && !hasSelection()) setChrome(root.dataset.readerChrome !== 'hidden');
        }, 280);
      });
      document.addEventListener('keydown', function (event) {
        if (event.key === 'Tab' && document.fullscreenElement === root && root.dataset.readerChrome === 'hidden') {
          event.preventDefault();
          setChrome(false);
          toggle.focus({preventScroll: true});
        }
      });
      function syncFullscreen() {
        var active = document.fullscreenElement === root;
        if (active !== wasFullscreen) {
          wasFullscreen = active;
          clearTimeout(tapTimer);
          setChrome(active);
          if (active && !hintShown && hint) {
            hintShown = true;
            hint.textContent = hint.dataset.message;
            hintTimer = setTimeout(dismissHint, 6000);
          }
          if (!active) { dismissHint(); delete root.dataset.readerChrome; }
        }
        fullscreen.setAttribute('aria-pressed', String(active));
        fullscreen.setAttribute('aria-label', active ? fullscreen.dataset.exitLabel : fullscreen.dataset.enterLabel);
        fullscreen.querySelector('path').setAttribute('d', active
          ? 'M4 9h5V4m6 0v5h5M9 20v-5H4m16 0h-5v5'
          : 'M9 4H4v5m11-5h5v5M4 15v5h5m11-5v5h-5');
      }
      fullscreen.addEventListener('click', async function () {
        fullscreen.disabled = true;
        if (coverFullscreen) coverFullscreen.disabled = true;
        fullscreenStatus.textContent = '';
        try {
          if (document.fullscreenElement === root) await document.exitFullscreen();
          else await root.requestFullscreen();
        } catch (error) {
          fullscreenStatus.textContent = fullscreen.dataset.error;
          if (coverFullscreen && hint) hint.textContent = fullscreen.dataset.error;
        } finally {
          fullscreen.disabled = false;
          if (coverFullscreen) coverFullscreen.disabled = false;
          syncFullscreen();
        }
      });
      document.addEventListener('fullscreenchange', syncFullscreen);
      syncFullscreen();
      fullscreen.hidden = false;
    }
    render();
    controls.hidden = false;
  });
}());
