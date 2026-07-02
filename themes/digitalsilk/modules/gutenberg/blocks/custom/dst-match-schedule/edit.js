/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import {
	useBlockProps,
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
	RichText,
} from '@wordpress/block-editor';
import {
	Button,
	PanelBody,
	PanelRow,
	TextControl,
	RangeControl,
	SelectControl,
	ColorPicker,
} from '@wordpress/components';
import { upload, image as imageIcon, plus, trash } from '@wordpress/icons';
import classNames from 'classnames';

const emptyMatch = ( stage, country ) => ( {
	stage: stage || 'group-stage',
	country: country || 'usa',
	stadium: '',
	groupLabel: '',
	teamAFlag: '🏳️',
	teamAName: '',
	teamBFlag: '🏳️',
	teamBName: '',
	date: '',
	time: '',
	link: '#',
} );

export const BlockEdit = ( props ) => {
	const { attributes, setAttributes, wrapperProps } = props;
	const {
		heading,
		headingColor,
		background,
		stages,
		countries,
		defaultStage,
		defaultCountry,
		ctaText,
		ctaColor,
		matches,
	} = attributes;

	const [ activeStage, setActiveStage ]     = useState( defaultStage );
	const [ activeCountry, setActiveCountry ] = useState( defaultCountry );

	const bgImageUrl     = background?.image?.url || '';
	const bgColor        = background?.bgColor    || '#6b9c3f';
	const overlayColor   = background?.overlayColor   || '#000000';
	const overlayOpacity = background?.overlayOpacity ?? 0;

	const blockProps = useBlockProps( {
		...wrapperProps,
		className: classNames( wrapperProps?.className, 'c-match-schedule' ),
		style: {
			...wrapperProps?.style,
			backgroundColor: bgColor,
		},
	} );

	const updateBackground = ( key, value ) =>
		setAttributes( { background: { ...background, [ key ]: value } } );

	const updateBackgroundImage = ( media ) =>
		updateBackground( 'image', {
			id:  media?.id  || '',
			url: media?.url || '',
			alt: media?.alt || '',
		} );

	const updateStage = ( index, key, value ) => {
		const next = [ ...stages ];
		next[ index ] = { ...next[ index ], [ key ]: value };
		setAttributes( { stages: next } );
	};

	const addStage = () =>
		setAttributes( { stages: [ ...stages, { label: 'New Stage', value: 'new-stage-' + stages.length, isHeading: false } ] } );

	const removeStage = ( index ) => {
		const next = [ ...stages ];
		next.splice( index, 1 );
		setAttributes( { stages: next } );
	};

	const updateCountry = ( index, key, value ) => {
		const next = [ ...countries ];
		next[ index ] = { ...next[ index ], [ key ]: value };
		setAttributes( { countries: next } );
	};

	const addCountry = () =>
		setAttributes( { countries: [ ...countries, { label: 'New Country', value: 'new-country-' + countries.length } ] } );

	const removeCountry = ( index ) => {
		const next = [ ...countries ];
		next.splice( index, 1 );
		setAttributes( { countries: next } );
	};

	const updateMatch = ( index, key, value ) => {
		const next = [ ...matches ];
		next[ index ] = { ...next[ index ], [ key ]: value };
		setAttributes( { matches: next } );
	};

	const addMatch = () =>
		setAttributes( { matches: [ ...matches, emptyMatch( activeStage, activeCountry === 'all-countries' ? 'usa' : activeCountry ) ] } );

	const removeMatch = ( index ) => {
		const next = [ ...matches ];
		next.splice( index, 1 );
		setAttributes( { matches: next } );
	};

	const visibleMatches = matches
		.map( ( match, index ) => ( { match, index } ) )
		.filter( ( { match } ) => {
			const stageMatch   = match.stage === activeStage;
			const countryMatch = activeCountry === 'all-countries' || match.country === activeCountry;
			return stageMatch && countryMatch;
		} );

	return (
		<>
			<InspectorControls>

				{ /* ── Section Settings ───────────────────────────── */ }
				<PanelBody title={ __( 'Section Settings', 'dstheme' ) } initialOpen={ true }>
					<PanelRow>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Heading', 'dstheme' ) }
							value={ heading }
							onChange={ ( value ) => setAttributes( { heading: value } ) }
						/>
					</PanelRow>
					<PanelRow>
						<div className="c-match-schedule__panel-item">
							<p className="c-match-schedule__panel-label">{ __( 'Heading Color', 'dstheme' ) }</p>
							<ColorPicker
								color={ headingColor }
								onChange={ ( value ) => setAttributes( { headingColor: value } ) }
								enableAlpha
							/>
						</div>
					</PanelRow>
					<PanelRow>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'CTA Button Text', 'dstheme' ) }
							value={ ctaText }
							onChange={ ( value ) => setAttributes( { ctaText: value } ) }
						/>
					</PanelRow>
					<PanelRow>
						<div className="c-match-schedule__panel-item">
							<p className="c-match-schedule__panel-label">{ __( 'CTA Button Color', 'dstheme' ) }</p>
							<ColorPicker
								color={ ctaColor }
								onChange={ ( value ) => setAttributes( { ctaColor: value } ) }
								enableAlpha
							/>
						</div>
					</PanelRow>
				</PanelBody>

				{ /* ── Background ──────────────────────────────────── */ }
				<PanelBody title={ __( 'Background', 'dstheme' ) } initialOpen={ false }>
					<PanelRow>
						<div className="c-match-schedule__panel-item">
							<p className="c-match-schedule__panel-label">{ __( 'Background Image', 'dstheme' ) }</p>
							<div className="c-match-schedule__panel-controls">
								{ bgImageUrl && (
									<img className="c-match-schedule__panel-preview" src={ bgImageUrl } alt="" />
								) }
								<MediaUploadCheck>
									<MediaUpload
										onSelect={ ( media ) => updateBackgroundImage( media ) }
										allowedTypes={ [ 'image' ] }
										value={ background?.image?.id }
										render={ ( { open } ) => (
											<Button
												variant="secondary"
												icon={ bgImageUrl ? upload : imageIcon }
												onClick={ open }
											>
												{ bgImageUrl ? __( 'Replace', 'dstheme' ) : __( 'Add Image', 'dstheme' ) }
											</Button>
										) }
									/>
								</MediaUploadCheck>
								{ bgImageUrl && (
									<Button
										variant="tertiary"
										isDestructive
										onClick={ () => updateBackground( 'image', { id: '', url: '', alt: '' } ) }
									>
										{ __( 'Remove', 'dstheme' ) }
									</Button>
								) }
							</div>
						</div>
					</PanelRow>
					<PanelRow>
						<div className="c-match-schedule__panel-item">
							<p className="c-match-schedule__panel-label">{ __( 'Background Color', 'dstheme' ) }</p>
							<ColorPicker
								color={ bgColor }
								onChange={ ( value ) => updateBackground( 'bgColor', value ) }
								enableAlpha
							/>
						</div>
					</PanelRow>
					<PanelRow>
						<RangeControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Overlay Opacity', 'dstheme' ) }
							value={ overlayOpacity }
							onChange={ ( value ) => updateBackground( 'overlayOpacity', value ) }
							min={ 0 }
							max={ 100 }
						/>
					</PanelRow>
					<PanelRow>
						<div className="c-match-schedule__panel-item">
							<p className="c-match-schedule__panel-label">{ __( 'Overlay Color', 'dstheme' ) }</p>
							<ColorPicker
								color={ overlayColor }
								onChange={ ( value ) => updateBackground( 'overlayColor', value ) }
								enableAlpha
							/>
						</div>
					</PanelRow>
				</PanelBody>

				{ /* ── Stages ───────────────────────────────────────── */ }
				<PanelBody title={ __( 'Stages (Sidebar)', 'dstheme' ) } initialOpen={ false }>
					<PanelRow>
						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Default Stage (frontend)', 'dstheme' ) }
							value={ defaultStage }
							options={ stages.filter( ( s ) => ! s.isHeading ).map( ( s ) => ( { label: s.label, value: s.value } ) ) }
							onChange={ ( value ) => setAttributes( { defaultStage: value } ) }
						/>
					</PanelRow>
					{ stages.map( ( stage, index ) => (
						<div className="c-match-schedule__repeater-item" key={ index }>
							<div className="c-match-schedule__repeater-head">
								<strong>{ stage.label || __( 'Stage', 'dstheme' ) }</strong>
								<Button icon={ trash } isDestructive isSmall onClick={ () => removeStage( index ) } />
							</div>
							<TextControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __( 'Label', 'dstheme' ) }
								value={ stage.label }
								onChange={ ( value ) => updateStage( index, 'label', value ) }
							/>
							<TextControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __( 'Value (slug)', 'dstheme' ) }
								value={ stage.value }
								onChange={ ( value ) => updateStage( index, 'value', value ) }
							/>
						</div>
					) ) }
					<Button variant="secondary" icon={ plus } onClick={ addStage } style={ { marginTop: '8px' } }>
						{ __( 'Add Stage', 'dstheme' ) }
					</Button>
				</PanelBody>

				{ /* ── Countries ────────────────────────────────────── */ }
				<PanelBody title={ __( 'Countries (Tabs)', 'dstheme' ) } initialOpen={ false }>
					<PanelRow>
						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Default Country (frontend)', 'dstheme' ) }
							value={ defaultCountry }
							options={ countries.map( ( c ) => ( { label: c.label, value: c.value } ) ) }
							onChange={ ( value ) => setAttributes( { defaultCountry: value } ) }
						/>
					</PanelRow>
					{ countries.map( ( country, index ) => (
						<div className="c-match-schedule__repeater-item" key={ index }>
							<div className="c-match-schedule__repeater-head">
								<strong>{ country.label || __( 'Country', 'dstheme' ) }</strong>
								<Button icon={ trash } isDestructive isSmall onClick={ () => removeCountry( index ) } />
							</div>
							<TextControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __( 'Label', 'dstheme' ) }
								value={ country.label }
								onChange={ ( value ) => updateCountry( index, 'label', value ) }
							/>
							<TextControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __( 'Value (slug)', 'dstheme' ) }
								value={ country.value }
								onChange={ ( value ) => updateCountry( index, 'value', value ) }
							/>
						</div>
					) ) }
					<Button variant="secondary" icon={ plus } onClick={ addCountry } style={ { marginTop: '8px' } }>
						{ __( 'Add Country', 'dstheme' ) }
					</Button>
				</PanelBody>

			</InspectorControls>

			<div { ...blockProps }>
				{ bgImageUrl && (
					<img className="c-match-schedule__bg" src={ bgImageUrl } alt={ background?.image?.alt || '' } aria-hidden="true" />
				) }
				{ overlayOpacity > 0 && (
					<span
						className="c-match-schedule__overlay"
						aria-hidden="true"
						style={ { backgroundColor: overlayColor, opacity: overlayOpacity / 100 } }
					/>
				) }

				<div className="c-match-schedule__inner">
					<RichText
						tagName="h2"
						className="c-match-schedule__heading"
						value={ heading }
						onChange={ ( value ) => setAttributes( { heading: value } ) }
						placeholder={ __( 'Section heading…', 'dstheme' ) }
						allowedFormats={ [] }
						style={ { color: headingColor } }
					/>

					<div className="c-match-schedule__panel">

						<div className="c-match-schedule__sidebar">
							{ stages.map( ( stage, index ) =>
								stage.isHeading ? (
									<p className="c-match-schedule__sidebar-label" key={ index }>{ stage.label }</p>
								) : (
									<button
										type="button"
										key={ index }
										className={ `c-match-schedule__stage-btn${ activeStage === stage.value ? ' -active' : '' }` }
										onClick={ () => setActiveStage( stage.value ) }
									>
										{ stage.label }
									</button>
								)
							) }
						</div>

						<div className="c-match-schedule__content">

							<div className="c-match-schedule__tabs">
								{ countries.map( ( country, index ) => (
									<button
										type="button"
										key={ index }
										className={ `c-match-schedule__tab${ activeCountry === country.value ? ' -active' : '' }` }
										onClick={ () => setActiveCountry( country.value ) }
									>
										{ country.label }
									</button>
								) ) }
							</div>

							<div className="c-match-schedule__grid">
								{ visibleMatches.map( ( { match, index } ) => (
									<div className="c-match-schedule__card" key={ index }>
										<div className="c-match-schedule__card-controls">
											<Button icon={ trash } isDestructive isSmall label={ __( 'Remove match', 'dstheme' ) } onClick={ () => removeMatch( index ) } />
										</div>

										<div className="c-match-schedule__card-top">
											<RichText
												tagName="span"
												className="c-match-schedule__stadium"
												value={ match.stadium }
												onChange={ ( value ) => updateMatch( index, 'stadium', value ) }
												placeholder={ __( 'Stadium name', 'dstheme' ) }
												allowedFormats={ [] }
											/>
											<RichText
												tagName="span"
												className="c-match-schedule__group"
												value={ match.groupLabel }
												onChange={ ( value ) => updateMatch( index, 'groupLabel', value ) }
												placeholder="GROUP A"
												allowedFormats={ [] }
											/>
										</div>

										<div className="c-match-schedule__team">
											<span className={ `c-match-schedule__flag fi fi-${ ( match.teamAFlag || '' ).toLowerCase() }` } />
											<RichText
												tagName="span"
												className="c-match-schedule__team-name"
												value={ match.teamAName }
												onChange={ ( value ) => updateMatch( index, 'teamAName', value ) }
												placeholder={ __( 'Team A', 'dstheme' ) }
												allowedFormats={ [] }
											/>
										</div>
										<div className="c-match-schedule__vs">vs</div>
										<div className="c-match-schedule__team">
											<span className={ `c-match-schedule__flag fi fi-${ ( match.teamBFlag || '' ).toLowerCase() }` } />
											<RichText
												tagName="span"
												className="c-match-schedule__team-name"
												value={ match.teamBName }
												onChange={ ( value ) => updateMatch( index, 'teamBName', value ) }
												placeholder={ __( 'Team B', 'dstheme' ) }
												allowedFormats={ [] }
											/>
										</div>

										<div className="c-match-schedule__bottom">
											<div className="c-match-schedule__datetime">
												<RichText
													tagName="span"
													className="c-match-schedule__date"
													value={ match.date }
													onChange={ ( value ) => updateMatch( index, 'date', value ) }
													placeholder="12 June, Friday"
													allowedFormats={ [] }
												/>
												<span className="c-match-schedule__time">
													Time: <RichText
														tagName="span"
														value={ match.time }
														onChange={ ( value ) => updateMatch( index, 'time', value ) }
														placeholder="9:00 PM ET"
														allowedFormats={ [] }
													/>
												</span>
											</div>
											<span className="c-match-schedule__cta" style={ { backgroundColor: ctaColor } }>
												{ ctaText }
											</span>
										</div>

										<div className="c-match-schedule__card-meta">
											<TextControl
												__next40pxDefaultSize
												__nextHasNoMarginBottom
												label={ __( 'Team A Flag (2-letter code)', 'dstheme' ) }
												value={ match.teamAFlag }
												placeholder="us"
												help={ __( 'ISO country code, e.g. us, gb, fr, jo, at', 'dstheme' ) }
												onChange={ ( value ) => updateMatch( index, 'teamAFlag', value ) }
											/>
											<TextControl
												__next40pxDefaultSize
												__nextHasNoMarginBottom
												label={ __( 'Team B Flag (2-letter code)', 'dstheme' ) }
												value={ match.teamBFlag }
												placeholder="py"
												help={ __( 'ISO country code, e.g. py, mx, ca', 'dstheme' ) }
												onChange={ ( value ) => updateMatch( index, 'teamBFlag', value ) }
											/>
											<SelectControl
												__next40pxDefaultSize
												__nextHasNoMarginBottom
												label={ __( 'Stage', 'dstheme' ) }
												value={ match.stage }
												options={ stages.filter( ( s ) => ! s.isHeading ).map( ( s ) => ( { label: s.label, value: s.value } ) ) }
												onChange={ ( value ) => updateMatch( index, 'stage', value ) }
											/>
											<SelectControl
												__next40pxDefaultSize
												__nextHasNoMarginBottom
												label={ __( 'Country', 'dstheme' ) }
												value={ match.country }
												options={ countries.filter( ( c ) => c.value !== 'all-countries' ).map( ( c ) => ( { label: c.label, value: c.value } ) ) }
												onChange={ ( value ) => updateMatch( index, 'country', value ) }
											/>
											<TextControl
												__next40pxDefaultSize
												__nextHasNoMarginBottom
												label={ __( 'Find Flights Link', 'dstheme' ) }
												value={ match.link }
												placeholder="https://"
												onChange={ ( value ) => updateMatch( index, 'link', value ) }
											/>
										</div>
									</div>
								) ) }

								<div className="c-match-schedule__add-match-wrap">
									<button type="button" className="c-match-schedule__add-match-btn" onClick={ addMatch } title={ __( 'Add Match', 'dstheme' ) }>
										<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path d="M12 5v14M5 12h14" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" />
										</svg>
									</button>
									<span className="c-match-schedule__add-match-label">{ __( 'Add Match', 'dstheme' ) }</span>
								</div>

								{ visibleMatches.length === 0 && (
									<p className="c-match-schedule__empty">{ __( 'No matches found for this selection.', 'dstheme' ) }</p>
								) }
							</div>

						</div>
					</div>
				</div>
			</div>
		</>
	);
};
