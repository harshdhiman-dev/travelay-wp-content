import { __ } from '@wordpress/i18n';
import { BlockControls } from '@wordpress/block-editor';
import {
	ToolbarGroup,
	ToolbarButton,
    ToolbarDropdownMenu,
    Icon,
} from '@wordpress/components';
import { starFilled, overlayText, arrowLeft, arrowUp, customPostType, formatIndent } from '@wordpress/icons';

export const ListToolbar = (
    {
        blockProps,
    }
) => {
    const { attributes, setAttributes } = blockProps;
    const { showIcons, showHeroText, showSubtitle, alignment } = attributes;

    let decorationIcon = customPostType;
    if (showIcons) {
        decorationIcon = starFilled;
    } else if (showHeroText) {
        decorationIcon = overlayText;
    }

    let decorationText = __('No Hero');
    if (showIcons) {
        decorationText = __('Hero Icons');
    } else if (showHeroText) {
        decorationText = __('Hero Text');
    }

    return (
        <>
            { /* Inline block controls */}
            <BlockControls>
                <ToolbarGroup>
                    <ToolbarDropdownMenu
                        icon={
                            () => (
                                <>
                                    <Icon icon={decorationIcon} />
                                    <span>{decorationText}</span>
                                </>
                            )
                        }
                        label={ __('Change List Decorations') }
                        controls={ [
                            {
                                title: __('No Hero'),
                                icon: customPostType,
                                onClick: () => setAttributes({ showHeroText: false, showIcons: false }),
                            },
                            {
                                title: __('Hero Icons'),
                                icon: starFilled,
                                onClick: () => setAttributes({ showHeroText: false, showIcons: true }),
                            },
                            {
                                title: __('Hero Text'),
                                icon: overlayText,
                                onClick: () => setAttributes({ showHeroText: true, showIcons: false }),
                            },
                        ] }
                    />
                    {
                        ( showHeroText || showIcons ) && (
                            <ToolbarButton
                                icon={ () => (
                                    <>
                                        <Icon icon={alignment === 'row' ? arrowLeft : arrowUp} />
                                        <span>{__('Align')}</span>
                                    </>
                                ) }
                                label={ __('Decoration Position') }
                                onClick={
                                    () => {
                                        setAttributes({
                                            alignment: alignment === 'row' ? 'column' : 'row',
                                        });
                                    }
                                }
                                style={{width: 'auto'}}
                            />
                        )
                    }
                </ToolbarGroup>
                <ToolbarGroup>
                    <ToolbarButton
                        icon={
                            ()=> (
                            <>
                                <Icon icon={formatIndent} />
                                <span>{ showSubtitle ? __('Hide Desc') : __('No Desc') }</span>
                            </>
                            )
                        }
                        label={ __('No Desc') }
                        onClick={() => setAttributes({ showSubtitle: ! showSubtitle })}
                        style={{width: 'auto'}}
                    />
                </ToolbarGroup>
            </BlockControls>
        </>
    );
};
