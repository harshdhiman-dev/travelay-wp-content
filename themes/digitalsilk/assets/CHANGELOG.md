# 0.1.1 - 2022-10-13

## FRONTEND

### Updates and improvements
- [LAYOUT]  Removed redundant paddings for Wrapper module
- [LAYOUT]  Replaced horizontal and vertical paddings with padding-block and padding-inline
- [LAYOUT]  Added max-width limitation for ultra-wide screens (>1920px)
- [LAYOUT]  Started replacing div with figure for image components
- [NAMING]  .c-btn-bar was renamed to .c-block__btn
- [A11Y]    Added a "Skip Link" to the header template 
- [DECOR]   scroll-down component now has more semantic structure and less hardcode in styles
- [DECOR]   for .c-image__src changed default value from object-fit: cover to contain  
- [FIX]     renamed hight to height everywhere     

### Module Banner
- [LAYOUT]  Simplified html structure a bit, removed unused classes 

### Module Slider
- [MAJOR]   Reworked Sliders Module (CSS and folders structure, including Circular Slider)
- [LAYOUT]  Added "Breaking Right Edge" Layout 
- Added default styles for data-slider-pagination="progressbar".
- Added "Edge Overflow" layout for cards slider

### Mobile
- [A11Y]    Updated Mobile Nav Button (Replaced div/span with more semantic structure)
- [NAV]     Mobile navigation dropdown: Now it's possible to have a possibility to expand a subnav accordion or click the page link itself
- [FONT]    Mobile headings now get the same size as it set in DSMP settings (2rem = 20px)  

### DSMP Admin
- Typography preview was updated (default/alternative colors in 2 columns)

## BACKEND
- Added custom classes to some modules and decorations (class-module-extra-fields.php)
