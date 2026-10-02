(function (wp, config) {
	'use strict';
	var el = wp.element.createElement;
	var metaKey = '_kilka_note_font';
	var labels = config.labels;
	function entryFont(select) {
		var meta = select('core/editor').getEditedPostAttribute('meta') || {};
		return Object.prototype.hasOwnProperty.call(config.fonts, meta[metaKey]) ? meta[metaKey] : 'plex';
	}
	function options(paragraph) {
		var list = paragraph ? [{label: labels.inherit, value: ''}] : [];
		Object.keys(config.fonts).forEach(function (key) {
			list.push({label: config.fonts[key], value: key});
		});
		return list;
	}
	var fontClass = /^kilka-note-font-(literata|plex|kelly|bad-script|hachi)$/;
	function SelectedBlockFont() {
		var block = wp.data.useSelect(function (select) { return select('core/block-editor').getSelectedBlock(); }, []);
		if (!block || (block.name !== 'core/paragraph' && block.name !== 'core/heading')) {
			return el('p', null, labels.selectBlock);
		}
		var classes = (block.attributes.className || '').split(/\s+/).filter(Boolean);
		var current = classes.find(function (name) { return fontClass.test(name); });
		return el(wp.components.SelectControl, {label: block.name === 'core/heading' ? labels.heading : labels.paragraph, value: current ? current.replace('kilka-note-font-', '') : '', options: options(true), help: labels.help, onChange: function (value) {
			var kept = classes.filter(function (name) { return !fontClass.test(name); });
			if (value) kept.push('kilka-note-font-' + value);
			wp.data.dispatch('core/block-editor').updateBlockAttributes(block.clientId, {className: kept.join(' ') || undefined});
		}});
	}
	function EntryPanel() {
		var font = wp.data.useSelect(entryFont, []);
		var titleFont = wp.data.useSelect(function (select) {
			return (select('core/editor').getEditedPostAttribute('meta') || {})._kilka_note_title_font || '';
		}, []);
		var resolvedTitle = titleFont || font;
		wp.element.useEffect(function () {
			var observers = [], seen = new WeakSet();
			function updateDocument(doc) {
				if (!doc || !doc.body) return;
				doc.querySelectorAll('.editor-post-title__input').forEach(function (title) {
					if (title.dataset.kilkaNoteTitleFont !== resolvedTitle) title.dataset.kilkaNoteTitleFont = resolvedTitle;
				});
				if (!seen.has(doc)) {
					seen.add(doc);
					var observer = new MutationObserver(syncTitle);
					observer.observe(doc.body, {childList: true, subtree: true});
					observers.push(observer);
				}
			}
			function syncTitle() {
				updateDocument(document);
				document.querySelectorAll('iframe[name="editor-canvas"]').forEach(function (frame) {updateDocument(frame.contentDocument);});
			}
			syncTitle();
			document.addEventListener('load', syncTitle, true);
			return function () {observers.forEach(function (observer) {observer.disconnect();}); document.removeEventListener('load', syncTitle, true);};
		}, [resolvedTitle]);
		return el(wp.editPost.PluginDocumentSettingPanel, {name: 'kilka-note-typography', title: labels.panel}, el(FontControls));
	}
	function FontControls() {
		var font = wp.data.useSelect(entryFont, []);
		var titleFont = wp.data.useSelect(function (select) {
			return (select('core/editor').getEditedPostAttribute('meta') || {})._kilka_note_title_font || '';
		}, []);
		return el(wp.element.Fragment, null,
			el(wp.components.SelectControl, {label: labels.entry, value: font, options: options(false), onChange: function (value) {
				var meta = {}; meta[metaKey] = value; wp.data.dispatch('core/editor').editPost({meta: meta});
			}}),
			el(wp.components.SelectControl, {label: labels.title, value: titleFont, options: options(true), onChange: function (value) {
				wp.data.dispatch('core/editor').editPost({meta: {_kilka_note_title_font: value}});
			}}),
			el(SelectedBlockFont));
	}
	wp.plugins.registerPlugin('kilka-note-typography', {render: EntryPanel});
	wp.hooks.addFilter('editor.BlockEdit', 'kilka-note/fonts-panel', wp.compose.createHigherOrderComponent(function (BlockEdit) {
		return function (props) {
			return el(wp.element.Fragment, null, el(BlockEdit, props), props.isSelected &&
				el(wp.blockEditor.InspectorControls, null,
					el(wp.components.PanelBody, {title: labels.panel, initialOpen: true}, el(FontControls))));
		};
	}, 'KilkaNoteFontsPanel'));
	wp.hooks.addFilter('editor.BlockListBlock', 'kilka-note/entry-preview', wp.compose.createHigherOrderComponent(function (BlockListBlock) {
		return function (props) {
			var font = wp.data.useSelect(entryFont, []);
			return el(BlockListBlock, Object.assign({}, props, {className: [props.className, 'kilka-note-editor-block', 'kilka-note-entry-font-' + font].filter(Boolean).join(' ')}));
		};
	}, 'KilkaNoteEntryPreview'));
	// Classic blocks have their own iframe. Update only our opt-in TinyMCE instances.
	wp.data.subscribe(function () {
		if (!window.tinymce) return;
		var font = entryFont(wp.data.select);
		window.tinymce.editors.forEach(function (editor) {
			if (editor.kilkaNoteSetFont) editor.kilkaNoteSetFont(font);
		});
	});
})(window.wp, window.kilkaNoteTypography);
