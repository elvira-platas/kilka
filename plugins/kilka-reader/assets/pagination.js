/* Pagination of the original DOM with temporary visual layers during page turns. */
(function () {
  'use strict';
  window.kilkaReaderPagination = function (reader, surface, body) {
    var mode = reader.querySelector('.kilka-reader-mode');
    var nav = reader.querySelector('.kilka-reader-pages');
    if (!mode || !nav || !window.ResizeObserver) return null;
    var viewport = document.createElement('div');
    viewport.className = 'kilka-reader-viewport';
    body.before(viewport);
    viewport.append(body);
    var article = reader.closest('article');
    var heading = article && (article.querySelector('.kilka-reading__header') || article.querySelector('h1'));
    var headingMarker = document.createComment('Reader heading position');
    var intro = reader.querySelector('.kilka-reader-intro');
    var firstPage = intro ? -1 : 0;
    var enabled = false, page = firstPage, count = 1, stride = 1, place = null, frame, resizePending = false;
    var previous = nav.querySelector('[data-reader-turn="previous"]');
    var next = nav.querySelector('[data-reader-turn="next"]');
    var number = nav.querySelector('.kilka-reader-page-number');
    var interactive = 'a, button, input, textarea, select, summary, [role="button"], [contenteditable]';
    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
    var animated = !!window.requestAnimationFrame, turnFrame = null, turnTarget = null, turnSerial = 0;
    var turnWidth = 0, turnTravel = 0, turnHeight = 0, turnLeft = 0, turnTop = 0, dragFrame = null, clickTimer = null;
    var turnStage = null, turnSheet = null, drag = null, suppressClick = false, surfaceOverflow = null, surfaceOverflowPriority = '', surfaceOverflowChanged = false;
    function cancelDragFrame() {
      if (dragFrame !== null) cancelAnimationFrame(dragFrame);
      dragFrame = null;
    }
    function clearClickSuppression() {
      clearTimeout(clickTimer);
      clickTimer = setTimeout(function () { suppressClick = false; }, 0);
    }
    function removeStage() {
      surface.removeAttribute('data-reader-turning');
      cancelDragFrame();
      clearClickSuppression();
      body.style.removeProperty('visibility');
      if (intro) {
        intro.style.removeProperty('visibility');
        intro.removeAttribute('data-reader-dragging');
      }
      if (surfaceOverflowChanged) {
        if (surfaceOverflow === null) surface.style.removeProperty('overflow');
        else surface.style.setProperty('overflow', surfaceOverflow, surfaceOverflowPriority);
        surfaceOverflow = null;
        surfaceOverflowChanged = false;
      }
      surface.toggleAttribute('data-reader-intro-active', enabled && page === -1);
      viewport.removeAttribute('data-reader-dragging');
      if (turnStage) turnStage.remove();
      turnStage = turnSheet = null;
      if (resizePending) {
        resizePending = false;
        cancelAnimationFrame(frame);
        frame = requestAnimationFrame(function () { frame = null; reflow(); });
      }
    }
    function cancelTurn(commit) {
      var target = turnTarget;
      turnTarget = null;
      turnSerial++;
      cancelAnimationFrame(turnFrame);
      turnFrame = null;
      var pointer = drag && drag.id;
      drag = null;
      if (pointer !== null && surface.hasPointerCapture(pointer)) surface.releasePointerCapture(pointer);
      removeStage();
      if (commit && target !== null) go(target);
    }
    function pageLayer(index, className) {
      var layer = document.createElement('div');
      if (index === -1 && intro) {
        var cover = intro.cloneNode(true);
        cover.hidden = false;
        cover.style.removeProperty('visibility');
        cover.style.position = 'absolute';
        cover.inert = true;
        cover.setAttribute('aria-hidden', 'true');
        layer.className = className;
        layer.append(cover);
        return layer;
      }
      var clone = body.cloneNode(true);
      layer.className = className;
      clone.removeAttribute('style');
      clone.setAttribute('aria-hidden', 'true');
      clone.inert = true;
      clone.querySelectorAll('[id]').forEach(function (item) { item.removeAttribute('id'); });
      clone.querySelectorAll(interactive).forEach(function (item) { item.setAttribute('tabindex', '-1'); });
      clone.style.width = viewport.clientWidth + 'px';
      // Do not promote an entire multi-column book into a GPU texture.
      clone.style.left = (-index * stride) + 'px';
      var textWindow = document.createElement('div');
      textWindow.className = 'kilka-reader-turn-text';
      textWindow.style.cssText = 'position:absolute;overflow:hidden;left:' + turnLeft + 'px;top:' + turnTop + 'px;width:' + viewport.clientWidth + 'px;height:' + viewport.clientHeight + 'px';
      textWindow.append(clone);
      var windowLayer = document.createElement('div');
      windowLayer.className = 'kilka-reader-turn-window';
      windowLayer.style.width = turnWidth + 'px';
      windowLayer.style.height = turnHeight + 'px';
      windowLayer.append(textWindow);
      layer.append(windowLayer);
      return layer;
    }
    function startSlide(delta) {
      var target = Math.max(firstPage, Math.min(count - 1, page + delta));
      if (target === page) return false;
      clearTimeout(clickTimer);
      turnTarget = target;
      surface.toggleAttribute('data-reader-intro-active', enabled && (page === -1 || target === -1));
      var bounds = (page === -1 ? intro : viewport).getBoundingClientRect();
      var textBounds = viewport.getBoundingClientRect();
      turnWidth = document.documentElement.clientWidth;
      turnTravel = page === -1 ? bounds.width : viewport.clientWidth;
      turnHeight = window.innerHeight;
      turnLeft = textBounds.left;
      turnTop = textBounds.top;
      turnStage = document.createElement('div');
      turnStage.className = 'kilka-reader-turn-stage';
      turnStage.setAttribute('aria-hidden', 'true');
      turnStage.inert = true;
      turnStage.dataset.direction = delta > 0 ? 'next' : 'previous';
      turnStage.style.setProperty('--reader-page-height', viewport.clientHeight + 'px');
      turnStage.style.width = turnWidth + 'px';
      turnStage.style.height = turnHeight + 'px';
      // Going back slides the previous page in over the stationary current page.
      var movingPage = delta > 0 ? page : target;
      turnStage.append(pageLayer(delta > 0 ? target : page, 'kilka-reader-turn-under'));
      turnSheet = document.createElement('div');
      turnSheet.className = 'kilka-reader-turn-sheet';
      turnSheet.append(pageLayer(movingPage, 'kilka-reader-turn-page'));
      turnStage.append(turnSheet);
      surfaceOverflow = surface.style.getPropertyValue('overflow') || null;
      surfaceOverflowPriority = surface.style.getPropertyPriority('overflow');
      surfaceOverflowChanged = true;
      surface.style.setProperty('overflow', 'visible', 'important');
      reader.append(turnStage);
      surface.setAttribute('data-reader-turning', '');
      if (intro) intro.style.visibility = 'hidden';
      body.style.visibility = 'hidden';
      setSlide(0, viewport.clientHeight / 2);
      return true;
    }
    function setSlide(progress, pointerY) {
      if (!turnStage) return;
      progress = Math.max(0, Math.min(1, progress));
      var width = turnWidth;
      var nextPage = turnStage.dataset.direction === 'next';
      var offset = -width * (nextPage ? progress : 1 - progress);
      turnSheet.style.transform = 'translate3d(' + offset + 'px,0,0)';
      // Fade only the edge shadow; page text and geometry remain unchanged.
      var lift = Math.min(1, progress / 0.14, (1 - progress) / 0.14);
      turnSheet.style.setProperty('--reader-turn-shadow', lift * lift * (3 - 2 * lift));
    }

    function settleSlide(from, destination, pointerY, commit) {
      var serial = ++turnSerial;
      var start = null;
      var duration = 110 + Math.abs(destination - from) * 230;
      var startShadow = Number(turnSheet.style.getPropertyValue('--reader-turn-shadow')) || 0;
      function step(now) {
        if (serial !== turnSerial) return;
        // Start the clock when the first frame is actually ready to paint.
        if (start === null) start = now;
        var elapsed = Math.min(1, (now - start) / duration);
        var eased = destination ? 1 - Math.pow(1 - elapsed, 3) : Math.pow(elapsed, 3);
        setSlide(from + (destination - from) * eased, pointerY);
        if (!commit) {
          // Return motion accelerates near its end; fade independently to avoid a snap.
          var fade = elapsed * elapsed * (3 - 2 * elapsed);
          turnSheet.style.setProperty('--reader-turn-shadow', startShadow * (1 - fade));
        }
        if (elapsed < 1) { turnFrame = requestAnimationFrame(step); return; }
        var target = turnTarget;
        turnTarget = null;
        turnFrame = null;
        removeStage();
        if (commit && target !== null) go(target);
      }
      turnFrame = requestAnimationFrame(step);
    }
    function effectState() {
      viewport.toggleAttribute('data-reader-effect', animated && !reduced.matches);
    }
    reduced.addEventListener('change', function () { cancelTurn(true); effectState(); });
    window.addEventListener('beforeprint', function () { cancelTurn(true); });
    document.addEventListener('visibilitychange', function () { if (document.hidden) cancelTurn(true); });
    function turn(delta) {
      // A rapid second turn completes the first destination before continuing.
      cancelTurn(true);
      var target = Math.max(firstPage, Math.min(count - 1, page + delta));
      if (target === page) return;
      if (!animated || reduced.matches || !startSlide(delta)) { go(target); return; }
      settleSlide(0, 1, viewport.clientHeight / 2, true);
    }
    function selected() { var s = getSelection(); return s && !s.isCollapsed; }
    function rangeAt(mark) {
      if (!mark || !mark.node.isConnected) return null;
      var r = document.createRange();
      r.setStart(mark.node, Math.min(mark.offset, mark.node.length - 1));
      r.setEnd(mark.node, Math.min(mark.offset + 1, mark.node.length));
      return r;
    }
    // Locate a text character, rather than a paragraph which may span many pages.
    function capture() {
      if (enabled && intro && page === -1) return null;
      var box = enabled ? viewport.getBoundingClientRect() : surface.getBoundingClientRect();
      var walker = document.createTreeWalker(body, NodeFilter.SHOW_TEXT);
      var node;
      while ((node = walker.nextNode())) {
        if (!node.textContent.trim() || node.parentElement.closest('script, style, [hidden]')) continue;
        var r = document.createRange(); r.selectNodeContents(node);
        var visible = Array.from(r.getClientRects()).some(function (b) {
          return b.bottom > box.top + 1 && b.top < box.bottom && b.right > box.left && b.left < box.right;
        });
        if (!visible) continue;
        // Binary search the first character on this page (linear DOM order).
        var low = 0, high = node.length - 1;
        while (low < high) {
          var mid = Math.floor((low + high) / 2);
          var b = rangeAt({node: node, offset: mid}).getBoundingClientRect();
          var before = enabled ? b.right <= box.left : b.bottom <= box.top + 1;
          if (before) low = mid + 1; else high = mid;
        }
        return {node: node, offset: low};
      }
      return null;
    }
    function update() {
      previous.disabled = page === firstPage;
      next.disabled = page >= count - 1;
      var onIntroduction = enabled && intro && page === -1;
      number.hidden = !!onIntroduction;
      if (onIntroduction) {
        number.textContent = '';
        number.removeAttribute('aria-label');
        return;
      }
      number.textContent = (page + 1) + ' / ' + count;
      number.setAttribute('aria-label', number.dataset.label.replace('%1$s', page + 1).replace('%2$s', count));
    }
    function go(target, remember) {
      cancelTurn(false);
      page = Math.max(firstPage, Math.min(count - 1, target));
      if (intro) {
        intro.hidden = enabled && page !== -1;
        surface.toggleAttribute('data-reader-intro-active', enabled && page === -1);
        viewport.inert = enabled && page === -1;
        viewport.setAttribute('aria-hidden', String(enabled && page === -1));
      }
      viewport.scrollLeft = Math.max(0, page) * stride;
      viewport.scrollTop = 0;
      surface.scrollTop = 0;
      update();
      if (remember !== false) place = capture();
    }
    function reflow(mark) {
      if (!enabled || window.matchMedia('print').matches) return;
      cancelTurn(true);
      if (mark && page !== -1) place = mark;
      var css = getComputedStyle(surface);
      var height = Math.floor(surface.clientHeight - parseFloat(css.paddingTop) - parseFloat(css.paddingBottom));
      if (height < 100) { setMode(false); return; }
      viewport.style.width = Math.floor(reader.getBoundingClientRect().width) + 'px';
      viewport.style.setProperty('--reader-page-height', height + 'px');
      stride = viewport.clientWidth + 48;
      count = Math.max(1, Math.round((viewport.scrollWidth + 48) / stride));
      var r = page === -1 ? null : rangeAt(place);
      var target = r ? Math.floor((r.getBoundingClientRect().left - viewport.getBoundingClientRect().left + viewport.scrollLeft + 1) / stride) : page;
      go(target, false);
    }
    function setMode(paged) {
      cancelTurn(true);
      var mark = enabled ? place || capture() : capture();
      if (paged === enabled) return;
      enabled = paged;
      if (enabled && heading && !body.contains(heading)) { heading.before(headingMarker); body.prepend(heading); }
      if (!enabled && !intro && headingMarker.isConnected) { headingMarker.replaceWith(heading); }
      surface.dataset.readerMode = enabled ? 'pages' : 'scroll';
      nav.hidden = !enabled;
      effectState();
      mode.querySelectorAll('button[data-reader-mode]').forEach(function (b) { b.setAttribute('aria-pressed', String((b.dataset.readerMode === 'pages') === enabled)); });
      if (enabled) {
        place = mark;
        if (mark && page === -1) page = 0;
        reflow();
      } else {
        if (intro) { intro.hidden = false; viewport.inert = false; viewport.removeAttribute('aria-hidden'); surface.removeAttribute('data-reader-intro-active'); }
        viewport.scrollLeft = 0;
        viewport.style.removeProperty('--reader-page-height');
        viewport.style.removeProperty('width');
        var r = rangeAt(mark);
        if (r) surface.scrollTop += r.getBoundingClientRect().top - surface.getBoundingClientRect().top - 24;
      }
    }
    mode.addEventListener('click', function (e) {
      var button = e.target.closest('button[data-reader-mode]');
      if (button) setMode(button.dataset.readerMode === 'pages');
    });
    nav.addEventListener('click', function (e) {
      var button = e.target.closest('[data-reader-turn]');
      if (button) turn(button.dataset.readerTurn === 'next' ? 1 : -1);
    });
    document.addEventListener('keydown', function (e) {
      if (!enabled || e.defaultPrevented || e.altKey || e.ctrlKey || e.metaKey || selected()) return;
      if ((e.target.closest(interactive) && !nav.contains(e.target)) || !reader.querySelector('#kilka-reader-settings').hidden) return;
      var delta = {ArrowRight: 1, PageDown: 1, ArrowLeft: -1, PageUp: -1}[e.key];
      if (!delta) return;
      e.preventDefault(); turn(delta);
    });
    document.addEventListener('pointerdown', function (e) {
      if (e.isPrimary === false) { if (drag) cancelTurn(false); return; }
      var turnControl = e.target.closest('[data-reader-turn]');
      if (!enabled || !animated || reduced.matches || (e.target.closest(interactive) && !turnControl)) return;
      if (e.pointerType === 'mouse' && e.button !== 0) return;
      var box = (page === -1 ? intro : viewport).getBoundingClientRect();
      if (e.clientY < box.top || e.clientY > box.bottom) return;
      var x = e.clientX - box.left;
      var edgeZone = e.pointerType === 'touch' ? Math.min(96, Math.round(box.width * 0.28)) : 64;
      var delta = turnControl
        ? (turnControl.dataset.readerTurn === 'next' ? 1 : -1)
        : (x >= box.width - edgeZone ? 1 : (x <= edgeZone ? -1 : 0));
      var introDrag = page === -1 && !turnControl;
      if ((!delta && !introDrag) || (!introDrag && delta < 0 && page === firstPage) || (!introDrag && delta > 0 && page >= count - 1)) return;
      cancelTurn(true);
      if (!introDrag && !startSlide(delta)) return;
      var selection = getSelection();
      if (selection) selection.removeAllRanges();
      drag = {id: e.pointerId, delta: introDrag ? 0 : delta, pending: introDrag, startX: e.clientX, startY: e.clientY, lastX: e.clientX, lastTime: performance.now(), velocity: 0, progress: 0, top: box.top, y: e.clientY - box.top, control: !!turnControl, moved: false};
      suppressClick = true;
      try { surface.setPointerCapture(e.pointerId); } catch (error) {}
      viewport.setAttribute('data-reader-dragging', '');
      e.preventDefault();
    });
    document.addEventListener('pointermove', function (e) {
      if (!drag || e.pointerId !== drag.id) return;
      var now = performance.now();
      if (drag.pending) {
        var dx = e.clientX - drag.startX, dy = e.clientY - drag.startY;
        if (Math.abs(dx) < 10 && Math.abs(dy) < 10) return;
        if (Math.abs(dy) > Math.abs(dx) * 1.15) { drag.blocked = true; return; }
        drag.pending = false;
        drag.delta = dx < 0 ? 1 : -1;
        if ((drag.delta < 0 && page === firstPage) || (drag.delta > 0 && page >= count - 1) || !startSlide(drag.delta)) {
          drag.blocked = true;
          return;
        }
        intro.setAttribute('data-reader-dragging', '');
      }
      if (drag.blocked) return;
      var distance = drag.delta > 0 ? drag.startX - e.clientX : e.clientX - drag.startX;
      var progress = Math.max(0, Math.min(1, distance / Math.max(1, turnTravel * 0.82)));
      drag.velocity = (progress - drag.progress) / Math.max(1, now - drag.lastTime);
      drag.progress = progress;
      drag.moved = drag.moved || Math.abs(e.clientX - drag.startX) > 6;
      drag.lastX = e.clientX;
      drag.lastTime = now;
      drag.y = e.clientY - drag.top;
      // Keep only the latest input; never queue a render for every pointer event.
      // Geometry is captured on pointerdown to avoid layout reads after writes.
      if (dragFrame === null) dragFrame = requestAnimationFrame(function () {
        dragFrame = null;
        if (drag) setSlide(drag.progress, drag.y);
      });
      e.preventDefault();
    });
    function releaseDrag(e, cancelled) {
      if (!drag || e.pointerId !== drag.id) return;
      var state = drag;
      cancelDragFrame();
      if (state.blocked || state.pending || !turnStage) {
        drag = null;
        if (surface.hasPointerCapture(e.pointerId)) surface.releasePointerCapture(e.pointerId);
        clearClickSuppression();
        return;
      }
      // Flush the final position before settling; no stale drag frame can follow it.
      setSlide(state.progress, state.y);
      drag = null;
      if (surface.hasPointerCapture(e.pointerId)) surface.releasePointerCapture(e.pointerId);
      var freshFlick = performance.now() - state.lastTime < 120 && state.velocity > 0.0012;
      var commit = !cancelled && ((state.control && !state.moved) || state.progress >= 0.46 || freshFlick);
      settleSlide(state.progress, commit ? 1 : 0, state.y, commit);
      clearClickSuppression();
      e.preventDefault();
    }
    document.addEventListener('pointerup', function (e) { releaseDrag(e, false); });
    document.addEventListener('pointercancel', function (e) { releaseDrag(e, true); });
    surface.addEventListener('lostpointercapture', function (e) {
      if (drag && drag.id === e.pointerId) cancelTurn(false);
    });
    window.addEventListener('blur', function () { if (drag) cancelTurn(false); });
    document.addEventListener('click', function (e) {
      if (!suppressClick) return;
      e.preventDefault();
      e.stopImmediatePropagation();
    }, true);
    var touch = null;
    reader.addEventListener('touchstart', function (e) {
      touch = (!animated || reduced.matches) && enabled && e.touches.length === 1 && !selected() && !e.target.closest(interactive)
        ? {x: e.touches[0].clientX, y: e.touches[0].clientY, time: Date.now()} : null;
    }, {passive: true});
    reader.addEventListener('touchcancel', function () { touch = null; }, {passive: true});
    reader.addEventListener('touchend', function (e) {
      if (!touch || e.touches.length || selected()) { touch = null; return; }
      var dx = e.changedTouches[0].clientX - touch.x, dy = e.changedTouches[0].clientY - touch.y;
      if (Date.now() - touch.time < 800 && Math.abs(dx) > 60 && Math.abs(dx) > Math.abs(dy) * 1.5) {
        e.preventDefault(); turn(dx < 0 ? 1 : -1);
      }
      touch = null;
    }, {passive: false});
    // Keyboard focus / fragment links may scroll an offscreen column into view.
    viewport.addEventListener('scroll', function () {
      if (!enabled || page === -1) return;
      var target = Math.round(viewport.scrollLeft / stride);
      if (target !== page) go(target);
    }, {passive: true});
    body.addEventListener('focusin', function (e) {
      if (!enabled) return;
      var b = e.target.getClientRects()[0] || e.target.getBoundingClientRect();
      go(Math.floor((b.left - viewport.getBoundingClientRect().left + viewport.scrollLeft + 1) / stride));
    });
    new ResizeObserver(function () {
      if (turnStage || drag) { resizePending = true; return; }
      cancelAnimationFrame(frame); frame = requestAnimationFrame(function () { reflow(); });
    }).observe(surface);
    body.addEventListener('load', function () { reflow(); }, true);
    if (document.fonts) document.fonts.ready.then(function () { reflow(); });
    mode.hidden = false;
    setMode(true);
    if (enabled) go(firstPage);
    return {capture: function () { cancelTurn(true); return enabled ? place || capture() : capture(); }, reflow: reflow, active: function () { return enabled; }};
  };
}());
