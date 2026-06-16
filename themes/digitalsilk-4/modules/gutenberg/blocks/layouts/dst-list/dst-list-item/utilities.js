import { dispatch, select, useSelect } from '@wordpress/data';
import { createBlock } from '@wordpress/blocks';
import { store as blockEditorStore } from '@wordpress/block-editor';

// Handle converting selected list items to a list block
export const handleConvertToList = ( clientId ) => {
    const { getSelectedBlockClientIds, getBlocksByClientId } = select(blockEditorStore);
    const { insertBlock, selectBlock, removeBlocks } = dispatch(blockEditorStore);

    const selectedIds = getSelectedBlockClientIds();

    if (selectedIds.length === 0) {
        return;
    }

    const selectedBlocks = getBlocksByClientId(selectedIds);

    const selectedListItems = selectedBlocks.filter(
        (block) => block.name === 'ds-blocks/c-list-item'
    );

    const newListBlock = createBlock('ds-blocks/c-list');

    const copiedChildren = selectedListItems.map((block) => {
        return createBlock('ds-blocks/c-list-item', { ...block.attributes });
    });

    newListBlock.innerBlocks = copiedChildren;

    insertBlock(newListBlock, undefined, clientId);
    selectBlock(newListBlock.clientId);

    const [, ...remainingIds] = selectedListItems.map((block) => block.clientId);
    if (remainingIds.length > 0) {
        removeBlocks(remainingIds);
    }
};

/**
 * Check if the block is inside a nested list structure:
 * ds-blocks/c-list-item > ds-blocks/c-list > ds-blocks/c-list-item (target)
 *
 * @param {string} clientId - The clientId of the block to check.
 * @return {boolean} True if inside a nested list item.
 */
export const useIsInsideNestedListItem = (clientId) => {
	return useSelect(
		(wpSelect) => {
			const blockEditor = wpSelect(blockEditorStore);

			const parentIds = blockEditor.getBlockParents(clientId); // from immediate parent → upward

			if (parentIds.length < 2) {
				return false;
			}

			const parentBlock   = blockEditor.getBlock(parentIds[0]); // immediate parent
			const grandParentBlock = blockEditor.getBlock(parentIds[1]); // grandparent

			return (
				parentBlock?.name === 'ds-blocks/c-list' &&
				grandParentBlock?.name === 'ds-blocks/c-list-item'
			);
		},
		[clientId]
	);
};