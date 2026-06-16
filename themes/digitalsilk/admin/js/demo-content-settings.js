(function($) {
	$(function() {
		if (typeof acf === 'undefined') {
			return;
		}

		let demoTitle = new acf.Model({
			data: {
				fieldDemoTarget: 'demo_content_settings',
				styles: {},
				fieldUnitsMapping: {
					'base-text-size': 'px',
					'larger-text-size': 'px',
					'smaller-text-size': 'px',
					'blog-text-size': 'px',
					'blockquote_font_size': 'px',
					'table_body_font_size': 'px',
					'table_head_font_size': 'px',
					'table_cell_padding': 'px',
					'elements_margin': 'em',
					'dst--blockquote-border-size': 'px',
					'list_item_offset_left': 'px',
					'list_item_icon_position': 'em'
				},
				colorFields: ['dst--body-bg', 'primary-text-color', 'primary-text-color-alt', 'primary-link-color', 'primary-link-color-alt', 'border_color', 'border_color_alternative', 'table_border_color', 'table_head_color', 'table_head_background', 'blockquote_border_color'],
				fontFamilyFields: ['base-text-font'],
				fontWeightFields: ['base-text-font-weight', 'table_body_font_weight'],
				imageFields: ['list_item_style', 'blockquote_style'],
				fields: ['dst--body-bg', 'base-text-line-height', 'base-text-size', 'base-text-line-height', 'body_text_title_placeholder', 'font_size_title_placeholder', 'blockquote_placeholder', 'table_placeholder', 'larger-text-size', 'smaller-text-size', 'blog-text-size', 'blockquote_font_size', 'dst--blockquote-border-size', 'table_body_font_size', 'table_head_font_size', 'table_cell_padding', 'elements_margin', 'list_item_offset_left', 'list_item_icon_position']
			},

			events: {
				change: 'update'
			},

			setup: function(props) {
				$.extend(this.data, props);
			},

			initialize: function() {
				this.wrapper = this.getFieldByName(this.data.fieldDemoTarget);
			},

			getFieldByName: function(name) {
				let $findField = acf.findFields({
					name: name
				});

				return $findField.length > 0 ? acf.getField($findField[0].dataset.key) : false;
			},

			getColor: function(field, fieldName) {
				if (!field || typeof field.val !== 'function') {
					console.warn(`Invalid field for color: ${ fieldName }`);
					return 'transparent';
				}

				const value = field.val();

				if (value === 'custom') {
					const customField = this.getFieldByName(fieldName + '_custom');
					const customValue = customField?.val?.();
					return customValue && customValue.length > 0 ? customValue : 'transparent';
				}

				if (value && value.startsWith('--')) {
					return `var(${ value })`;
				}

				return value || 'transparent';
			},

			getFontFamily: function(field) {
				return field?.val() ? 'var(' + field.val() + ')' : '';
			},

			getImage: function(field) {
				const $img = field.$el.find('.image-wrap img');
				if ($img.length > 0) {
					const src = $img.attr('src');
					if (src && src.length > 0) {
						return src;
					}
				}

				console.warn('Image URL not found in field:', field);
				return null;
			},

			getFieldCssVar: function(field) {
				if (!field) {
					return false;
				}

				let $cssVar = field.$el.find('.acf-label .description');
				return $cssVar.length > 0 && $($cssVar[0]).text() ? $($cssVar[0]).text() : false;
			},

			update: function() {
				if (this.wrapper) {
					this.updateStyles();
				}
			},

			updateStyles: function() {
				const self = this;

				if (!this.wrapper) {
					console.warn('Wrapper not found!');
					return;
				}

				let styles = {};

				// Loop through simple fields
				$.each(this.data.fields, function(index, acfName) {
					let field = self.getFieldByName(acfName);
					if (!field || typeof field.val !== 'function') {
						console.warn(`Field "${ acfName }" is missing or invalid.`);
						return; // Skip this field and continue
					}

					let cssVar = self.getFieldCssVar(field);
					if (cssVar) {
						let valueSuffix = self.data.fieldUnitsMapping[acfName] ?? '';
						const value = field.val();
						if (value !== null && value !== undefined && value !== '') {
							styles[cssVar] = value + valueSuffix;
						}
					}
				});

				// Loop through font family fields
				$.each(this.data.fontFamilyFields, function(index, acfName) {
					let field = self.getFieldByName(acfName);
					if (!field || typeof field.val !== 'function') {
						console.warn(`Font Family Field "${ acfName }" is missing or invalid.`);
						return;
					}

					let cssVar = self.getFieldCssVar(field);
					if (cssVar) {
						styles[cssVar] = self.getFontFamily(field);
					}
				});

				// Loop through color fields
				$.each(this.data.colorFields, function(index, acfName) {
					let field = self.getFieldByName(acfName);
					if (!field || typeof field.val !== 'function') {
						console.warn(`Color Field "${ acfName }" is missing or invalid.`);
						return;
					}

					let cssVar = self.getFieldCssVar(field);
					if (cssVar) {
						styles[cssVar] = self.getColor(field, acfName);
					}
				});

				// Loop through font weight fields
				$.each(this.data.fontWeightFields, function(index, acfName) {
					let field = self.getFieldByName(acfName);
					if (!field || typeof field.val !== 'function') {
						console.warn(`Font Weight Field "${ acfName }" is missing or invalid.`);
						return;
					}

					let cssVar = self.getFieldCssVar(field);
					if (cssVar) {
						styles[cssVar] = field.val();
					}
				});

				$.each(this.data.imageFields, function(index, acfName) {
					let field = self.getFieldByName(acfName);

					const cssVar = self.getFieldCssVar(field);
					if (!cssVar) return;

					let variableName = cssVar;
					if (field.length > 1) {
						variableName = `${ cssVar }-${ index + 1 }`;
					}

					const url = self.getImage(field);
					if (url) {
						styles[variableName] = `url("${ url }")`;
					}
				});

				// Apply styles globally to :root
				console.log('Applying styles:', styles);
				$(':root').css(styles);
			}
		});
	});
})(jQuery);
