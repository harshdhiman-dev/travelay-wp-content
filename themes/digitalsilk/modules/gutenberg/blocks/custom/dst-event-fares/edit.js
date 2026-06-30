/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
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
	ColorPicker,
} from '@wordpress/components';
import { upload, image as imageIcon, plus, trash } from '@wordpress/icons';
import classNames from 'classnames';

const emptyFlight = () => ( {
	fromCode: '',
	fromCity: '',
	toCode: '',
	toCity: '',
	date: '',
	price: '',
	link: '#',
} );

const emptyCity = () => ( {
	cityName: '',
	stadium: '',
	matchesText: 'HOSTS 0 MATCHES',
	badgeText: '',
	badgeColor: '#1f7a4d',
	flights: [ emptyFlight() ],
} );

export const BlockEdit = ( props ) => {
	const { attributes, setAttributes, wrapperProps } = props;
	const {
		heading,
		headingColor,
		background,
		columns,
		itemsPerPage,
		viewMoreText,
		cities,
	} = attributes;

	const bgImageUrl     = background?.image?.url || '';
	const bgColor        = background?.bgColor    || '#4a7c2f';
	const overlayColor   = background?.overlayColor   || '#000000';
	const overlayOpacity = background?.overlayOpacity ?? 0;

	const blockProps = useBlockProps( {
		...wrapperProps,
		className: classNames( wrapperProps?.className, 'c-event-fares' ),
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

	const updateCity = ( index, key, value ) => {
		const next = [ ...cities ];
		next[ index ] = { ...next[ index ], [ key ]: value };
		setAttributes( { cities: next } );
	};

	const addCity = () => setAttributes( { cities: [ ...cities, emptyCity() ] } );

	const removeCity = ( index ) => {
		const next = [ ...cities ];
		next.splice( index, 1 );
		setAttributes( { cities: next } );
	};

	const updateFlight = ( cityIndex, flightIndex, key, value ) => {
		const next = [ ...cities ];
		const flights = [ ...( next[ cityIndex ].flights || [] ) ];
		flights[ flightIndex ] = { ...flights[ flightIndex ], [ key ]: value };
		next[ cityIndex ] = { ...next[ cityIndex ], flights };
		setAttributes( { cities: next } );
	};

	const addFlight = ( cityIndex ) => {
		const next = [ ...cities ];
		const flights = [ ...( next[ cityIndex ].flights || [] ), emptyFlight() ];
		next[ cityIndex ] = { ...next[ cityIndex ], flights };
		setAttributes( { cities: next } );
	};

	const removeFlight = ( cityIndex, flightIndex ) => {
		const next = [ ...cities ];
		const flights = [ ...( next[ cityIndex ].flights || [] ) ];
		flights.splice( flightIndex, 1 );
		next[ cityIndex ] = { ...next[ cityIndex ], flights };
		setAttributes( { cities: next } );
	};

	return (
		<>
			<InspectorControls>

				{ /* ── Section settings ───────────────────────────── */ }
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
						<div className="c-event-fares__panel-item">
							<p className="c-event-fares__panel-label">{ __( 'Heading Color', 'dstheme' ) }</p>
							<ColorPicker
								color={ headingColor }
								onChange={ ( value ) => setAttributes( { headingColor: value } ) }
								enableAlpha
							/>
						</div>
					</PanelRow>
					<PanelRow>
						<RangeControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Columns', 'dstheme' ) }
							value={ columns }
							onChange={ ( value ) => setAttributes( { columns: value } ) }
							min={ 1 }
							max={ 4 }
						/>
					</PanelRow>
					<PanelRow>
						<RangeControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Cities Per Page', 'dstheme' ) }
							value={ itemsPerPage }
							onChange={ ( value ) => setAttributes( { itemsPerPage: value } ) }
							min={ 1 }
							max={ 12 }
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( '"View More" Button Text', 'dstheme' ) }
							value={ viewMoreText }
							onChange={ ( value ) => setAttributes( { viewMoreText: value } ) }
						/>
					</PanelRow>
				</PanelBody>

				{ /* ── Background ──────────────────────────────────── */ }
				<PanelBody title={ __( 'Background', 'dstheme' ) } initialOpen={ false }>
					<PanelRow>
						<div className="c-event-fares__panel-item">
							<p className="c-event-fares__panel-label">{ __( 'Background Image', 'dstheme' ) }</p>
							<div className="c-event-fares__panel-controls">
								{ bgImageUrl && (
									<img className="c-event-fares__panel-preview" src={ bgImageUrl } alt="" />
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
						<div className="c-event-fares__panel-item">
							<p className="c-event-fares__panel-label">{ __( 'Background Color', 'dstheme' ) }</p>
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
						<div className="c-event-fares__panel-item">
							<p className="c-event-fares__panel-label">{ __( 'Overlay Color', 'dstheme' ) }</p>
							<ColorPicker
								color={ overlayColor }
								onChange={ ( value ) => updateBackground( 'overlayColor', value ) }
								enableAlpha
							/>
						</div>
					</PanelRow>
				</PanelBody>

				{ /* ── Cities repeater ─────────────────────────────── */ }
				<PanelBody title={ __( 'Host Cities', 'dstheme' ) } initialOpen={ false }>
					{ cities.map( ( city, cityIndex ) => (
						<div className="c-event-fares__repeater-item" key={ cityIndex }>
							<div className="c-event-fares__repeater-head">
								<strong>{ city.cityName || __( 'New City', 'dstheme' ) }</strong>
								<Button
									icon={ trash }
									isDestructive
									isSmall
									label={ __( 'Remove city', 'dstheme' ) }
									onClick={ () => removeCity( cityIndex ) }
								/>
							</div>

							<TextControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __( 'City Name', 'dstheme' ) }
								value={ city.cityName }
								onChange={ ( value ) => updateCity( cityIndex, 'cityName', value ) }
							/>
							<TextControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __( 'Stadium', 'dstheme' ) }
								value={ city.stadium }
								onChange={ ( value ) => updateCity( cityIndex, 'stadium', value ) }
							/>
							<TextControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __( 'Matches Text', 'dstheme' ) }
								value={ city.matchesText }
								placeholder="HOSTS 8 MATCHES"
								onChange={ ( value ) => updateCity( cityIndex, 'matchesText', value ) }
							/>
							<TextControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __( 'Badge Text', 'dstheme' ) }
								value={ city.badgeText }
								placeholder="e.g. Final City"
								onChange={ ( value ) => updateCity( cityIndex, 'badgeText', value ) }
							/>
							<div className="c-event-fares__panel-item">
								<p className="c-event-fares__panel-label">{ __( 'Badge Color', 'dstheme' ) }</p>
								<ColorPicker
									color={ city.badgeColor }
									onChange={ ( value ) => updateCity( cityIndex, 'badgeColor', value ) }
									enableAlpha
								/>
							</div>

							<div className="c-event-fares__flights-repeater">
								<p className="c-event-fares__panel-label" style={ { marginTop: '12px' } }>
									{ __( 'Flights', 'dstheme' ) }
								</p>
								{ ( city.flights || [] ).map( ( flight, flightIndex ) => (
									<div className="c-event-fares__flight-repeater-item" key={ flightIndex }>
										<div className="c-event-fares__repeater-head">
											<small>{ __( 'Flight', 'dstheme' ) } { flightIndex + 1 }</small>
											<Button
												icon={ trash }
												isDestructive
												isSmall
												label={ __( 'Remove flight', 'dstheme' ) }
												onClick={ () => removeFlight( cityIndex, flightIndex ) }
											/>
										</div>
										<div className="c-event-fares__flight-grid">
											<TextControl
												__next40pxDefaultSize
												__nextHasNoMarginBottom
												label={ __( 'From Code', 'dstheme' ) }
												value={ flight.fromCode }
												placeholder="FLL"
												onChange={ ( value ) => updateFlight( cityIndex, flightIndex, 'fromCode', value ) }
											/>
											<TextControl
												__next40pxDefaultSize
												__nextHasNoMarginBottom
												label={ __( 'From City', 'dstheme' ) }
												value={ flight.fromCity }
												placeholder="Fort Lauderdale"
												onChange={ ( value ) => updateFlight( cityIndex, flightIndex, 'fromCity', value ) }
											/>
											<TextControl
												__next40pxDefaultSize
												__nextHasNoMarginBottom
												label={ __( 'To Code', 'dstheme' ) }
												value={ flight.toCode }
												placeholder="SWF"
												onChange={ ( value ) => updateFlight( cityIndex, flightIndex, 'toCode', value ) }
											/>
											<TextControl
												__next40pxDefaultSize
												__nextHasNoMarginBottom
												label={ __( 'To City', 'dstheme' ) }
												value={ flight.toCity }
												placeholder="Newburgh"
												onChange={ ( value ) => updateFlight( cityIndex, flightIndex, 'toCity', value ) }
											/>
											<TextControl
												__next40pxDefaultSize
												__nextHasNoMarginBottom
												label={ __( 'Date', 'dstheme' ) }
												value={ flight.date }
												placeholder="09 Jul 2026"
												onChange={ ( value ) => updateFlight( cityIndex, flightIndex, 'date', value ) }
											/>
											<TextControl
												__next40pxDefaultSize
												__nextHasNoMarginBottom
												label={ __( 'Price', 'dstheme' ) }
												value={ flight.price }
												placeholder="51.0"
												onChange={ ( value ) => updateFlight( cityIndex, flightIndex, 'price', value ) }
											/>
										</div>
										<TextControl
											__next40pxDefaultSize
											__nextHasNoMarginBottom
											label={ __( 'Link URL', 'dstheme' ) }
											value={ flight.link }
											placeholder="https://"
											onChange={ ( value ) => updateFlight( cityIndex, flightIndex, 'link', value ) }
										/>
									</div>
								) ) }
								<Button
									variant="secondary"
									icon={ plus }
									onClick={ () => addFlight( cityIndex ) }
									style={ { marginTop: '8px' } }
								>
									{ __( 'Add Flight', 'dstheme' ) }
								</Button>
							</div>
						</div>
					) ) }

					<Button
						variant="primary"
						icon={ plus }
						onClick={ addCity }
						style={ { marginTop: '12px' } }
					>
						{ __( 'Add City', 'dstheme' ) }
					</Button>
				</PanelBody>

			</InspectorControls>

			<div { ...blockProps }>
				{ bgImageUrl && (
					<img
						className="c-event-fares__bg"
						src={ bgImageUrl }
						alt={ background?.image?.alt || '' }
						aria-hidden="true"
					/>
				) }
				{ overlayOpacity > 0 && (
					<span
						className="c-event-fares__overlay"
						aria-hidden="true"
						style={ {
							backgroundColor: overlayColor,
							opacity: overlayOpacity / 100,
						} }
					/>
				) }

				<div className="c-event-fares__inner">
					{ heading && (
						<h2 className="c-event-fares__heading" style={ { color: headingColor } }>
							{ heading }
						</h2>
					) }

					<div
						className="c-event-fares__grid"
						style={ { gridTemplateColumns: `repeat(${ columns }, 1fr)` } }
					>
						{ cities.slice( 0, itemsPerPage ).map( ( city, index ) => (
							<div className="c-event-fares__card" key={ index }>
								<div className="c-event-fares__card-top">
									<div className="c-event-fares__card-titlewrap">
										<h3 className="c-event-fares__city-name">
											{ city.cityName || __( 'City Name', 'dstheme' ) }
										</h3>
										{ city.badgeText && (
											<span
												className="c-event-fares__badge"
												style={ { backgroundColor: city.badgeColor || '#1f7a4d' } }
											>
												{ city.badgeText }
											</span>
										) }
									</div>
									{ city.stadium && (
										<div className="c-event-fares__stadium">{ city.stadium }</div>
									) }
									{ city.matchesText && (
										<p className="c-event-fares__matches">{ city.matchesText }</p>
									) }
								</div>

								<div className="c-event-fares__flights">
									{ ( city.flights || [] ).map( ( flight, fIndex ) => (
										<div className="c-event-fares__flight" key={ fIndex }>
											<div className="c-event-fares__flight-route">
												<div className="c-event-fares__flight-point">
													<span className="c-event-fares__flight-code">{ flight.fromCode }</span>
													<span className="c-event-fares__flight-city">{ flight.fromCity }</span>
												</div>
												<span className="c-event-fares__flight-line-mid">···········&gt;</span>
												<div className="c-event-fares__flight-point c-event-fares__flight-point--end">
													<span className="c-event-fares__flight-code">{ flight.toCode }</span>
													<span className="c-event-fares__flight-city">{ flight.toCity }</span>
												</div>
											</div>
											<div className="c-event-fares__flight-bottom">
												<span className="c-event-fares__flight-date">{ flight.date }</span>
												<span className="c-event-fares__flight-price">
													${ flight.price }<span className="c-event-fares__flight-per">/per person</span>
												</span>
											</div>
										</div>
									) ) }
								</div>
							</div>
						) ) }
					</div>

					{ cities.length > itemsPerPage && (
						<div className="c-event-fares__view-more-wrap">
							<button className="c-event-fares__view-more" disabled>
								{ viewMoreText }
							</button>
						</div>
					) }
				</div>
			</div>
		</>
	);
};
