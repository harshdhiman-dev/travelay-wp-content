**DS Vite Starter** - Fast & Simple Toolkit for WordPress Theme Development using Vite  
___

## Features

| Feature       | Description                                                                                   |
|---------------|-----------------------------------------------------------------------------------------------|
| CSS           | [SCSS](http://sass-lang.com/) for writing maintainable styles.                                |
| JavaScript    | Bundled with [Vite](https://v4.vitejs.dev/guide/), optimized for fast builds and HMR.          |
| Live Reload   | Integrated [Vite HMR](https://v4.vitejs.dev/guide/features.html#hot-module-replacement) for live updates during development. |
| Image Handling| Images are copied directly to the build folder, optimizing loading in production.              |
| SVG Sprites   | Automatically generates a sprite with [@spiriit/vite-plugin-svg-spritemap](https://github.com/SpiriitLabs/vite-plugin-svg-spritemap), improving performance and maintainability. |

## Getting started

### Recommendations

Ensure you have the following tools installed:

- [Node.js](https://nodejs.org/) (Recommended: Node.js >=22.7.0)
- Package Manager: [npm](https://www.npmjs.com/) (recommended) or [yarn](https://yarnpkg.com/en/) (optional) 

### 1. Setup .env

Copy `.env.default` to `.env` in the theme root folder

`cp .env.default .env`

Edit .env file to set your local variables.

- `LOCAL_PORT` -  The default is 3000. Change this if 3000 is used by other project, or you're running multiple projects simultaneously.
- `VITE_SITE_URL` - Specify your local site URL here. This will be opened automatically when you run `npm run dev`
- `VITE_THEME_PATH` - path to your theme, default is `/wp-content/themes/digitalsilk`, change it only if you changed the theme folder name

### 2. Install dependencies:

Install the required packages with:

```bash
npm install          
```

### 3. Start development

```bash
npm run dev          // Start development mode with live reloading
npm run build        // Compile assets for production
```

### 4. Husky Setup

Run the following command to setup Husky hooks.
```bash
npm run prepare // Run this command only once after project setup.
```

##### Local Server explained

Running `npm run dev` launches a local server and handles asset compilation, including live reloading. The `.ds-dev-mode` file is temporarily created in the theme directory to indicate that assets are being loaded from the local server (default: http://localhost:3000).

Once `npm run dev` is stopped, the `.ds-dev-mode` file is deleted, and assets will load from the `assets/_dist` directory as usual. Ensure `.ds-dev-mode` is not present in production or staging environments by adding it to `.gitignore`.

##### !IMPORTANT!

Keep `.ds-dev-mode` file in .gitignore and NEVER push it to production or staging server

### CSS

- [Include Media](https://eduardoboucas.github.io/include-media/) for Media Queries

### Images

Images placed in `_src/images/` will only be copied to the build folder if they are referenced in your JS/CSS files.

- Use `@` as an alias for the image path in your CSS:
  ```css
  background-image: url(@/images/some-image.png);

- SVGs used in CSS, such as:

	```css
	background-image: url(@/images/some-icon.svg);

will be encoded as base64 by default, reducing the need for additional network requests.

### SVG Sprite

All SVG files in the `_src/images/svg-icons/` folder will automatically generate a sprite. 
The sprite is injected inline at the top of each page.

To use an icon from the sprite, use the following PHP function:

```php
<?php echo get_svg(array('icon' => 'icon_file_name', 'class' => 'icon_name__icon')); ?>
``` 

### JS

All JS is bundled through Vite. Place your custom scripts to `_src/js/` and import them in `_src/js/index.js`.

For blog-specific scripts, use `dst-blog.js`. 

Avoid using external libraries that have unnecessary dependencies to keep builds lightweight.

#### Lazy Load (vanilla-lazyload)

[Lazy Load](https://github.com/verlok/vanilla-lazyload)

In order to make your content be loaded by LazyLoad, you must use some data-attributes instead of the actual attributes.

```
<img 
  alt="A lazy image" 
  class="lazy"
  data-src="lazy.jpg" 
/>
```


### License

Created by DigitalSilk dev team
MIT License
