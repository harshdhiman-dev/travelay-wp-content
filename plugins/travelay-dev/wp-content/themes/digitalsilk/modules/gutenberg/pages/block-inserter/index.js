import domReady from '@wordpress/dom-ready';
import { getBlockType, unregisterBlockType, registerBlockType } from '@wordpress/blocks';

domReady(
    () => {
        // Define blocks to modify.
        const blocksToModify = [
            'core/paragraph',
            'core/list',
            'core/heading',
            'core/quote',
            'core/table',
            'core/freeform',
        ];

        // Define allowed parent blocks
        const allowedParents = ['ds-blocks/c-btn', 'ds-blocks/c-heading', 'ds-blocks/simple-text', 'ds-blocks/c-accordion-item'];
    
        blocksToModify.forEach(
            (blockName) => {
                const block = getBlockType(blockName);
        
                if (block) {
                    unregisterBlockType(blockName);
        
                    registerBlockType(
                        blockName,
                        {
                            ...block,
                            parent: allowedParents, // Apply the same parent restriction
                        }
                    );
                }
            }
        );
    }
);
