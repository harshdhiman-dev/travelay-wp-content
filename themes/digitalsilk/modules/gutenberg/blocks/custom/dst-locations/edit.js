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
	SelectControl,
	ToggleControl,
} from '@wordpress/components';
import { upload, image as imageIcon } from '@wordpress/icons';
import classNames from 'classnames';

export const BlockEdit = ( props ) => {
	const { attributes, setAttributes, wrapperProps } = props;
	const {
		heading,
		showDepartureBoard,
		showGlobe,
		showSearch,
		showFilters,
		showServiceFilters,
		showMapDirections,
		showWhatsapp,
		whatsappNumber,
		showStats,
		showMap,
		showCta,
		defaultPhone,
		ctaUrl,
		ctaTitle,
		ctaText,
		mobileDefaultView,
		mobileMapHeight,
		stickySearch,
		cardDensity,
		mapHeightDesktop,
		heroImage,
	} = attributes;

	const blockProps = useBlockProps( {
		...wrapperProps,
		className: classNames( wrapperProps?.className, 'tl-locations tl-locations--editor-preview' ),
	} );

	return (
		<>
			<InspectorControls>

				{ /* ── Content ──────────────────────────── */ }
				<PanelBody title={ __( 'Content', 'dstheme' ) } initialOpen={ true }>
					<PanelRow>
						<TextControl
							__next40pxDefaultSize __nextHasNoMarginBottom
							label={ __( 'Heading', 'dstheme' ) }
							value={ heading }
							onChange={ ( v ) => setAttributes( { heading: v } ) }
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							__next40pxDefaultSize __nextHasNoMarginBottom
							label={ __( 'Default Phone', 'dstheme' ) }
							value={ defaultPhone }
							onChange={ ( v ) => setAttributes( { defaultPhone: v } ) }
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							__next40pxDefaultSize __nextHasNoMarginBottom
							label={ __( 'WhatsApp Number (digits only)', 'dstheme' ) }
							value={ whatsappNumber }
							onChange={ ( v ) => setAttributes( { whatsappNumber: v } ) }
						/>
					</PanelRow>
				</PanelBody>

				{ /* ── Hero ─────────────────────────────── */ }
				<PanelBody title={ __( 'Hero Background', 'dstheme' ) } initialOpen={ false }>
					<PanelRow>
						<div style={ { width: '100%' } }>
							<p style={ { fontSize: '11px', fontWeight: 600, textTransform: 'uppercase', color: '#1e1e1e', margin: '0 0 8px' } }>
								{ __( 'Hero Image (overrides globe)', 'dstheme' ) }
							</p>
							<div style={ { display: 'flex', flexDirection: 'column', gap: '8px' } }>
								{ heroImage?.url && <img src={ heroImage.url } alt="" style={ { width: '100%', maxHeight: '80px', objectFit: 'cover', borderRadius: '4px' } } /> }
								<MediaUploadCheck>
									<MediaUpload
										onSelect={ ( m ) => setAttributes( { heroImage: { id: m?.id || '', url: m?.url || '', alt: m?.alt || '' } } ) }
										allowedTypes={ [ 'image' ] }
										value={ heroImage?.id }
										render={ ( { open } ) => (
											<Button variant="secondary" icon={ heroImage?.url ? upload : imageIcon } onClick={ open }>
												{ heroImage?.url ? __( 'Replace', 'dstheme' ) : __( 'Add Hero Image', 'dstheme' ) }
											</Button>
										) }
									/>
								</MediaUploadCheck>
								{ heroImage?.url && (
									<Button variant="tertiary" isDestructive onClick={ () => setAttributes( { heroImage: { id: '', url: '', alt: '' } } ) }>
										{ __( 'Remove', 'dstheme' ) }
									</Button>
								) }
							</div>
						</div>
					</PanelRow>
					<PanelRow>
						<ToggleControl __nextHasNoMarginBottom label={ __( 'Globe Background', 'dstheme' ) } checked={ showGlobe } onChange={ ( v ) => setAttributes( { showGlobe: v } ) } />
					</PanelRow>
					<PanelRow>
						<ToggleControl __nextHasNoMarginBottom label={ __( 'Departure Board', 'dstheme' ) } checked={ showDepartureBoard } onChange={ ( v ) => setAttributes( { showDepartureBoard: v } ) } />
					</PanelRow>
				</PanelBody>

				{ /* ── Features ─────────────────────────── */ }
				<PanelBody title={ __( 'Features', 'dstheme' ) } initialOpen={ false }>
					<PanelRow><ToggleControl __nextHasNoMarginBottom label={ __( 'Search Bar', 'dstheme' ) }         checked={ showSearch }         onChange={ ( v ) => setAttributes( { showSearch: v } ) }         /></PanelRow>
					<PanelRow><ToggleControl __nextHasNoMarginBottom label={ __( 'Country Filters', 'dstheme' ) }    checked={ showFilters }        onChange={ ( v ) => setAttributes( { showFilters: v } ) }        /></PanelRow>
					<PanelRow><ToggleControl __nextHasNoMarginBottom label={ __( 'Service Filters', 'dstheme' ) }    checked={ showServiceFilters } onChange={ ( v ) => setAttributes( { showServiceFilters: v } ) } /></PanelRow>
					<PanelRow><ToggleControl __nextHasNoMarginBottom label={ __( 'In-Map Directions', 'dstheme' ) }  checked={ showMapDirections }  onChange={ ( v ) => setAttributes( { showMapDirections: v } ) }  /></PanelRow>
					<PanelRow><ToggleControl __nextHasNoMarginBottom label={ __( 'WhatsApp Button', 'dstheme' ) }    checked={ showWhatsapp }       onChange={ ( v ) => setAttributes( { showWhatsapp: v } ) }       /></PanelRow>
					<PanelRow><ToggleControl __nextHasNoMarginBottom label={ __( 'Stats Bar', 'dstheme' ) }          checked={ showStats }          onChange={ ( v ) => setAttributes( { showStats: v } ) }          /></PanelRow>
					<PanelRow><ToggleControl __nextHasNoMarginBottom label={ __( 'Interactive Map', 'dstheme' ) }    checked={ showMap }            onChange={ ( v ) => setAttributes( { showMap: v } ) }            /></PanelRow>
					<PanelRow><ToggleControl __nextHasNoMarginBottom label={ __( 'Bottom CTA Strip', 'dstheme' ) }   checked={ showCta }            onChange={ ( v ) => setAttributes( { showCta: v } ) }            /></PanelRow>
				</PanelBody>

				{ /* ── Bottom CTA ─────────────────────── */ }
				<PanelBody title={ __( 'Bottom CTA', 'dstheme' ) } initialOpen={ false }>
					<PanelRow>
						<TextControl __next40pxDefaultSize __nextHasNoMarginBottom label={ __( 'CTA Title', 'dstheme' ) } value={ ctaTitle } onChange={ ( v ) => setAttributes( { ctaTitle: v } ) } />
					</PanelRow>
					<PanelRow>
						<TextareaControl __nextHasNoMarginBottom label={ __( 'CTA Text', 'dstheme' ) } value={ ctaText } rows={ 3 } onChange={ ( v ) => setAttributes( { ctaText: v } ) } />
					</PanelRow>
					<PanelRow>
						<TextControl __next40pxDefaultSize __nextHasNoMarginBottom label={ __( 'Book Flights URL', 'dstheme' ) } value={ ctaUrl } onChange={ ( v ) => setAttributes( { ctaUrl: v } ) } />
					</PanelRow>
				</PanelBody>

				{ /* ── Mobile & Layout ─────────────────── */ }
				<PanelBody title={ __( 'Mobile & Layout', 'dstheme' ) } initialOpen={ false }>
					<PanelRow>
						<SelectControl
							__next40pxDefaultSize __nextHasNoMarginBottom
							label={ __( 'Default Mobile View', 'dstheme' ) }
							value={ mobileDefaultView }
							options={ [
								{ label: __( 'List first', 'dstheme' ), value: 'list' },
								{ label: __( 'Map first', 'dstheme' ),  value: 'map'  },
							] }
							onChange={ ( v ) => setAttributes( { mobileDefaultView: v } ) }
						/>
					</PanelRow>
					<PanelRow>
						<RangeControl __next40pxDefaultSize __nextHasNoMarginBottom label={ __( 'Mobile Map Height (vh)', 'dstheme' ) } value={ mobileMapHeight } onChange={ ( v ) => setAttributes( { mobileMapHeight: v } ) } min={ 40 } max={ 85 } step={ 5 } />
					</PanelRow>
					<PanelRow>
						<RangeControl __next40pxDefaultSize __nextHasNoMarginBottom label={ __( 'Desktop Map Height (px)', 'dstheme' ) } value={ mapHeightDesktop } onChange={ ( v ) => setAttributes( { mapHeightDesktop: v } ) } min={ 360 } max={ 800 } step={ 20 } />
					</PanelRow>
					<PanelRow>
						<ToggleControl __nextHasNoMarginBottom label={ __( 'Sticky Search on Scroll', 'dstheme' ) } checked={ stickySearch } onChange={ ( v ) => setAttributes( { stickySearch: v } ) } />
					</PanelRow>
					<PanelRow>
						<SelectControl
							__next40pxDefaultSize __nextHasNoMarginBottom
							label={ __( 'Card Density', 'dstheme' ) }
							value={ cardDensity }
							options={ [
								{ label: __( 'Comfortable', 'dstheme' ), value: 'comfortable' },
								{ label: __( 'Compact', 'dstheme' ),     value: 'compact'     },
							] }
							onChange={ ( v ) => setAttributes( { cardDensity: v } ) }
						/>
					</PanelRow>
				</PanelBody>

			</InspectorControls>

			<div { ...blockProps }>
				<div style={ { padding: '32px 24px', background: 'linear-gradient(135deg, #f3f8f5 0%, #fff 60%)', borderRadius: '12px', textAlign: 'center', border: '2px dashed #c8e6d4' } }>
					<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style={ { display: 'block', margin: '0 auto 16px' } }>
						<path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" fill="#1f7a4d" opacity=".7"/>
					</svg>
					<h3 style={ { fontSize: '1.6rem', fontWeight: 700, color: '#1a1a1a', margin: '0 0 8px' } }>{ heading || __( 'Locations Explorer', 'dstheme' ) }</h3>
					<p style={ { fontSize: '1.3rem', color: '#666', margin: '0 0 12px' } }>
						{ __( 'Location cards, map, and filters render on the frontend. Manage locations under', 'dstheme' ) }
						{ ' ' }
						<strong>{ __( 'Locations → Add New', 'dstheme' ) }</strong>
						{ __( ' in the admin menu.', 'dstheme' ) }
					</p>
					<p style={ { fontSize: '1.2rem', color: '#999', margin: 0 } }>
						{ showMap ? '✅ Map' : '❌ Map' } { ' · ' }
						{ showSearch ? '✅ Search' : '❌ Search' } { ' · ' }
						{ showStats ? '✅ Stats' : '❌ Stats' } { ' · ' }
						{ showCta ? '✅ CTA' : '❌ CTA' }
					</p>
				</div>
			</div>
		</>
	);
};
