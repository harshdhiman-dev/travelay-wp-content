(function($){

    if (typeof (acf) === 'undefined') { return; }

    acf.addAction('new_field', function(field){
        if (!field.$el.hasClass('js-ds-super-admin')) {
            return;
        }
        $('[data-key="' + field.data.key + '"]').hide();
    });

})(jQuery);
