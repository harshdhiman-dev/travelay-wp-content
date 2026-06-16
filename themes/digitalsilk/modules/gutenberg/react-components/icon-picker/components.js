/* eslint-disable jsx-a11y/click-events-have-key-events */
/* eslint-disable jsx-a11y/no-noninteractive-element-interactions */
import { memo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
    Icon,
    Tooltip,
    Icon as IconRender,
    Spinner,
    TabPanel,
    Panel,
    PanelBody,
} from '@wordpress/components';
// eslint-disable-next-line import/no-extraneous-dependencies
import { close } from '@wordpress/icons';

/**
 * Normalizes a string by replacing hyphens/underscores with spaces
 * and capitalizing each word.
 *
 * @param {string} name - The input string (e.g., 'icon-name').
 * @return {string} The normalized name (e.g., 'Icon Name').
 */
const normalizeName = (name) => {
    return name
        .replace(/[-_]/g, ' ') // Replace "-" and "_" with spaces
        .replace(/\b\w/g, (char) => char.toUpperCase()); // Capitalize each word
};

/**
 * Renders the icon pill in the popover header.
 * Shows a pill with either "Remove selected icon" or "Select an icon" text,
 * and a close (×) icon to remove the current icon.
 *
 * @param {Object}   props
 * @param {string}   props.selectedIcon - The currently selected icon slug or ID.
 * @param {boolean}  props.showInline   - Whether the picker is shown inline (popover) or in modal.
 * @param {Function} props.onRemove     - Callback to remove the selected icon.
 * @return {JSX.Element|null} Icon pill element or null if `showInline` is false.
 */
export const RenderIconPill = memo(
    (
        {
            selectedIcon,
            showInline,
            onRemove
        }
    ) => {
        if ( ! showInline ) {
            return null;
        }

        return (
            <div className="iconPopover__header">
                <button
                    className={`iconPopover__pill ${!selectedIcon ? '-disabled' : ''}`}
                    onClick={onRemove}
                >
                    <p className="iconPopover__title">
                        {selectedIcon ? __('Remove selected icon') : __('Select an icon')}
                    </p>
                    <Icon icon={close} />
                </button>
            </div>
        );
    }
);

/**
 * Renders the icon controls UI inside the picker.
 * Displays a tabbed interface to switch between theme icons (fetched from API)
 * and uploaded icons (from icon library object), allowing the user to select or remove icons.
 *
 * @param {Object}        props
 * @param {Array}         props.availableIcons - Theme icons fetched from the REST API.
 * @param {Object}        props.iconsList      - Uploaded icons grouped by category.
 * @param {Array<string>} props.iconSet        - Array of enabled icon sets/categories.
 * @param {string|number} props.selectedIcon   - The currently selected icon.
 * @param {boolean}       props.isNumber       - Whether the selected icon is a numeric ID.
 * @param {Function}      props.onSelect       - Callback when selecting a new icon.
 * @param {Function}      props.onRemove       - Callback to remove the selected icon.
 * @return {JSX.Element} The icon control interface with tabs and icon lists.
 */
export const RenderIconControls = memo(
    (
        {
            availableIcons,
            iconsList,
            iconSet,
            selectedIcon,
            isNumber,
            onSelect,
            onRemove
        }
    ) => {
        const safeIconsList = iconsList || {};
        const validCategories = Object.keys(safeIconsList).filter(category => iconSet.includes(category));

        const hasThemeIcons = availableIcons && availableIcons.length > 0;
        const hasUploadedIcons = validCategories.length > 0;

        if ( ! hasThemeIcons && ! hasUploadedIcons ) {
            return <p className="dsIconPicker__no-icons">{__('There are no available icons.')}</p>;
        }

        // Determines which tab to show by default based on selected icon type.
        let initialTabName = hasUploadedIcons ? 'icon-library' : 'theme-icons';

        if ( selectedIcon ) {
            if ( isNumber && hasUploadedIcons ) {
                initialTabName = 'icon-library';
            } else if ( ! isNumber && hasThemeIcons ) {
                initialTabName = 'theme-icons';
            }
        }

        // Renders theme icons fetched via REST API.
        const renderThemeIconList = () => (
            <Panel>
                <PanelBody>
                    <ul className="iconList">
                        {
                            availableIcons.map(
                                (currentIcon) => (
                                    <li
                                        key={currentIcon.slug}
                                        className={`iconList__item ${currentIcon.slug === selectedIcon ? '-selected' : ''}`}
                                    >
                                        <Tooltip text={currentIcon.name}>
                                            <button
                                                onClick={
                                                    () => {
                                                        if (currentIcon.slug !== selectedIcon) {
                                                            onSelect(currentIcon.slug);
                                                        } else {
                                                            onRemove();
                                                        }
                                                    }
                                                }
                                            >
                                                <svg
                                                    className={`icon icon-${currentIcon.slug}`}
                                                    aria-hidden="true"
                                                    width={24}
                                                    height={24}
                                                    role="img"
                                                    style={{pointerEvents: 'all'}}
                                                >
                                                    <use href={`#${currentIcon.slug}`} />
                                                </svg>
                                            </button>
                                        </Tooltip>
                                    </li>
                                )
                            )
                        }
                    </ul>
                </PanelBody>
            </Panel>
        );

        // Renders uploaded icons grouped by category (from `window.iconLibraryDataStore`).
        const renderUploadedIconList = () => (
            <Panel>
                {
                    validCategories.map(
                        (category) => (
                            <PanelBody title={normalizeName(category)} key={category}>
                                <ul className="iconList">
                                    {
                                        iconsList[category].map(
                                            (currentIcon) => (
                                                <li
                                                    key={currentIcon.id}
                                                    className={`iconList__item ${currentIcon.id.toString() === selectedIcon ? '-selected' : ''}`}
                                                >
                                                    <Tooltip text={normalizeName(currentIcon.title)}>
                                                        <button
                                                            onClick={() => {
                                                                if (currentIcon.id.toString() !== selectedIcon) {
                                                                    onSelect(currentIcon.id.toString());
                                                                } else {
                                                                    onRemove();
                                                                }
                                                            }}
                                                        >
                                                            <img
                                                                src={currentIcon.url}
                                                                alt={currentIcon.title}
                                                                width={24}
                                                                height={24}
                                                                className="dsIconPicker__image"
                                                                aria-hidden="true"
                                                            />
                                                        </button>
                                                    </Tooltip>
                                                </li>
                                            )
                                        )
                                    }
                                </ul>
                            </PanelBody>
                        )
                    )
                }
            </Panel>
        );

        // Wraps both uploaded + theme icon lists inside a tab switcher.
        return (
            <TabPanel
                initialTabName={initialTabName}
                tabs={
                    [
                        ...(hasUploadedIcons ? [{ name: 'icon-library', title: __('Icon Library') }] : []),
                        ...(hasThemeIcons ? [{ name: 'theme-icons', title: __('Theme Icons') }] : []),
                    ]
                }
            >
                {
                    (tab) => (
                        <>
                            {tab.name === 'icon-library' && renderUploadedIconList()}
                            {tab.name === 'theme-icons' && renderThemeIconList()}
                        </>
                    )
                }
            </TabPanel>
        );
    }
);

/**
 * Displays a selected uploaded icon (by media attachment).
 * Handles various states like loading, missing media, raw SVGs, or fallback thumbnails.
 *
 * @param {Object}        props
 * @param {Object|null}   props.attachment - The media object from REST API.
 * @param {number|string} props.size       - Desired display size in px.
 * @param {boolean}       props.loading    - Whether the attachment is still loading.
 * @param {Function}      props.onClick    - Callback triggered when the icon is clicked.
 * @param {string}        props.displayAs  - How to display SVG: 'inline' (default) or 'img'.
 * @return {JSX.Element} Rendered icon element (spinner, SVG, image, or error).
 */
const UploadedIconDisplay = memo(
    (
        {
            attachment,
            size,
            loading,
            onClick,
            displayAs
        }
    ) => {
        if (loading) {
            return (
                <Spinner
                    style={{
                        height: size ? `${size}px` : '28px',
                        width: size ? `${size}px` : '28px',
                    }}
                />
            );
        }

		if(attachment  && attachment?.media_type ==="image" && attachment?.source_url){
			return (
				<img
					src={attachment.source_url}
					alt={attachment.alt_text || 'Uploaded Icon'}
					className={`icon icon-${attachment.id} -library`}
					width={size || 28}
					height={size || 28}
					onClick={onClick}
				/>
			);
		}

        if ( ! attachment || ! attachment.media_details?.sizes?.thumbnail ) {
            return (
                <button className="iconList__error" onClick={onClick}>
                    <IconRender
                        icon={
                            <svg
                                height="32"
                                width="32"
                                style={{ overflow: 'visible', enableBackground: 'new 0 0 32 32', pointerEvents: 'all' }}
                                viewBox="0 0 32 32"
                                xmlns="http://www.w3.org/2000/svg"
                                xmlnsXlink="http://www.w3.org/1999/xlink"
                                xmlSpace="preserve"
                            >
                                <g>
                                    <circle cx="16" cy="16" r="16" fill="#D72828" />
                                    <path d="M14.5,25h3v-3h-3V25z M14.5,6v13h3V6H14.5z" fill="#E6E6E6" />
                                </g>
                            </svg>
                        }
                    />
                    <p>{__('Icon not found')}</p>
                </button>
            );
        }

        // For uploaded icons, render based on displayAs preference
        if ( displayAs && displayAs === 'img' ) {
            return (
                <img
                    src={attachment.media_details.sizes.full.source_url}
                    alt={attachment.alt_text || 'Uploaded Icon'}
                    className={`icon icon-${attachment.id} -library`}
                    width={size || 28}
                    height={size || 28}
                    onClick={onClick}
                />
            );
        }

        if ( attachment.svgRaw && attachment.svgRaw.markup && attachment.svgRaw.viewBox ) {
            const { markup, viewBox, attributes = {} } = attachment.svgRaw;

            return (
                <svg
                    dangerouslySetInnerHTML={{ __html: markup }}
                    viewBox={viewBox}
                    width={size || 28}
                    height={size || 28}
                    className={`icon icon-${attachment.id} -library`}
                    onClick={onClick}
                    style={{pointerEvents: 'all'}}
                    {...attributes}
                />
            );
        }

        // Default inline SVG rendering
        return (
            <img
                src={attachment.media_details.sizes.thumbnail.source_url}
                alt={attachment.alt_text || 'Uploaded Icon'}
                className={`icon icon-${attachment.id} -library`}
                width={size || 28}
                height={size || 28}
                onClick={onClick}
            />
        );
    }
);

/**
 * Renders the currently selected icon (either uploaded or theme-based).
 * Delegates uploaded rendering to UploadedIconDisplay.
 *
 * @param {Object}        props
 * @param {string|number} props.iconData   - The icon slug or attachment ID.
 * @param {boolean}       props.isNumber   - Whether the icon is numeric (uploaded).
 * @param {Object|null}   props.attachment - Attachment object if icon is uploaded.
 * @param {number|string} props.size       - Icon size in pixels.
 * @param {boolean}       props.loading    - Whether the attachment is loading.
 * @param {Function}      props.onClick    - Callback for when icon is clicked (toggles picker).
 * @param {string}        props.displayAs  - How to display SVG: 'inline' (default) or 'img'.
 * @return {JSX.Element} Rendered icon (SVG or uploaded image).
 */
export const SelectedIconDisplay = memo(
    (
        {
            iconData,
            isNumber,
            attachment,
            size,
            loading,
            onClick,
            displayAs
        }
    ) => {
        if (isNumber) {
            return (
                <UploadedIconDisplay
                    attachment={attachment}
                    size={size}
                    loading={loading}
                    onClick={onClick}
                    displayAs={displayAs}
                />
            );
        }

        // Default inline SVG rendering
        return (
            <svg
                className={`icon icon-${iconData}`}
                aria-hidden="true"
                width={size || 28}
                height={size || 28}
                role="img"
                onClick={onClick}
                style={{pointerEvents: 'all'}}
            >
                <use href={`#${iconData}`} />
            </svg>
        );
    }
);
