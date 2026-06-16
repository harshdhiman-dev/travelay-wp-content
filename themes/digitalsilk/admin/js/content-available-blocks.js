(function($) {

    if (typeof (acf) === 'undefined') { return; }

    wp.domReady(function () {
        var blockList = wp.data.select('core/block-editor').getBlocks();
        var lastSelectedBlock = null;

        wp.data.subscribe(function () {
            var selectedBlock = wp.data.select('core/block-editor').getSelectedBlock();
            var currentBlockList = wp.data.select('core/block-editor').getBlocks();

            if (JSON.stringify(blockList) !== JSON.stringify(currentBlockList)) {
                blockList = currentBlockList;
                lastSelectedBlock = null;
            }

            if (
                lastSelectedBlock?.clientId !== selectedBlock?.clientId &&
                (selectedBlock?.name === 'acf/banner-6' || selectedBlock?.name === 'acf/navigation')
            ) {
                lastSelectedBlock = selectedBlock;
                setTimeout(function () {
                    loadAnchorAvailableBlocks();
                }, 0)
            }
        });

        acf.addAction('new_field/name=anchor_available_blocks', function (field) {
            loadAnchorAvailableBlocks();
        });

        function loadAnchorAvailableBlocks() {
            var currentBlockList = wp.data.select('core/block-editor').getBlocks();
            var $availableBlocks = $('[data-name="anchor_available_blocks"] select');

            if (currentBlockList.length > 0) {

                $.each($availableBlocks, function (index, select) {
                    var $select = $(select);
                    var selectedVal = $select.val();
                    $select.empty();
                    $select.append($('<option></option>').val('').html('Choose link'));
                    $.each(currentBlockList, function (index2, block) {
                        var blockObject = wp.data.select('core/blocks').getBlockType(block.name);
                        $select.append($('<option></option>').val(block.attributes.id).html('Section ' + (index2 + 1) + ' - ' + blockObject.title));

                        if (block.innerBlocks.length > 0) {
                            $.each(block.innerBlocks, function (index3, block2) {
                                var blockObject = wp.data.select('core/blocks').getBlockType(block2.name);
                                $select.append($('<option></option>').val(block2.attributes.id).html('Section ' + (index2 + 1) + ' - ' + blockObject.title));
                            });
                        }
                    });

                    $select.val(selectedVal).change();
                });

            } else {
                $availableBlocks.empty();
            }
        }
    })

})(jQuery);