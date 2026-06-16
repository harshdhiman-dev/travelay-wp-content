(function ($) {
    /**
     * initializeBlock
     *
     * Adds custom JavaScript to the block HTML.
     *
     * @date    15/4/19
     * @since   1.0.0
     *
     * @param   object $block The block $ element.
     * @param   object attributes The block attributes (only available when editing).
     * @return  void
     */
    var initializeBlock = function ($block) {
        $block.find('.accordion_title').click(function () {
            if (!$(this).closest('li').hasClass('active')) {
                $block.find('li').removeClass('active');
                $block.find('.accordion_content').hide('medium');

                $(this).closest('li').addClass('active');
                $(this).closest('li').find('.accordion_content').show('medium');
            } else {
                $block.find('li').removeClass('active');
                $block.find('.accordion_content').hide('medium');
            }
        });
    };

    // Initialize each block on page load (front end).
    $(document).ready(function () {
        $('.module__faq').each(function () {
            initializeBlock($(this));
        });
    });

    // Initialize dynamic block preview (editor).
    if (window.acf) {
        window.acf.addAction('render_block_preview/type=faq', initializeBlock);
    }

})(jQuery);
