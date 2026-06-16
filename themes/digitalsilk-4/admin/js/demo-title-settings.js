(function ($) {
    $(function () {
        if (typeof acf === 'undefined') {
            return;
        }

        let demoTitle = new acf.Model({
            data: {
                fieldDemoTarget: 'demo_title_settings',
                styles: {},
                fieldUnitsMapping: {
                    'backtitle-font-size': 'px',
                    'pretitle-font-size': 'px',
                    'pretitle-font-size-mobile': 'px',
                    'dst--h1-font-size': 'px',
                    'dst--h1-font-size-mobile': 'px',
                    'subtitle-font-size': 'px',
                    'subtitle-font-size-mobile': 'px',
                    'h2-font-size': 'px',
                    'h2-font-size-mobile': 'px',
                    'h3-font-size': 'px',
                    'h3-font-size-mobile': 'px',
                    'h4-font-size': 'px',
                    'h4-font-size-mobile': 'px',
	                'h1-margin-bottom': 'em',
	                'h2-margin-bottom': 'em',
	                'h3-margin-bottom': 'em',
	                'h4-margin-bottom': 'em',
	                'pretitle-margin-bottom': 'em',
	                'subtitle-margin-bottom': 'em',
                },
                colorFields: [
                    'backtitle-color',
                    'backtitle-color-alt',
                    'pretitle-color',
                    'pretitle-color-alt',
                    'section-title-color',
                    'section-title-color-alt',
                    'subtitle-color',
                    'subtitle-color-alt',
                ],
                fontFamilyFields: [
					'backtitle-font',
	                'pretitle-font',
	                'dst--h1-font',
	                'subtitle-font',
	                'h2-font',
	                'h3-font',
	                'h4-font',
                ],
                fontWeightFields: [
                    'backtitle-font-weight',
                    'pretitle-font-weight',
	                'subtitle-font-weight',
	                'h1-font-weight',
                    'h2-font-weight',
                    'h3-font-weight',
                    'h4-font-weight',
                ],
                fields: [
                    'backtitle-font-size',
                    'backtitle-letter-spacing',
                    'backtitle-text-transform',
                    'pretitle-font-size',
                    'pretitle-font-size-mobile',
                    'pretitle-line-height',
                    'pretitle-letter-spacing',
                    'pretitle-margin-bottom',
                    'pretitle-text-transform',
                    'dst--h1-font-size',
                    'dst--h1-font-size-mobile',
                    'h1-line-height',
                    'h1-letter-spacing',
                    'h1-margin-bottom',
                    'h1-text-transform',
                    'subtitle-font-size',
                    'subtitle-font-size-mobile',
                    'subtitle-line-height',
                    'subtitle-letter-spacing',
                    'subtitle-margin-bottom',
                    'subtitle-text-transform',
                    'h2-font-size',
                    'h2-font-size-mobile',
                    'h2-line-height',
                    'h2-letter-spacing',
                    'h2-margin-bottom',
                    'h2-text-transform',
                    'h3-font-size',
                    'h3-font-size-mobile',
                    'h3-line-height',
                    'h3-letter-spacing',
                    'h3-margin-bottom',
                    'h3-text-transform',
                    'h4-font-size',
                    'h4-font-size-mobile',
                    'h4-line-height',
                    'h4-letter-spacing',
                    'h4-margin-bottom',
                    'h4-text-transform',
                ],
            },

            events: {
                change: 'update',
            },

            setup: function (props) {
                $.extend(this.data, props);
            },

            initialize: function () {
                this.wrapper = this.getFieldByName(this.data.fieldDemoTarget);
            },

            getFieldByName: function (name) {
                let $findField = acf.findFields({
                    name: name,
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

            getFontFamily: function (field) {
                return field?.val() ? 'var(' + field.val() + ')' : '';
            },

            getFieldCssVar: function (field) {
                if (!field) {
                    return false;
                }

                let $cssVar = field.$el.find('.acf-label .description');
                return $cssVar.length > 0 && $($cssVar[0]).text() ? $($cssVar[0]).text() : false;
            },

            update: function () {
                if (this.wrapper) {
                    this.updateStyles();
                }
            },

            updateStyles: function () {
                const self = this;

                if (!this.wrapper) {
                    console.warn('Wrapper not found!');
                    return;
                }

                let styles = {};

                // Loop through simple fields
                $.each(this.data.fields, function (index, acfName) {
                    let field = self.getFieldByName(acfName);
                    if (!field || typeof field.val !== 'function') {
                        console.warn(`Field "${acfName}" is missing or invalid.`);
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
                $.each(this.data.fontFamilyFields, function (index, acfName) {
                    let field = self.getFieldByName(acfName);
                    if (!field || typeof field.val !== 'function') {
                        console.warn(`Font Family Field "${acfName}" is missing or invalid.`);
                        return;
                    }

                    let cssVar = self.getFieldCssVar(field);
                    if (cssVar) {
                        styles[cssVar] = self.getFontFamily(field);
                    }
                });

                // Loop through color fields
                $.each(this.data.colorFields, function (index, acfName) {
                    let field = self.getFieldByName(acfName);
                    if (!field || typeof field.val !== 'function') {
                        console.warn(`Color Field "${acfName}" is missing or invalid.`);
                        return;
                    }

                    let cssVar = self.getFieldCssVar(field);
                    if (cssVar) {
                        styles[cssVar] = self.getColor(field, acfName);
                    }
                });


				// Loop through font weight fields
	            $.each(this.data.fontWeightFields, function (index, acfName) {
		            let field = self.getFieldByName(acfName);
		            if (!field || typeof field.val !== 'function') {
			            console.warn(`Font Weight Field "${acfName}" is missing or invalid.`);
			            return;
		            }

		            let cssVar = self.getFieldCssVar(field);
		            if (cssVar) {
			            styles[cssVar] = field.val();
		            }
	            });


                // Apply styles globally to :root
                console.log('Applying styles:', styles);
                $(':root').css(styles);
            },
        });
    });
})(jQuery);
