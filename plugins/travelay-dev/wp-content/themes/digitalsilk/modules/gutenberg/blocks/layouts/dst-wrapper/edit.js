import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import {
    useBlockProps,
    useInnerBlocksProps,
    InnerBlocks,
    InspectorControls,
} from '@wordpress/block-editor';
import {
    PanelBody,
} from '@wordpress/components';
import {
    DstBackgroundColorPicker,
    DstBackgroundImagePicker,
    DstBackgroundImageRender,
    DstDecorationsPicker,
    DstDecorationsRender,
    ClientLockControl,
} from '../../../react-components';
import { useDebouncedAttributeSync } from './utilities'
import { WrapperInspectorControls } from './inspector';
import classNames from 'classnames';

export const BlockEdit = ( props ) => {
    const { attributes, setAttributes, wrapperProps } = props;
    const {
		decorations: savedDecorations,
		backgroundColor,
		backgroundImage: savedBackgroundImage,
        htmlTag: HtmlTag,
        moduleVariant,
		heightVariant
	} = attributes;

    const [ decorations, setDecorations ] = useState( savedDecorations || [] );
	const [ backgroundImage, setBackgroundImage ] = useState( savedBackgroundImage || {} );
	const isUpdatingDecorations = useDebouncedAttributeSync( decorations, setAttributes, 'decorations' );
	const isUpdatingBackgroundImage = useDebouncedAttributeSync( backgroundImage, setAttributes, 'backgroundImage' );

    // Create block class names.
    const blockClass = classNames(
        'dst-wrapper',
        moduleVariant,
		heightVariant,
		{
            'is-updating': isUpdatingDecorations || isUpdatingBackgroundImage,
        }
    );

    // Create block props.
    const blockProps = useBlockProps(
        {
            ...wrapperProps,
            className: classNames(
                wrapperProps?.className,
                blockClass,
            ),
        }
    );

	// Set block styles.
	blockProps.style = {
		...blockProps.style,
		background: backgroundColor || undefined,
	};

    const innerBlocksProps = useInnerBlocksProps(
        { className: 'dst-wrapper__inner' },
        {
            renderAppender: () => (
                <div className="ds-wrapper-appender">
                    <InnerBlocks.DefaultBlockAppender />
                </div>
            ),
        }
    );

    return (
        <>
            <ClientLockControl>
                <WrapperInspectorControls blockProps={ props } />
            </ClientLockControl>
            <InspectorControls group='styles'>
                <PanelBody>
					<DstBackgroundColorPicker
						label={ __( 'Background Color' ) }
						value={ backgroundColor }
						onChange={ ( newColor ) => setAttributes({ backgroundColor: newColor }) }
					/>
					<DstBackgroundImagePicker
						label={ __( 'Background Media' ) }
						value={ backgroundImage }
						onChange={ setBackgroundImage }
					/>
	                <ClientLockControl>
		                <DstDecorationsPicker
			                value={ decorations }
			                onChange={ setDecorations }
			                label={ __( 'Decorative Elements' ) }
		                />
	                </ClientLockControl>
                </PanelBody>
            </InspectorControls>
            <HtmlTag { ...blockProps }>
                <DstDecorationsRender value={ decorations } />
				<DstBackgroundImageRender value={ backgroundImage } />
                <div { ...innerBlocksProps } />
            </HtmlTag>
        </>
    );
};
