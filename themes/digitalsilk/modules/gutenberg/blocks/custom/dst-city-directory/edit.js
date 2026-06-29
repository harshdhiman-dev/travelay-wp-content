/**
 * WordPress dependencies
 */
import { __, } from '@wordpress/i18n';
import * as React from 'react';
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
	SelectControl,
} from '@wordpress/components';
import { plus, trash, upload, image as imageIcon } from '@wordpress/icons';
import classNames from 'classnames';

const ALPHABET = [ 'All', ...'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split( '' ) ];

// Known slug lists per category — used to filter pages on import.
const COUNTRY_SLUGS = [
	'usa','canada','mexico','united-kingdom','spain','france','brazil',
	'russia','australia','japan','greece','italy','saudi-arabia',
	'switzerland','netherlands','india',
];

const CITY_SLUGS = [
	'atlanta','orlando','new-york','london','paris','miami','san-diego',
	'los-angeles','chicago','boston','austin','seattle','charlotte',
	'houston','phoenix','fort-lauderdale','san-francisco','raleigh',
	'long-beach','santa-ana','newark','sacramento','dallas','columbus',
	'portland','philadelphia','san-antonio','st-louis','oklahoma','porto',
];

const AIRLINE_SLUGS = [
	'delta','american-airlines','lufthansa-airlines','emirates-airlines',
	'delta-airlines',
];

export const BlockEdit = ( props ) => {
	const { attributes, setAttributes, wrapperProps } = props;
	const { heading, cities, itemsPerPage, discoverMoreText, viewMoreText, cardLayout } = attributes;
	const [ importing, setImporting ] = React.useState( false );
	const [ importMsg, setImportMsg ] = React.useState( '' );
	const [ importType, setImportType ] = React.useState( '' );

	const blockProps = useBlockProps( {
		...wrapperProps,
		className: classNames( wrapperProps?.className, 'c-city-directory' ),
	} );

	/**
	 * Fetch all pages and filter by a list of slugs, then import as cities.
	 *
	 * @param {string[]} slugList   List of slugs to match against.
	 * @param {string}   label      Label shown in the loading/success message.
	 */
	const importFromPages = async ( slugList, label ) => {
		setImporting( true );
		setImportMsg( `Fetching ${ label }…` );

		try {
			// Fetch up to 100 pages — covers all your current pages comfortably.
			const res = await fetch(
				`/wp-json/wp/v2/pages?per_page=100&_fields=id,title,slug,link,featured_media`,
				{
					headers: {
						'X-WP-Nonce': window.wpApiSettings?.nonce || '',
					},
				}
			);

			if ( ! res.ok ) {
				throw new Error( `HTTP ${ res.status }` );
			}

			const pages = await res.json();

			// Filter pages whose slug is in our known slug list.
			const matched = pages.filter( ( page ) =>
				slugList.includes( page.slug )
			);

			if ( matched.length === 0 ) {
				setImportMsg( `No ${ label } found.` );
				setImporting( false );
				return;
			}

			// Map to city block format — no image since featured_media is 0 on all.
			const imported = matched.map( ( page ) => ( {
				name: page.title?.rendered || page.slug,
				description: 'Global powerhouse of culture, fashion and finance. NYC is a hub for Broadway shows, flavorful dishes and amazing nightlife.',
				link: page.link || '#',
				media: { id: '', url: '', alt: '' },
			} ) );

			// Replace all existing cities with the newly imported set.
			setAttributes( { cities: imported } );
			setImportMsg( `✓ Imported ${ imported.length } ${ label }` );
			setImportType( label );
		} catch ( err ) {
			setImportMsg( `Error: ${ err.message }` );
		}

		setImporting( false );
	};

	const updateCity = ( index, key, value ) => {
		const newCities = cities.map( ( city, i ) =>
			i === index ? { ...city, [ key ]: value } : city
		);
		setAttributes( { cities: newCities } );
	};

	const updateCityMedia = ( index, media ) => {
		updateCity( index, 'media', {
			id: media?.id || '',
			url: media?.url || '',
			alt: media?.alt || '',
		} );
	};

	const removeCityMedia = ( index ) => {
		updateCity( index, 'media', { id: '', url: '', alt: '' } );
	};

	const addCity = () => {
		setAttributes( {
			cities: [
				...cities,
				{
					name: __( 'New City', 'dstheme' ),
					description: '',
					link: '#',
					media: { id: '', url: '', alt: '' },
				},
			],
		} );
	};

	const removeCity = ( index ) => {
		setAttributes( { cities: cities.filter( ( _, i ) => i !== index ) } );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'dstheme' ) } initialOpen={ true }>
				<PanelRow>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Card Layout', 'dstheme' ) }
						value={ cardLayout }
						options={ [
							{ label: __( 'Horizontal (image left, text right)', 'dstheme' ), value: 'horizontal' },
							{ label: __( 'Vertical (image top, name below)', 'dstheme' ), value: 'vertical' },
						] }
						onChange={ ( value ) => setAttributes( { cardLayout: value } ) }
					/>
				</PanelRow>
					<PanelRow>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Discover More Text', 'dstheme' ) }
							value={ discoverMoreText }
							onChange={ ( value ) => setAttributes( { discoverMoreText: value } ) }
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'View More Button Text', 'dstheme' ) }
							value={ viewMoreText }
							onChange={ ( value ) => setAttributes( { viewMoreText: value } ) }
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Cities per page', 'dstheme' ) }
							value={ String( itemsPerPage ) }
							type="number"
							min={ 3 }
							max={ 30 }
							onChange={ ( value ) => setAttributes( { itemsPerPage: parseInt( value ) || 9 } ) }
						/>
					</PanelRow>
				</PanelBody>

				<PanelBody title={ importType ? `${ importType } (${ cities.length })` : __( 'Cities', 'dstheme' ) } initialOpen={ false }>
					<PanelRow>
						<div style={ { width: '100%' } }>
							<p style={ { margin: '0 0 8px', fontWeight: 600, fontSize: '12px' } }>
								{ __( 'Import from site pages', 'dstheme' ) }
							</p>
							<div style={ { display: 'flex', gap: '8px', flexWrap: 'wrap' } }>
								<Button
									variant="secondary"
									size="small"
									isBusy={ importing }
									disabled={ importing }
									onClick={ () => importFromPages( COUNTRY_SLUGS, 'Countries' ) }
								>
									{ __( '🌍 Import Countries', 'dstheme' ) }
								</Button>
								<Button
									variant="secondary"
									size="small"
									isBusy={ importing }
									disabled={ importing }
									onClick={ () => importFromPages( CITY_SLUGS, 'Cities' ) }
								>
									{ __( '🏙️ Import Cities', 'dstheme' ) }
								</Button>
								<Button
									variant="secondary"
									size="small"
									isBusy={ importing }
									disabled={ importing }
									onClick={ () => importFromPages( AIRLINE_SLUGS, 'Airlines' ) }
								>
									{ __( '✈️ Import Airlines', 'dstheme' ) }
								</Button>
							</div>
							{ importMsg && (
								<p style={ { margin: '8px 0 0', fontSize: '12px', color: importMsg.startsWith( '✓' ) ? '#1f7a4d' : '#cc0000' } }>
									{ importMsg }
								</p>
							) }
							<Button
								variant="tertiary"
								isDestructive
								size="small"
								style={ { marginTop: '8px' } }
								onClick={ () => {
									if ( window.confirm( __( 'Clear all cities?', 'dstheme' ) ) ) {
										setAttributes( { cities: [] } );
										setImportMsg( '' );
									}
								} }
							>
								{ __( 'Clear all', 'dstheme' ) }
							</Button>
						</div>
					</PanelRow>

					{ cities.map( ( city, index ) => (
					<PanelBody
						key={ index }
						title={ city.name || `${ __( 'City', 'dstheme' ) } ${ index + 1 }` }
						initialOpen={ false }
						className="c-city-directory__city-panel"
					>
						<div className="c-city-directory__panel-controls">
							{ city.media?.url && (
								<img
									className="c-city-directory__panel-preview"
									src={ city.media.url }
									alt=""
								/>
							) }
							<MediaUploadCheck>
								<MediaUpload
									onSelect={ ( media ) => updateCityMedia( index, media ) }
									allowedTypes={ [ 'image' ] }
									value={ city.media?.id }
									render={ ( { open } ) => (
										<Button
											variant="secondary"
											icon={ city.media?.url ? upload : imageIcon }
											onClick={ open }
										>
											{ city.media?.url
												? __( 'Replace', 'dstheme' )
												: __( 'Add Image', 'dstheme' ) }
										</Button>
									) }
								/>
							</MediaUploadCheck>
							{ city.media?.url && (
								<Button
									variant="tertiary"
									isDestructive
									onClick={ () => removeCityMedia( index ) }
								>
									{ __( 'Remove', 'dstheme' ) }
								</Button>
							) }
						</div>

						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Link URL', 'dstheme' ) }
							value={ city.link || '' }
							onChange={ ( value ) => updateCity( index, 'link', value ) }
							style={ { marginTop: '8px' } }
						/>

						<Button
							variant="tertiary"
							isDestructive
							icon={ trash }
							onClick={ () => removeCity( index ) }
							style={ { marginTop: '8px' } }
						>
							{ __( 'Remove city', 'dstheme' ) }
						</Button>
					</PanelBody>
				) ) }

					<PanelRow>
						<Button variant="primary" icon={ plus } onClick={ addCity }>
							{ __( 'Add city', 'dstheme' ) }
						</Button>
					</PanelRow>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<RichText
					tagName="h2"
					className="c-city-directory__heading"
					value={ heading }
					onChange={ ( value ) => setAttributes( { heading: value } ) }
					placeholder={ __( 'List of All Cities', 'dstheme' ) }
					allowedFormats={ [] }
				/>

				<div className="c-city-directory__wrapper">
					<div className="c-city-directory__toolbar">
						<div className="c-city-directory__search-wrap">
							<input
								type="text"
								className="c-city-directory__search"
								placeholder={ __( 'Search by City, State or Country', 'dstheme' ) }
								readOnly
							/>
							<span className="c-city-directory__search-icon" aria-hidden="true">
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
									<circle cx="11" cy="11" r="7" stroke="currentColor" strokeWidth="2" />
									<path d="M16.5 16.5L21 21" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
								</svg>
							</span>
						</div>
						<span className="c-city-directory__az-label">{ __( 'A-Z Cities', 'dstheme' ) }</span>
					</div>

					<div className="c-city-directory__alphabet">
						{ ALPHABET.map( ( letter ) => (
							<button
								key={ letter }
								className={ classNames( 'c-city-directory__letter', { '-active': letter === 'All' } ) }
								disabled
							>
								{ letter }
							</button>
						) ) }
					</div>

					<div className={ `c-city-directory__grid c-city-directory__grid--${ cardLayout }` }>
					{ cities.slice( 0, itemsPerPage ).map( ( city, index ) => (
						<div className={ `c-city-directory__card c-city-directory__card--${ cardLayout }` } key={ index }>
							{ cardLayout === 'vertical' ? (
								<>
									<div className="c-city-directory__card-image c-city-directory__card-image--vertical">
										{ city.media?.url ? (
											<img src={ city.media.url } alt={ city.media.alt || city.name } />
										) : (
											<div className="c-city-directory__image-placeholder" />
										) }
									</div>
									<div className="c-city-directory__card-footer">
										<span className="c-city-directory__card-name c-city-directory__card-name--vertical">{ city.name || __( 'City name…', 'dstheme' ) }</span>
										<span className="c-city-directory__card-arrow" style={ { fontSize: '1.4rem', color: '#555' } }>›</span>
									</div>
								</>
							) : (
								<>
									<div className="c-city-directory__card-image">
										{ city.media?.url ? (
											<img src={ city.media.url } alt={ city.media.alt || city.name } />
										) : (
											<div className="c-city-directory__image-placeholder" />
										) }
									</div>
									<div className="c-city-directory__card-body">
										<RichText
											tagName="h3"
											className="c-city-directory__card-name"
											value={ city.name }
											onChange={ ( value ) => updateCity( index, 'name', value ) }
											placeholder={ __( 'City name…', 'dstheme' ) }
											allowedFormats={ [] }
										/>
										<RichText
											tagName="p"
											className="c-city-directory__card-description"
											value={ city.description }
											onChange={ ( value ) => updateCity( index, 'description', value ) }
											placeholder={ __( 'City description…', 'dstheme' ) }
											allowedFormats={ [] }
										/>
										<span className="c-city-directory__card-link">
											{ discoverMoreText } &rsaquo;
										</span>
									</div>
								</>
							) }
						</div>
					) ) }
				</div>

					{ cities.length > itemsPerPage && (
						<div className="c-city-directory__view-more-wrap">
							<button className="c-city-directory__view-more" disabled>
								{ viewMoreText }
							</button>
						</div>
					) }
				</div>
			</div>
		</>
	);
};
