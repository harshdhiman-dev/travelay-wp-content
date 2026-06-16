import { __ } from '@wordpress/i18n';
import {
    PanelBody,
    SelectControl,
    __experimentalToggleGroupControl as ToggleGroupControl,
    __experimentalToggleGroupControlOption as ToggleGroupControlOption
} from '@wordpress/components';
import {
    InspectorControls,
} from '@wordpress/block-editor';


export const WrapperInspectorControls = (
    {
        blockProps
    }
) => {
    const { attributes, setAttributes } = blockProps;
    const {
        htmlTag,
        moduleVariant,
		heightVariant
    } = attributes;

    let helpText = __( 'Defines a section in a document.' );
    switch ( htmlTag ) {
        case 'header':
            helpText = __( 'Defines a header for a document or section. This is useful when you are creating a header template.' );
            break;
        case 'footer':
            helpText = __( 'Defines a footer for a document or section. This is useful when you are creating a footer template.' );
            break;
        case 'article':
            helpText = __( 'Defines an independent, self-contained content.' );
            break;
        case 'section':
            helpText = __( 'Defines a section in a document.' );
            break;
        case 'aside':
            helpText = __( 'Defines content aside from the content it is placed in (like a sidebar).' );
            break;
        case 'nav':
            helpText = __( 'Defines navigation links.' );
            break;
    }

    return (
        <>
            <InspectorControls>
                <PanelBody>
                    <SelectControl
                        __next40pxDefaultSize
                        __nextHasNoMarginBottom
                        label={__( 'HTML Tag' )}
                        help={helpText}
                        onChange={(newValue) => setAttributes({ htmlTag: newValue })}
                        value={htmlTag}
                        options={[
                            {
                                disabled: true,
                                label: __( 'Please select an HTML tag' ),
                                value: ''
                            },
                            {
                                label: '<div>',
                                value: 'div'
                            },
                            {
                                label: '<header>',
                                value: 'header'
                            },
                            {
                                label: '<footer>',
                                value: 'footer'
                            },
                            {
                                label: '<article>',
                                value: 'article'
                            },
                            {
                                label: '<section>',
                                value: 'section'
                            },
                            {
                                label: '<aside>',
                                value: 'aside'
                            },
                            {
                                label: '<nav>',
                                value: 'nav'
                            }
                        ]}
                    />
                </PanelBody>
                <PanelBody title={__('Wrapper Variants')} initialOpen={false}>
                    <ToggleGroupControl
                        __next40pxDefaultSize
                        __nextHasNoMarginBottom
                        label={__('')}
                        value={moduleVariant}
                        onChange={(newVariant) => setAttributes({ moduleVariant: newVariant })}
                        isBlock
                    >
                        <ToggleGroupControlOption
                            value=""
                            label={__('Default')}
                        />
                        <ToggleGroupControlOption
                            value="flex-wrapper"
                            label={__('Flexible')}
                        />
                        <ToggleGroupControlOption
                            value="wrapper-v1"
                            label={__('Variant 1')}
                        />
                        <ToggleGroupControlOption
                            value="wrapper-v2"
                            label={__('Variant 2')}
                        />
                        <ToggleGroupControlOption
                            value="wrapper-v3"
                            label={__('Variant 3')}
                        />
                    </ToggleGroupControl>
                </PanelBody>
				<PanelBody title={__('Height Wrapper Variants')} initialOpen={false}>
					<ToggleGroupControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={__('')}
						value={heightVariant}
						onChange={(newVariant) => setAttributes({ heightVariant: newVariant })}
						isBlock
					>
						<ToggleGroupControlOption
							value=""
							label={__('Default')}
						/>
						<ToggleGroupControlOption
							value="--h-small"
							label={__('Small')}
						/>
						<ToggleGroupControlOption
							value="--h-medium"
							label={__('Medium')}
						/>
						<ToggleGroupControlOption
							value="--h-large"
							label={__('Large')}
						/>
						<ToggleGroupControlOption
							value="--h-full"
							label={__('Full')}
						/>
					</ToggleGroupControl>
				</PanelBody>
            </InspectorControls>
        </>
    );
}
