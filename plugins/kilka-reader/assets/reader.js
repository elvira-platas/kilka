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
      toggle.addEventListener('click', function () {
        var opening = panel.hidden;
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
        fullscreenStatus.textContent = '';
        try {
          if (document.fullscreenElement === root) await document.exitFullscreen();
          else await root.requestFullscreen();
        } catch (error) {
          fullscreenStatus.textContent = fullscreen.dataset.error;
        } finally {
          fullscreen.disabled = false;
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
