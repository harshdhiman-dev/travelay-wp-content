import {__} from '@wordpress/i18n';
import { useDispatch, select, useSelect } from '@wordpress/data';
import {
	useBlockProps,
	useInnerBlocksProps,
	RichText,
	InspectorControls,
} from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
} from '@wordpress/components';
import { ListItemToolbar } from './toolbar';
import { DstIconPicker, DstRangeUnits } from '../../../../react-components';
import classNames from 'classnames';

export default function Edit(props) {
	const { attributes, setAttributes, context, clientId, wrapperProps } = props;
	const { listTitle, listSubTitle, icon, iconSize, heroText, iconDisplay } = attributes;
	const blockProps = useBlockProps(
		{
			...wrapperProps,
			className: classNames( wrapperProps?.className, 'dst-list__item' ),
		}
	);
	const { children } = useInnerBlocksProps(
		blockProps,
		{
			allowedBlocks: [ "ds-blocks/c-list"],
			renderAppender: () => {
				return false;
			},
		}
	);
	const { removeBlock } = useDispatch('core/block-editor');
	const parentClientId = select('core/block-editor').getBlockHierarchyRootClientId(clientId);
	const showSubtitle = context['ds-blocks/showSubtitle'];
	const showIcon     = context['ds-blocks/showIcons'];
	const showHeroText = context['ds-blocks/showHeroText'];

	const hasChildren = useSelect(
		( wpSelect ) => {
			const childBlockClientIds = wpSelect('core/block-editor').getBlockOrder(clientId);
			return childBlockClientIds.length > 0;
		},
		[clientId]
	);

    // On backspace, remove block.
    const onKeyDown = (event) => {
        if (event.key === 'Backspace' && !listTitle) {
            event.preventDefault();
            removeBlock(clientId, parentClientId);
        }
    };

	return (
		<>
			<ListItemToolbar blockProps={props} />
			{ showIcon && ! hasChildren &&
				<InspectorControls>
					<PanelBody>
						<DstRangeUnits
							label={__('Icon Size')}
							value={iconSize}
							onChange={(newSize) => setAttributes({iconSize: newSize})}
						/>
						{
							icon && typeof icon === 'string' && ! isNaN( icon ) && (
								<SelectControl
									__next40pxDefaultSize
									__nextHasNoMarginBottom
									label={__('Icon Display')}
									value={iconDisplay || 'inline'}
									options={[
										{ label: __('Inline SVG'), value: 'inline' },
										{ label: __('Image Tag'), value: 'img' }
									]}
									onChange={(value) => setAttributes({ iconDisplay: value })}
									help={__('Choose how to display SVG icons. Change to "Image Tag" if you have problems with uploaded Icons.')}
								/>
							)
						}
					</PanelBody>
				</InspectorControls>
			}
			<li {...blockProps} >
				{
					hasChildren ? (
						<>
						{
							children
						}
						</>
					) : (
						<>
							{
								showIcon && (
									<div className="dst-list__media ds-list-item__icon" style={{'--dst-list__icon-size': iconSize ? iconSize : undefined}}>
										<DstIconPicker
											icon={icon}
											onChange={(newIcon) => setAttributes({icon: newIcon})}
											size={iconSize}
											placeholder={__('Icon')}
											displayAs={iconDisplay || 'inline'}
										/>
									</div>
								)
							}
							{
								showHeroText && (
									<RichText
										tagName="div"
										value={heroText}
										onChange={(value) => setAttributes({heroText: value})}
										placeholder={__('Hero..')}
										className="dst-list__hero"
									/>
								)
							}
							<div className='dst-list__content'>
								<RichText
									tagName="div"
									value={listTitle}
									onChange={(newTitle) => setAttributes({ listTitle: newTitle })}
									placeholder={__('Enter item title here...')}
									className="dst-list__title"
									identifier='listTitle'
									onKeyDown={onKeyDown}
								/>
								{
									showSubtitle && (
										<RichText
											tagName="div"
											value={listSubTitle}
											onChange={(newTitle) => setAttributes({listSubTitle: newTitle})}
											placeholder={__('Enter your text here...')}
											className="dst-list__description"
										/>
									)
								}
							</div>
							{
								children
							}
						</>
					)
				}
			</li>
		</>
	);
}
