/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
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
	NumberControl,
} from '@wordpress/components';
import { plus, trash, upload, image as imageIcon } from '@wordpress/icons';
import classNames from 'classnames';

const ALPHABET = [ 'All', ...'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split( '' ) ];

export const BlockEdit = ( props ) => {
	const { attributes, setAttributes, wrapperProps } = props;
	const { heading, cities, itemsPerPage, discoverMoreText, viewMoreText } = attributes;

	const blockProps = useBlockProps( {
		...wrapperProps,
		className: classNames( wrapperProps?.className, 'c-city-directory' ),
	} );

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
						<NumberControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Cities per page', 'dstheme' ) }
							value={ itemsPerPage }
							min={ 3 }
							max={ 30 }
							onChange={ ( value ) => setAttributes( { itemsPerPage: parseInt( value ) || 9 } ) }
						/>
					</PanelRow>
				</PanelBody>

				<PanelBody title={ __( 'Cities', 'dstheme' ) } initialOpen={ false }>
					{ cities.map( ( city, index ) => (
						<PanelRow key={ index } className="c-city-directory__panel-row">
							<div className="c-city-directory__panel-item">
								<p className="c-city-directory__panel-label">
									{ city.name || `${ __( 'City', 'dstheme' ) } ${ index + 1 }` }
								</p>

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
							</div>
						</PanelRow>
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

					<div className="c-city-directory__grid">
						{ cities.slice( 0, itemsPerPage ).map( ( city, index ) => (
							<div className="c-city-directory__card" key={ index }>
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
