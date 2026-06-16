// Tiny heading component. Used as a replacement for c-heading component in Gutenberg editor.
import {useState, useEffect} from '@wordpress/element';
import {__} from '@wordpress/i18n';
import {
	RichText,
} from '@wordpress/block-editor';
import { HeadingToolbar } from './toolbar';
import { HeadingInspectorControls } from './inspector';
import { HeadingInspectorStyles } from './styles';
import classNames from 'classnames';

export const DstTinyHeading = (
    {
        value = {},
        onChange,
        showToolbars = true,
        showInspectorControls = true,
        readOnly = false,
    }
) => {
    // Extract the necessary attributes from the value object.
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
		showPretitle,
		showTitle,
		showSubtitle,
		showDescription,
        description,
		headingTheme,
	} = value || {};
	const { tag, tag_style } = title_styles || {};
    const Tag = tag || 'h2';

	// Set popover states.
	const [ backtitlePopoverVisible, setBacktitlePopoverVisible ] = useState(false);
	const [ headingPopoverVisible, setHeadingPopoverVisible ] = useState(false);
	const [ alignmentPopoverVisible, setAlignmentPopoverVisible ] = useState(false);

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
				onChange({
                    ...value,
                    description: ''
                });
			}
		},
		// eslint-disable-next-line react-hooks/exhaustive-deps
		[]
	);

    // Heading wrapper classes.
    const headingClasses = classNames(
        'c-heading',
        {
            [`text-${alignment}`]: alignment,
            [`text-${alignmentMobile}-mobile`]: alignmentMobile,
            'is-style-colors-inverted': headingTheme === 'inverted',
            [`-${tag_style}`]: tag_style,
        }
    );

    return (
        <>
            { /* All of our toolbar controls */ }
            {
                showToolbars && (
                    <HeadingToolbar
                        value = {value}
                        onChange = {onChange}
                        backtitlePopoverVisible={backtitlePopoverVisible}
                        setBacktitlePopoverVisible={setBacktitlePopoverVisible}
                        headingPopoverVisible={headingPopoverVisible}
                        setHeadingPopoverVisible={setHeadingPopoverVisible}
                        alignmentPopoverVisible={alignmentPopoverVisible}
                        setAlignmentPopoverVisible={setAlignmentPopoverVisible}
                    />
                )
            }
            { /* Inspector Controls ( shown in the sidebar ) */ }
            {
                showInspectorControls && (
                    <>
                        <HeadingInspectorControls
                            value = {value}
                            onChange = {onChange}
                        />
                        { /* Inspector Styles ( shown in the sidebar ) */ }
                        <HeadingInspectorStyles
                            value = {value}
                            onChange = {onChange}
                        />
                    </>
                )
            }
            
            <div className={headingClasses}>
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
                            {
                                readOnly ? (
                                    <span>
                                        {pretitle}
                                    </span>
                                ) : (
                                    <RichText
                                        value={pretitle}
                                        onChange={
                                            (newValue) => onChange(
                                                {
                                                    ...value,
                                                    pretitle: newValue
                                                }
                                            )
                                        }
                                        onClick={closePopovers}
                                        tagName='span'
                                        placeholder={__('Enter pre-title text here..')}
                                    />
                                )
                            }
                        </div>
                    )
                }
                {
                    showTitle && (
                        <>
                            {
                                readOnly ? (
                                    <>
                                        {
                                            title && (
                                                <Tag
                                                    className='c-heading__title'
                                                    style={title_styles?.color ? {color: title_styles.color} : {}}
                                                >
                                                    {title}
                                                </Tag>
                                            )
                                        }
                                    </>
                                ) : (
                                    <RichText
                                        value={title}
                                        onChange={
                                            (newValue) => onChange(
                                                {
                                                    ...value,
                                                    title: newValue
                                                }
                                            )
                                        }
                                        onClick={closePopovers}
                                        tagName={tag}
                                        className='c-heading__title'
                                        placeholder={__('Enter heading text here..')}
                                        style={title_styles?.color ? {color: title_styles.color} : {}}
                                    />
                                )
                            }
                        </>
                    )
                }
                {
                    showSubtitle && (
                        <div className='c-heading__sub'>
                            {
                                readOnly ? (
                                    <>
                                        {
                                            subtitle && (
                                                <span
                                                    style={subtitle_color ? {color: subtitle_color} : {}}
                                                >
                                                    {subtitle}
                                                </span>
                                            )
                                        }
                                    </>
                                ) : (
                                    <RichText
                                        value={subtitle}
                                        onChange={
                                            (newValue) => onChange(
                                                {
                                                    ...value,
                                                    subtitle: newValue
                                                }
                                            )
                                        }
                                        onClick={closePopovers}
                                        tagName='span'
                                        placeholder={__('Enter subtitle text here..')}
                                        style={subtitle_color ? {color: subtitle_color} : {}}
                                    />
                                )
                            }
                        </div>
                    )
                }
                {
                    showDescription && (
                        <div className='c-heading__description is-wysiwyg'>
                            {
                                readOnly ? (
                                    <>
                                        {
                                            description && (
                                                <p>{description}</p>
                                            )
                                        }
                                    </>
                                    
                                ) : (
                                    <RichText
                                        value={description}
                                        onChange={
                                            (newValue) => onChange(
                                                {
                                                    ...value,
                                                    description: newValue
                                                }
                                            )
                                        }
                                        onClick={closePopovers}
                                        tagName='p'
                                        placeholder={__('Enter description text here..')}
                                    />
                                )
                            }
                        </div>
                    )
                }
            </div>
        </>
    );
}