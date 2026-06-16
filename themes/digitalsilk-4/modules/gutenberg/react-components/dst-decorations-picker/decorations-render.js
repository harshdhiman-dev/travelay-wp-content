import {getDecorationStyles} from './utilities';

/**
 * Renders decorative background images or custom elements with responsive CSS variable styles.
 *
 * @param {Object} props
 * @param {Array}  props.value - Array of decoration objects.
 * @return {JSX.Element|null} Rendered decorations or null if no valid data.
 */
export const DstDecorationsRender = ({value = []}) => {
	if (!Array.isArray(value) || value.length === 0) {
		return null;
	}

	return (
		<div className="c-decoration">
			{value.map((decoration, index) => {
				const styles = getDecorationStyles(decoration);

				if (decoration.type === 'custom' && decoration.className) {
					return (
						<div key={index} className="c-decoration__item" style={styles}>
							<div className={decoration.className}/>
						</div>
					);
				}

				const imageUrl = decoration?.media?.url;
				const altText = decoration?.media?.alt || '';

				if (!imageUrl) return null;

				return (
					<div key={index} className="c-decoration__item" style={styles}>
						<img src={imageUrl} alt={altText}/>
					</div>
				);
			})}
		</div>
	);
};
