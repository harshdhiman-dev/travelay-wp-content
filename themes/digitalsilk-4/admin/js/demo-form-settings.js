(function ($) {
    $(function () {
        if (typeof acf === 'undefined') {
            return;
        }

        let demoForm = new acf.Model({
            data: {
                fieldName: 'demo_form_settings',
                className: 'c-form',
                styles: {},
            },

            events: {
                change: 'update',
                'submit form.gform_fields': 'onSubmitForm',
            },

            onSubmitForm: function (e) {
                e.preventDefault();
                console.log('Form submitted:', e.currentTarget);
            },

            setup: function (props) {
                $.extend(this.data, props);
            },

            initialize: function () {
                this.wrapper = this.getFieldByName(this.data.fieldName);
                if (this.wrapper) {
                    this.formBlock = this.wrapper.$el.find('div.' + this.data.className)[0];
                }
            },

            getFieldByName: function (name) {
                let $findField = acf.findFields({ name: name });

                if ($findField.length > 0) {
                    return acf.getField($findField[0].dataset.key);
                } else {
                    return null;
                }
            },

            getColor: function (colorSelectorName) {
                let color = '';
                let colorSelector = this.getFieldByName(colorSelectorName).val();
                if (colorSelector === 'custom') {
                    color = this.getFieldByName(colorSelectorName + '_custom').val();
                    color = color.length > 0 ? color : 'transparent';
                } else {
                    color = 'var(' + colorSelector + ')';
                }

                return color;
            },

            update: function () {
                if (this.wrapper) {
                    this.updateStyles();
                    this.updateForm();
                }
            },

            updateStyles: function () {
                this.data.styles['--dst--form-label-size'] = this.getFieldByName('label-font-size').val() / 10 + 'rem';
                this.data.styles['--dst--form-label-weight'] = this.getFieldByName('label-font-weight').val();
                this.data.styles['--dst--form-label-text-transform'] = this.getFieldByName('label-text-transform').val();
                this.data.styles['--dst--form-label-color'] = this.getColor('dst--form-label-color');

                this.data.styles['--dst--input-font-size'] = this.getFieldByName('input-font-size').val() / 10 + 'rem';
                this.data.styles['--dst--input-font-weight'] = this.getFieldByName('input-font-weight').val();
                this.data.styles['--dst--input-color'] = this.getColor('input-color');
                this.data.styles['--dst--input-bg-color'] = this.getColor('input-background-color');
                this.data.styles['--dst--input-border-width'] = this.getFieldByName('input-border-width').val() + 'px';
                this.data.styles['--dst--input-border-radius'] = this.getFieldByName('input-border-radius').val() + 'px';
                this.data.styles['--dst--input-border-color'] = this.getColor('input-border-color');
                this.data.styles['--dst--input-padding-block'] = this.getFieldByName('input-padding-top-bottom').val() + 'px';
                this.data.styles['--dst--input-padding-inline'] = this.getFieldByName('input-padding-left-right').val() + 'px';
                this.data.styles['--dst--input-height'] = this.getFieldByName('input-height').val() / 10 + 'rem';

                this.data.styles['--dst--form-row-margin-block'] = this.getFieldByName('form-row-margin-top-bottom').val() + 'px';
                this.data.styles['--dst--form-row-margin-inline'] = this.getFieldByName('form-row-margin-left-right').val() + 'px';

                this.data.styles['--dst--message-font-size'] = this.getFieldByName('message-font-size').val() / 10 + 'rem';
                this.data.styles['--dst--message-line-height'] = this.getFieldByName('message-line-height').val();
                this.data.styles['--dst--message-font-weight'] = this.getFieldByName('message-font-weight').val();
                this.data.styles['--dst--validation-error-color'] = this.getFieldByName('dst--validation-error-color').val();
                this.data.styles['--dst--validation-success-color'] = this.getFieldByName('dst--validation-success-color').val();
                this.data.styles['--dst--validation-notice-color'] = this.getFieldByName('dst--validation-notice-color').val();

                // if (this.getFieldByName('base-text-font').val() === '--font-family-primary') {
                //     this.data.styles['--dst--label-font-weight'] = acf.getField('field_fw_p_label-font-weight').val();
                //     this.data.styles['--dst--input-font-weight'] = acf.getField('field_fw_p_input-font-weight').val();
                //     this.data.styles['--dst--message-font-weight'] = acf.getField('field_fw_p_message-font-weight').val();
                // } else {
                //     this.data.styles['--dst--label-font-weight'] = acf.getField('field_fw_s_label-font-weight').val();
                //     this.data.styles['--dst--input-font-weight'] = acf.getField('field_fw_s_input-font-weight').val();
                //     this.data.styles['--dst--message-font-weight'] = acf.getField('field_fw_s_message-font-weight').val();
                // }
            },

            updateForm: function () {
                //update styles
                if (this.formBlock) {
                    for (const styleItem in this.data.styles) {
                        $(this.formBlock).css(styleItem, this.data.styles[styleItem]);
                    }
                }
            },
        });
    });
})(jQuery);
