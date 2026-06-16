(function($) {

    if (typeof (acf) === 'undefined') { return; }

    function wrapper_range (){
        if( jQuery('div.acf-field-group-wrapper-1-layout-settings-columns-ratio').length == 0  ) return false;

        if( jQuery('.range_values').length > 0 ) return false;

        function changeVal( input ){
            let prevval = input.val()
            let nextval = 100 - prevval;
            jQuery('.range_values').remove();
            $input_r.parent().append('<div class="range_values"></span>');
            if(jQuery('.range_values')) jQuery('.range_values').html(prevval+' : '+nextval);
        }

        const $acf_field = jQuery('div.acf-field-group-wrapper-1-layout-settings-columns-ratio');

        var   $input_r     = $acf_field.find('input[type=range]');
        var   $input_n     = $acf_field.find('input[type=number]');
        $input_n.hide();

        changeVal($input_r);

        $input_r.on("change mousemove",function(){
            let $this = jQuery(this);
            //console.log($this.val());
            changeVal($this);
        })
    }

    wp.domReady(function () {
        var blockList = wp.data.select('core/block-editor').getBlocks();

        wp.data.subscribe(function () {
            var selectedBlock = wp.data.select('core/block-editor').getSelectedBlock()

            if (selectedBlock?.name === 'acf/wrapper-1') {
                setTimeout(function () {
                    wrapper_range();
                }, 0)
            }
        });

    })



})(jQuery);