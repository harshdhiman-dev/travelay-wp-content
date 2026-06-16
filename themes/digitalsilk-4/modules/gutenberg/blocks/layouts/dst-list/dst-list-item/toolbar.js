import { __ } from '@wordpress/i18n';
import { BlockControls } from '@wordpress/block-editor';
import {
    ToolbarGroup,
    ToolbarButton,
} from '@wordpress/components';
import { postList } from '@wordpress/icons';
import { handleConvertToList } from './utilities';

export const ListItemToolbar = (
    {
        blockProps,
    }
) => {
    const { clientId } = blockProps;

    return (
        <>
            { /* Inline block controls */}
            <BlockControls>
                <ToolbarGroup>
                    <ToolbarButton
                        icon={postList}
                        label={ __('Convert to a list') }
                        onClick={()=>handleConvertToList(clientId)}
                    />
                </ToolbarGroup>
            </BlockControls>
        </>
    );
};
