/**
 * Convert a focal point to a percentage string.
 *
 * @param {number} val
 * @return {string} Percentage string (e.g. "50%").
 */
const toPercent = (val) => `${Math.round(val * 100)}%`;

/**
 * Generates inline style object for a background media item.
 * Includes desktop styles always, mobile styles only if different.
 *
 * @param {Object} item
 * @return {Object} CSS variables as style object and classes.
 */
const getBackgroundMediaStyles = (item) => {
	const desktop = item?.desktop || {};
	const mobile = item?.mobile || {};

	const desktopStyles = {
		focal: `${toPercent(desktop.focal?.x ?? 0.5)} ${toPercent(desktop.focal?.y ?? 0.5)}`,
		fixed: desktop.fixed ? 'fixed' : 'scroll',
		size: desktop.size || 'auto',
		width: desktop.width || 'auto',
	};

	const mobileStyles = {
		focal: `${toPercent(mobile.focal?.x ?? 0.5)} ${toPercent(mobile.focal?.y ?? 0.5)}`,
		fixed: mobile.fixed ? 'fixed' : 'scroll',
		size: mobile.size || 'auto',
		width: mobile.width || 'auto',
	};

	const styles = {
		'--dst--bg-desktop-focal': desktopStyles.focal,
		'--dst--bg-desktop-fixed': desktopStyles.fixed,
		'--dst--bg-desktop-size': desktopStyles.size,
		'--dst--bg-desktop-width': desktopStyles.width,
	};

	// Only print mobile styles if different from desktop
	Object.entries(mobileStyles).forEach(([key, val]) => {
		if (val !== desktopStyles[key]) {
			styles[`--dst--bg-mobile-${key}`] = val;
		}
	});

	// Generate classes for the background
	const classes = [];
	if (desktop.fixed) {
		classes.push('is-fixed');
	}

	return {
		styles,
		classes,
	};
};

/**
 * Render a <picture> element for image-based backgrounds.
 *
 * @param {Object}  desktop
 * @param {Object}  mobile
 * @param {boolean} lazy
 * @param {string}  className
 * @return {JSX.Element} If no desktop media URL, returns null, otherwise renders picture.
 */
const renderPictureElement = (desktop, mobile, lazy, className) => {
	const sameMedia = desktop?.media?.id === mobile?.media?.id;
	if (!desktop?.media?.url) return null;

	if (sameMedia) {
		const sizes = Object.values(desktop.media?.sizes || {})
			.filter((size) => size?.url && size?.width)
			.sort((a, b) => b.width - a.width);

		return (
			<picture className={`${className || ''}`}>
				{sizes.map((size) => (
					<source
						key={size.url}
						media={`(min-width: ${size.width}px)`}
						srcSet={size.url}
					/>
				))}
				<img
					src={desktop.media.url}
					alt={desktop.media.alt || ''}
					loading={lazy ? 'lazy' : undefined}
					decoding={lazy ? 'async' : undefined}
					width={desktop.media.width || undefined}
					height={desktop.media.height || undefined}
					className={`c-bg__media`}
				/>
			</picture>
		);
	}

	return (
		<picture className={`${className || ''}`}>
			<source media="(min-width: 769px)" srcSet={desktop.media.url} />
			<source media="(max-width: 768px)" srcSet={mobile?.media?.url} />
			<img
				src={desktop.media.url}
				alt={desktop.media.alt || ''}
				loading={lazy ? 'lazy' : undefined}
				decoding={lazy ? 'async' : undefined}
				width={desktop.media.width || undefined}
				height={desktop.media.height || undefined}
				className={`c-bg__media`}
			/>
		</picture>
	);
};

/**
 * Render a <video> element.
 *
 * @param {Object}  media
 * @param {boolean} lazy
 * @param {string}  className
 * @return {JSX.Element|null} If no media URL, returns null, otherwise renders video.
 */
const renderVideoElement = (media, lazy, className) => {
	if (!media?.url) return null;

	return (
		<video
			autoPlay
			muted
			loop
			loading={lazy ? 'lazy' : undefined}
			decoding={lazy ? 'async' : undefined}
			className={`c-bg__media ${className || ''}`}
		>
			<source src={media.url} type={media.mime || 'video/mp4'} />
		</video>
	);
};

/**
 * Main render component for background media
 *
 * @param {Object} props
 * @param {Array}  props.value
 * @return {JSX.Element|null} If no value, returns null, otherwise renders background media.
 */
export const DstBackgroundImageRender = ({ value = [] }) => {
	if (!Array.isArray(value) || value.length === 0) return null;

	return (
		<div className="c-bg">
			{value.map((item, index) => {
				const desktop = item.desktop;
				const mobile = item.mobile;
				const bgAttributes = getBackgroundMediaStyles(item);
				const style = bgAttributes.styles;
				const additionalClasses = bgAttributes.classes?.length ? ` ${bgAttributes.classes.join(' ')}` : '';
				const lazy = !!item.lazy;
				const wrapperClass = `c-bg__item -item${index + 1}${additionalClasses}`;

				const desktopType = desktop?.media?.type;
				const mobileType = mobile?.media?.type;
				const sameMedia = desktop?.media?.id && desktop.media.id === mobile?.media?.id;

				return (
					<div className={wrapperClass} style={style} key={item.id || index}>
						{(() => {
							// Case 1, 2: Images
							if (desktopType === 'image' && mobileType === 'image') {
								return renderPictureElement(desktop, mobile, lazy);
							}

							// Case 3: Same video
							if (desktopType === 'video' && sameMedia) {
								return renderVideoElement(desktop.media, lazy);
							}

							// Case 4: Video desktop, image mobile
							if (desktopType === 'video' && mobileType === 'image') {
								return (
									<>
										{renderVideoElement(desktop.media, lazy, '-visible-desktop -hidden-mobile')}
										{renderPictureElement(mobile, mobile, lazy, '-visible-mobile -hidden-desktop')}
									</>
								);
							}

							// Case 5: Image desktop, video mobile
							if (desktopType === 'image' && mobileType === 'video') {
								return (
									<>
										{renderPictureElement(desktop, desktop, lazy, '-visible-desktop -hidden-mobile')}
										{renderVideoElement(mobile.media, lazy, '-visible-mobile -hidden-desktop')}
									</>
								);
							}

							return null;
						})()}
					</div>
				);
			})}
		</div>
	);
};
