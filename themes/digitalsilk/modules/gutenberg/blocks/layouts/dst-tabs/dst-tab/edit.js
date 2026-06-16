/* eslint-disable jsdoc/check-param-names */
/* eslint-disable jsdoc/no-undefined-types */
import {
    useBlockProps,
    InnerBlocks,
    store as blockEditorStore
} from '@wordpress/block-editor';
import { useSelect, useDispatch } from '@wordpress/data';
import { getBlockDefaultClassName } from '@wordpress/blocks';
import { DstIconPicker } from '../../../../react-components';
import { useEffect } from '@wordpress/element';
import classnames from 'classnames';

// Helper function to check if an object is empty
const isEmptyObject = (obj) => Object.keys(obj).length === 0;

/**
 * Component to render the tab label.
 *
 * @param {string}   parentBlockClassName - The class name of the parent block.
 * @param {string}   blockClassName       - The class name of the current block.
 * @param {Object}   tabItem              - The tab item data including icon layout, icon, content, etc.
 * @param {number}   selectedIndex        - The index of the currently selected tab.
 * @param {number}   blockIndex           - The index of the current block.
 * @param {Function} handleClick          - The function to handle the click event on the tab label.
 * @return {JSX.Element} The rendered tab label component.
 */
const TabLabel = ({ parentBlockClassName, blockClassName, tabItem, selectedIndex, blockIndex, handleClick }) => {
    // Add classes to labels.
    const labelClassnames = classnames(
        `${parentBlockClassName}__label ${blockClassName}__label`,
        {
            '--active': selectedIndex === blockIndex,
            [`--icon-${tabItem.iconLayout}`]: tabItem.iconLayout && tabItem.iconLayout !== 'none',
        }
    );

    return (
        // eslint-disable-next-line jsx-a11y/click-events-have-key-events
        <span role="button" tabIndex={0} className={labelClassnames} onClick={handleClick}>
            { (tabItem.iconLayout === 'left' || tabItem.iconLayout === 'top') && tabItem.icon && (
                <span
                    className={`${parentBlockClassName}__icon`}
                    style={tabItem.iconColor && { color: tabItem.iconColor }}
                >
                    <DstIconPicker
                        icon={tabItem.icon}
                        size={tabItem.iconSize || '40px'}
                        disabled
                    />
                </span>
            )}
            <span className={`${parentBlockClassName}__text`}>{tabItem.content || ''}</span>
            { (tabItem.iconLayout === 'right' || tabItem.iconLayout === 'bottom') && tabItem.icon && (
                <span
                    className={`${parentBlockClassName}__icon`}
                    style={tabItem.iconColor && { color: tabItem.iconColor }}
                >
                    <DstIconPicker
                        icon={tabItem.icon}
                        size={tabItem.iconSize || '40px'}
                        disabled
                    />
                </span>
            )}
        </span>
    );
};


export const BlockEdit = (props) => {
    const { context, clientId, name, attributes, setAttributes } = props;
	const { currentBlockIndex } = attributes;

    // Extract parent block ID
    const parentClientId = useSelect(
        (select) => {
            const { getBlockParents } = select('core/block-editor');
            const parents = getBlockParents(clientId);
            return parents.length ? parents[0] : null;
        },
        [clientId]
    );

    // Extract parent block name
    const parentBlockName = useSelect(
        (select) => {
            const { getBlock } = select('core/block-editor');
            const parentBlock = getBlock(parentClientId);
            return parentBlock ? parentBlock.name : null;
        },
        [parentClientId]
    );

    // Use the dispatch hook to update the parent block's attributes
    const { updateBlockAttributes } = useDispatch(blockEditorStore);

    // Function to update the parent's blockSelectedIndex attribute
    const updateParentBlockSelectedIndex = (index) => {
        if (parentClientId) {
            updateBlockAttributes(parentClientId, { blockSelectedIndex: index });
        }
    };

	/**
	 * Extract class names from both our parrent block and our block.
	 */
    const parentBlockClassName = parentBlockName ? getBlockDefaultClassName(parentBlockName) : null;
    const blockClassName = getBlockDefaultClassName(name);

    // Select the current inner block index (inner block position)
    const blockIndex = useSelect(
        (select) => select('core/block-editor').getBlockIndex(clientId),
        [clientId]
    );

    /**
     * Run on page load.
     */
    useEffect(
        () => {
            if ( blockIndex !== undefined && ! currentBlockIndex !== undefined ) {
                setAttributes({ currentBlockIndex: blockIndex });
            }
        },
        // Empty dependencies, so it runs only once, on page load.
        // eslint-disable-next-line react-hooks/exhaustive-deps
        []
    );

    // Extract clicked tab from context
    const selectedIndex = context['ds-blocks/selectedIndex'];
    const blockProps = useBlockProps({
        className: selectedIndex === blockIndex ? '--active' : '',
    });

    // Check if the tabs should be transformed into an accordion
    const tabAccordion = context['ds-blocks/tabAccordion'];
    const tabItems = (tabAccordion && context['ds-blocks/tabItem']) ? context['ds-blocks/tabItem'] : {};
    const tabItem = (tabItems && tabItems[blockIndex + 1]) ? tabItems[blockIndex + 1] : {};

    // Function to handle click event and update the parent's blockSelectedIndex attribute
    const handleClick = () => updateParentBlockSelectedIndex(blockIndex);

    return (
        <>
			{tabAccordion && !isEmptyObject(tabItem) && (
				<TabLabel
					parentBlockClassName={parentBlockClassName}
					blockClassName={blockClassName}
					tabItem={tabItem}
					selectedIndex={selectedIndex}
					blockIndex={blockIndex}
					handleClick={handleClick}
				/>
			)}
            <div {...blockProps}>
                <div className={`${blockClassName}__content`}>
                    <InnerBlocks />
                </div>
            </div>
        </>
    );
};
