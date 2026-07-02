/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import {
	useBlockProps,
	RichText,
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
} from '@wordpress/block-editor';
import {
	Button,
	PanelBody,
	PanelRow,
	TextControl,
	TextareaControl,
	RangeControl,
	SelectControl,
	ColorPicker,
} from '@wordpress/components';
import { upload, image as imageIcon, plus, trash } from '@wordpress/icons';
import classNames from 'classnames';

const emptyCard = () => ( {
	image:       { id: '', url: '', alt: '' },
	title:       '',
	description: '',
	ctaType:     'email',
	ctaLabel:    '',
	ctaValue:    '',
} );

const CTA_TYPES = [
	{ label: 'Email',  value: 'email' },
	{ label: 'Phone',  value: 'phone' },
	{ label: 'Link',   value: 'link'  },
];

export const BlockEdit = ( props ) => {
	const { attributes, setAttributes, wrapperProps } = props;
	const {
		heading,
		headingColor,
		headingAlign,
		background,
		columns,
		ctaBgColor,
		ctaTextColor,
		ctaBorderColor,
		cards,
	} = attributes;

	const [ openMeta, setOpenMeta ] = useState( {} );

	const bgImageUrl     = background?.image?.url || '';
	const bgColor        = background?.bgColor    || '#1f7a4d';
	const overlayColor   = background?.overlayColor   || '#000000';
	const overlayOpacity = background?.overlayOpacity ?? 0;

	const blockProps = useBlockProps( {
		...wrapperProps,
		className: classNames( wrapperProps?.className, 'c-connect-cards' ),
		style: { ...wrapperProps?.style, backgroundColor: bgColor },
	} );

	const updateBg = ( key, value ) =>
		setAttributes( { background: { ...background, [ key ]: value } } );

	const updateCard = ( index, key, value ) => {
		const next = [ ...cards ];
		next[ index ] = { ...next[ index ], [ key ]: value };
		setAttributes( { cards: next } );
	};

	const updateCardImage = ( index, media ) =>
		updateCard( index, 'image', { id: media?.id || '', url: media?.url || '', alt: media?.alt || '' } );

	const addCard    = () => setAttributes( { cards: [ ...cards, emptyCard() ] } );
	const removeCard = ( i ) => { const n = [ ...cards ]; n.splice( i, 1 ); setAttributes( { cards: n } ); };

	const toggleMeta = ( i ) => setOpenMeta( prev => ( { ...prev, [ i ]: !prev[ i ] } ) );

	const iconSvg = (
		<svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
			<rect x="2" y="4" width="20" height="16" rx="2" stroke="currentColor" strokeWidth="1.8"/>
			<path d="M2 7l10 7 10-7" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round"/>
		</svg>
	);

	return (
		<>
			<InspectorControls>

				{ /* ── Section ──────────────────────────── */ }
				<PanelBody title={ __( 'Section', 'dstheme' ) } initialOpen={ true }>
					<PanelRow>
						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Heading Alignment', 'dstheme' ) }
							value={ headingAlign }
							options={ [
								{ label: 'Left',   value: 'left'   },
								{ label: 'Center', value: 'center' },
								{ label: 'Right',  value: 'right'  },
							] }
							onChange={ ( value ) => setAttributes( { headingAlign: value } ) }
						/>
					</PanelRow>
					<PanelRow>
						<div className="c-connect-cards__panel-item">
							<p className="c-connect-cards__panel-label">{ __( 'Heading Color', 'dstheme' ) }</p>
							<ColorPicker color={ headingColor } onChange={ ( v ) => setAttributes( { headingColor: v } ) } enableAlpha />
						</div>
					</PanelRow>
					<PanelRow>
						<RangeControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Columns', 'dstheme' ) }
							value={ columns }
							onChange={ ( v ) => setAttributes( { columns: v } ) }
							min={ 1 } max={ 4 }
						/>
					</PanelRow>
				</PanelBody>

				{ /* ── Background ───────────────────────── */ }
				<PanelBody title={ __( 'Background', 'dstheme' ) } initialOpen={ false }>
					<PanelRow>
						<div className="c-connect-cards__panel-item">
							<p className="c-connect-cards__panel-label">{ __( 'Background Image', 'dstheme' ) }</p>
							<div className="c-connect-cards__panel-controls">
								{ bgImageUrl && <img className="c-connect-cards__panel-preview" src={ bgImageUrl } alt="" /> }
								<MediaUploadCheck>
									<MediaUpload
										onSelect={ ( m ) => updateBg( 'image', { id: m?.id || '', url: m?.url || '', alt: m?.alt || '' } ) }
										allowedTypes={ [ 'image' ] }
										value={ background?.image?.id }
										render={ ( { open } ) => (
											<Button variant="secondary" icon={ bgImageUrl ? upload : imageIcon } onClick={ open }>
												{ bgImageUrl ? __( 'Replace', 'dstheme' ) : __( 'Add Image', 'dstheme' ) }
											</Button>
										) }
									/>
								</MediaUploadCheck>
								{ bgImageUrl && (
									<Button variant="tertiary" isDestructive onClick={ () => updateBg( 'image', { id: '', url: '', alt: '' } ) }>
										{ __( 'Remove', 'dstheme' ) }
									</Button>
								) }
							</div>
						</div>
					</PanelRow>
					<PanelRow>
						<div className="c-connect-cards__panel-item">
							<p className="c-connect-cards__panel-label">{ __( 'Background Color', 'dstheme' ) }</p>
							<ColorPicker color={ bgColor } onChange={ ( v ) => updateBg( 'bgColor', v ) } enableAlpha />
						</div>
					</PanelRow>
					<PanelRow>
						<RangeControl
							__next40pxDefaultSize __nextHasNoMarginBottom
							label={ __( 'Overlay Opacity', 'dstheme' ) }
							value={ overlayOpacity }
							onChange={ ( v ) => updateBg( 'overlayOpacity', v ) }
							min={ 0 } max={ 100 }
						/>
					</PanelRow>
					<PanelRow>
						<div className="c-connect-cards__panel-item">
							<p className="c-connect-cards__panel-label">{ __( 'Overlay Color', 'dstheme' ) }</p>
							<ColorPicker color={ overlayColor } onChange={ ( v ) => updateBg( 'overlayColor', v ) } enableAlpha />
						</div>
					</PanelRow>
				</PanelBody>

				{ /* ── CTA Style ────────────────────────── */ }
				<PanelBody title={ __( 'CTA Button Style', 'dstheme' ) } initialOpen={ false }>
					<PanelRow>
						<div className="c-connect-cards__panel-item">
							<p className="c-connect-cards__panel-label">{ __( 'Button Background', 'dstheme' ) }</p>
							<ColorPicker color={ ctaBgColor } onChange={ ( v ) => setAttributes( { ctaBgColor: v } ) } enableAlpha />
						</div>
					</PanelRow>
					<PanelRow>
						<div className="c-connect-cards__panel-item">
							<p className="c-connect-cards__panel-label">{ __( 'Button Text Color', 'dstheme' ) }</p>
							<ColorPicker color={ ctaTextColor } onChange={ ( v ) => setAttributes( { ctaTextColor: v } ) } enableAlpha />
						</div>
					</PanelRow>
					<PanelRow>
						<div className="c-connect-cards__panel-item">
							<p className="c-connect-cards__panel-label">{ __( 'Button Border Color', 'dstheme' ) }</p>
							<ColorPicker color={ ctaBorderColor } onChange={ ( v ) => setAttributes( { ctaBorderColor: v } ) } enableAlpha />
						</div>
					</PanelRow>
				</PanelBody>

			</InspectorControls>

			<div { ...blockProps }>
				{ bgImageUrl && <img className="c-connect-cards__bg" src={ bgImageUrl } alt="" aria-hidden="true" /> }
				{ overlayOpacity > 0 && (
					<span className="c-connect-cards__overlay" aria-hidden="true" style={ { backgroundColor: overlayColor, opacity: overlayOpacity / 100 } } />
				) }

				<div className="c-connect-cards__inner">
					<RichText
						tagName="h2"
						className="c-connect-cards__heading"
						value={ heading }
						onChange={ ( v ) => setAttributes( { heading: v } ) }
						placeholder={ __( 'Section heading…', 'dstheme' ) }
						allowedFormats={ [] }
						style={ { color: headingColor, textAlign: headingAlign } }
					/>

					<div className="c-connect-cards__grid" style={ { gridTemplateColumns: `repeat(${ columns }, 1fr)` } }>
						{ cards.map( ( card, index ) => (
							<div className="c-connect-cards__card" key={ index }>

								<div className="c-connect-cards__card-controls">
									<Button icon={ trash } isDestructive isSmall label={ __( 'Remove card', 'dstheme' ) } onClick={ () => removeCard( index ) } />
								</div>

								<div className="c-connect-cards__image-wrap">
									{ card.image?.url ? (
										<img className="c-connect-cards__image" src={ card.image.url } alt={ card.image.alt || '' } />
									) : (
										<MediaUploadCheck>
											<MediaUpload
												onSelect={ ( m ) => updateCardImage( index, m ) }
												allowedTypes={ [ 'image' ] }
												value={ card.image?.id }
												render={ ( { open } ) => (
													<button type="button" className="c-connect-cards__image-placeholder" onClick={ open }>
														<svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
															<rect x="3" y="3" width="18" height="18" rx="2" stroke="#bbb" strokeWidth="1.5"/>
															<circle cx="8.5" cy="8.5" r="1.5" fill="#bbb"/>
															<path d="M21 15l-5-5L5 21" stroke="#bbb" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
														</svg>
														<span>{ __( 'Add Image', 'dstheme' ) }</span>
													</button>
												) }
											/>
										</MediaUploadCheck>
									) }
									{ card.image?.url && (
										<Button
											className="c-connect-cards__image-replace"
											isSmall variant="secondary"
											onClick={ () => { const mc = [ ...cards ]; mc[ index ] = { ...mc[ index ], image: { id: '', url: '', alt: '' } }; setAttributes( { cards: mc } ); } }
										>
											{ __( 'Replace', 'dstheme' ) }
										</Button>
									) }
								</div>

								<RichText
									tagName="h3"
									className="c-connect-cards__card-title"
									value={ card.title }
									onChange={ ( v ) => updateCard( index, 'title', v ) }
									placeholder={ __( 'Card title…', 'dstheme' ) }
									allowedFormats={ [] }
								/>

								<RichText
									tagName="p"
									className="c-connect-cards__card-desc"
									value={ card.description }
									onChange={ ( v ) => updateCard( index, 'description', v ) }
									placeholder={ __( 'Card description…', 'dstheme' ) }
									allowedFormats={ [ 'core/bold', 'core/italic' ] }
								/>

								<span
									className="c-connect-cards__cta"
									style={ { backgroundColor: ctaBgColor, color: ctaTextColor, borderColor: ctaBorderColor } }
								>
									<span className="c-connect-cards__cta-icon">{ iconSvg }</span>
									<RichText
										tagName="span"
										value={ card.ctaLabel }
										onChange={ ( v ) => updateCard( index, 'ctaLabel', v ) }
										placeholder={ __( 'CTA label…', 'dstheme' ) }
										allowedFormats={ [] }
									/>
								</span>

								{ /* Settings toggle */ }
								<div className="c-connect-cards__meta-toggle">
									<button type="button" className="c-connect-cards__meta-btn" onClick={ () => toggleMeta( index ) }>
										<svg width="13" height="13" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path d="M12 15a3 3 0 100-6 3 3 0 000 6z" stroke="currentColor" strokeWidth="1.8"/>
											<path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z" stroke="currentColor" strokeWidth="1.8"/>
										</svg>
										{ openMeta[ index ] ? __( 'Hide settings', 'dstheme' ) : __( 'Edit settings', 'dstheme' ) }
									</button>

									{ openMeta[ index ] && (
										<div className="c-connect-cards__meta">
											<SelectControl
												__next40pxDefaultSize __nextHasNoMarginBottom
												label={ __( 'CTA Type', 'dstheme' ) }
												value={ card.ctaType }
												options={ CTA_TYPES }
												onChange={ ( v ) => updateCard( index, 'ctaType', v ) }
											/>
											<TextControl
												__next40pxDefaultSize __nextHasNoMarginBottom
												label={ __( 'CTA Value (email/phone/URL)', 'dstheme' ) }
												value={ card.ctaValue }
												placeholder={ card.ctaType === 'email' ? 'name@email.com' : card.ctaType === 'phone' ? '+1 234 567 890' : 'https://' }
												onChange={ ( v ) => updateCard( index, 'ctaValue', v ) }
											/>
										</div>
									) }
								</div>

							</div>
						) ) }

						<div className="c-connect-cards__add-wrap">
							<button type="button" className="c-connect-cards__add-btn" onClick={ addCard } title={ __( 'Add Card', 'dstheme' ) }>
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M12 5v14M5 12h14" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round"/>
								</svg>
							</button>
							<span className="c-connect-cards__add-label">{ __( 'Add Card', 'dstheme' ) }</span>
						</div>
					</div>
				</div>
			</div>
		</>
	);
};
