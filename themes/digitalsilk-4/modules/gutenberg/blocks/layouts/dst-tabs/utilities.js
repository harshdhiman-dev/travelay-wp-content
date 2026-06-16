export const RenderThemeIcon = (
    {
        icon,
        width = 24,
        height = 24
    }
) => {
	return (
		<>
			{
                icon && (
                    <svg className={`icon icon-${icon}`} aria-hidden="true" width={width} height={height} role="img">
                        <use href={`#${icon}`} />
                    </svg>
			    )
            }
		</>
	);
};