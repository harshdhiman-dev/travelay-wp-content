import {useState, useEffect} from '@wordpress/element';
import {__} from '@wordpress/i18n';
import {
	useBlockProps,
	InnerBlocks,
	useInnerBlocksProps,
	RichText,
} from '@wordpress/block-editor';
import { dispatch } from '@wordpress/data';
import { HeadingInspectorStyles } from './styles';
import { HeadingToolbar } from './toolbar';
import { HeadingInspectorControls } from './inspector';
import './editor.scss';
import classNames from 'classnames';

export default function Edit(props) {
	const { attributes, setAttributes, wrapperProps, clientId } = props;
	const {
		backtitle,
		pretitle,
		pretitle_color,
		subtitle,
		subtitle_color,
		title,
		title_styles,
		alignment,
		alignmentMobile,
		showReadMore,
		description_hidden,
		description_hidden_button,
		description_hidden_button_less,
		showPretitle,
		showTitle,
		showSubtitle,
		showDescription,
		headingTheme,
		moduleVariant,
		disableModuleMargins,
	} = attributes;
	const { tag, tag_style } = title_styles;
	const additionalClasses = classNames(
		{
			[`text-${alignment}`]: alignment,
			[`text-${alignmentMobile}-mobile`]: alignmentMobile,
			'is-style-colors-inverted': headingTheme === 'inverted',
			[moduleVariant]: Boolean(moduleVariant),
			'no-inner-margin': disableModuleMargins,
		}
	);
	const blockProps = useBlockProps(
		{
			...wrapperProps, // Wrapper props contain all of the inline classes, styles, etc..
			className: classNames( wrapperProps?.className, additionalClasses ),
		}
	);
	// Create our inner blocks structure, for the description part.
    const { children, ...innerBlocksProps } = useInnerBlocksProps(
		{
			className: 'c-heading__description is-wysiwyg'
		},
		{
			allowedBlocks: [ 'core/paragraph', 'core/list', 'ds-blocks/c-list', 'ds-blocks/c-icon', 'ds-blocks/c-media', 'ds-blocks/button-group', 'ds-blocks/simple-text', 'acf/shortcode' ],
			template: [
				[
					'ds-blocks/simple-text'
				]
			],
			templateLock: false,
            renderAppender: () => (
				<div className="ds-heading-appender">
					<InnerBlocks.DefaultBlockAppender />
				</div>
			),
    	}
	);

	// Set popover states.
	const [ backtitlePopoverVisible, setBacktitlePopoverVisible ] = useState(false);
	const [ headingPopoverVisible, setHeadingPopoverVisible ] = useState(false);
	const [ alignmentPopoverVisible, setAlignmentPopoverVisible ] = useState(false);

	// Set read more toggle state.
	const [ toggleReadMore, setToggleReadMore ] = useState(false);

	// Close all popovers.
	const closePopovers = () => {
		setBacktitlePopoverVisible(false);
		setHeadingPopoverVisible(false);
		setAlignmentPopoverVisible(false);
	};

	// Reset description hidden button text on page load, to clear up any old values.
	useEffect(
		() => {
			if ( ! showDescription ) {
				setAttributes(
					{
						showReadMore: false,
						description_hidden: '',
						description_hidden_button: __( 'Show More' ),
						description_hidden_button_less: __( 'Show Less' )
					}
				);
			}
		},
		// eslint-disable-next-line react-hooks/exhaustive-deps
		[]
	);

	// Remove all of the inner blocks, when we hide the description.
	useEffect(
		() => {
			if ( ! showDescription ) {
				// Remove all inner blocks
				dispatch('core/block-editor').replaceInnerBlocks(clientId, []);
			}
		},
		// eslint-disable-next-line react-hooks/exhaustive-deps
		[ showDescription]
	);

	return (
		<>
			{ /* All of our toolbar controls */ }
			<HeadingToolbar
				blockProps={props}
				backtitlePopoverVisible={backtitlePopoverVisible}
				setBacktitlePopoverVisible={setBacktitlePopoverVisible}
				headingPopoverVisible={headingPopoverVisible}
				setHeadingPopoverVisible={setHeadingPopoverVisible}
				alignmentPopoverVisible={alignmentPopoverVisible}
				setAlignmentPopoverVisible={setAlignmentPopoverVisible}
			/>
			{ /* Inspector Controls ( shown in the sidebar ) */ }
			<HeadingInspectorControls blockProps={props} setToggleReadMore={setToggleReadMore} />
			{ /* Inspector Styles ( shown in the sidebar ) */ }
			<HeadingInspectorStyles blockProps={props} />

			<div {...blockProps}>
				<div className={`c-heading -${tag_style}`}>
					{
						backtitle && (
							<div className='c-heading__preamble' style={{pointerEvents: 'none'}}>
								<span>{backtitle}</span>
							</div>
						)
					}
					{
						showPretitle && (
							<div className='c-heading__pre' style={pretitle_color ? {color: pretitle_color} : {}}>
								<RichText
									value={pretitle}
									onChange={(newValue) => setAttributes({pretitle: newValue})}
									onClick={closePopovers}
									tagName='span'
									placeholder={__('Enter pre-title text here..')}
								/>
							</div>
						)
					}
					{
						showTitle && (
							<RichText
								value={title}
								onChange={(newValue) => setAttributes({title: newValue})}
								onClick={closePopovers}
								tagName={tag}
								className='c-heading__title'
								placeholder={__('Enter heading text here..')}
								style={title_styles?.color ? {color: title_styles.color} : {}}
							/>
						)
					}
					{
						showSubtitle && (
							<div className='c-heading__sub'>
								<RichText
									value={subtitle}
									onChange={(newValue) => setAttributes({subtitle: newValue})}
									onClick={closePopovers}
									tagName='span'
									placeholder={__('Enter subtitle text here..')}
									style={subtitle_color ? {color: subtitle_color} : {}}
								/>
							</div>
						)
					}
				</div>
				{
					showDescription && (
						<div {...innerBlocksProps}>
							{ children }
							{
								showReadMore && (
									<div className={`read-more-wrapper ${toggleReadMore ? 'is-active' : ''}`}>
										<div className="read-more-text">
											<RichText
												value={description_hidden}
												onChange={(newValue) => setAttributes({description_hidden: newValue})}
												onClick={closePopovers}
												tagName='p'
												placeholder={__('Type hidden content here...')}
											/>
										</div>
										<button
											className="c-btn -normal -link cta_1 read-more-toggle js-read-more-toggle"
											data-show-less-text={description_hidden_button_less}
											onClick={ () => setToggleReadMore(!toggleReadMore) }
										>
											<span className='c-btn__txt'>
												{
													toggleReadMore ? description_hidden_button_less : description_hidden_button
												}
											</span>
										</button>
									</div>
								)
							}
						</div>
					)
				}
			</div>
		</>
	);
}
