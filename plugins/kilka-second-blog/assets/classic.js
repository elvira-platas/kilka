(function () {
	'use strict';
	tinymce.PluginManager.add('kilka_note_font', function (editor) {
		var config = typeof editor.settings.kilka_note_config === 'string' ? JSON.parse(editor.settings.kilka_note_config) : editor.settings.kilka_note_config;
		if (!config) return;
		var keys = Object.keys(config.fonts);
		var values = [{text: config.labels.inherit, value: ''}];
		keys.forEach(function (key) {values.push({text: config.fonts[key], value: key});});
		editor.kilkaNoteSetFont = function (font) {
			var body = editor.getBody();
			if (!body || body.getAttribute('data-kilka-note-font') === font) return;
			Object.keys(config.fonts).forEach(function (key) {editor.dom.toggleClass(body, 'kilka-note-entry-font-' + key, font === key);});
			body.setAttribute('data-kilka-note-font', font);
		};
		editor.on('init', function () {
			keys.forEach(function (key) {editor.formatter.register('kilka-note-' + key, {selector: 'p', classes: 'kilka-note-font-' + key});});
			var font = config.entryFont;
			if (window.wp && wp.data && wp.data.select('core/editor')) {
				var meta = wp.data.select('core/editor').getEditedPostAttribute('meta') || {}; font = meta._kilka_note_font || font;
			}
			editor.kilkaNoteSetFont(font);
			var field = document.getElementById('kilka-note-entry-font');
			var titleField = document.getElementById('kilka-note-title-font');
			function updateTitle() {
				var title = document.getElementById('title');
				if (title && titleField && field) title.dataset.kilkaNoteTitleFont = titleField.value || field.value;
			}
			if (field) field.addEventListener('change', function () {editor.kilkaNoteSetFont(field.value); updateTitle();});
			if (titleField) titleField.addEventListener('change', updateTitle);
			updateTitle();
		});
		editor.addButton('kilka_note_font', {
			type: 'listbox', text: config.labels.paragraph, tooltip: config.labels.paragraph, values: values,
			onselect: function () {
				var key = this.value();
				editor.undoManager.transact(function () {
					keys.forEach(function (name) {editor.formatter.remove('kilka-note-' + name);});
					if (keys.indexOf(key) >= 0) editor.formatter.apply('kilka-note-' + key);
				});
				editor.nodeChanged(); editor.fire('change');
			},
			onPostRender: function () {
				var control = this;
				editor.on('NodeChange', function () {
					var p = editor.dom.getParent(editor.selection.getNode(), 'p');
					control.disabled(!p);
					control.value(p ? keys.find(function (key) {return editor.dom.hasClass(p, 'kilka-note-font-' + key);}) || '' : '');
				});
			}
		});
	});
})();
