import { defineConfig, loadEnv } from 'vite';
import path from 'path';
import { globSync } from 'glob';
import VitePluginSvgSpritemap from '@spiriit/vite-plugin-svg-spritemap';
import fs from 'fs';
import { liveReload } from 'vite-plugin-live-reload';

// Function to get all .scss and .js files from the root of their respective directories
function getEntries() {
	const entries = {};

	try {
		// Get all .scss files
		const scssFiles = globSync('assets/_src/sass/**/*.scss').filter((file) => !path.basename(file).startsWith('_'));
		scssFiles.forEach((file) => {
			const name = path.relative('assets/_src/sass', file).replace(/\.scss$/, '');
			entries[`css/${ name }`] = file;
		});

		// Get all .js files
		const jsFiles = globSync('assets/_src/js/*.js');
		jsFiles.forEach((file) => {
			const name = path.basename(file, '.js');
			entries[`js/${ name }`] = file;
		});

		// Log imported files
		// console.log('Resolved Paths:', scssFiles, jsFiles);

	} catch (error) {
		// eslint-disable-next-line no-console
		console.error('Error while fetching entries:', error);
	}

	return entries;
}

export default defineConfig(({ mode }) => {
	Object.assign(process.env, loadEnv(mode, process.cwd()));
	const localPort = process.env.VITE_PORT || 3000;
	const devEnv = process.env.NODE_ENV || 'development';
	const theme = process.env.VITE_THEME_PATH || '/wp-content/themes/digitalsilk';
	if (devEnv === 'development') {
		fs.closeSync(fs.openSync('.ds-dev-mode', 'w'));
	} else if (fs.existsSync('.ds-dev-mode')) {
		fs.unlinkSync('.ds-dev-mode');
	}

	return {
		root: path.resolve(__dirname, 'assets/_src'),
		base: devEnv === 'development' ? `${ theme }/assets/_src/` : `${ theme }/assets/_dist/`,
		resolve: {
			alias: {
				'@': path.resolve(__dirname, 'assets/_src')
			}
		},
		build: {
			outDir: path.resolve(__dirname, 'assets/_dist'),
			emptyOutDir: true,
			manifest: true,
			rollupOptions: {
				input: getEntries(),
				output: {
					entryFileNames: '[name]-[hash].js',
					assetFileNames({ name }) {
						if (/\.(gif|jpe?g|png|svg)$/.test(name ?? '')) {
							return 'images/[name][extname]';
						}

						if (/\.css$/.test(name ?? '')) {
							return '[name]-[hash][extname]';
						}

						if (/.(png|woff|woff2|eot|ttf)/.test(name ?? '')) {
							return 'fonts/[name]-[hash][extname]';
						}
						// default value
						// ref: https://rollupjs.org/guide/en/#outputassetfilenames
						return 'assets/[name]-[hash][extname]';
					}
				}
			}
		},
		server: {
			// required to load scripts from custom host
			cors: true,

			// we need a strict port to match on PHP side
			// change freely, but update in your functions.php to match the same port
			strictPort: true,
			open: process.env.VITE_SITE_URL,
			port: localPort,

			// serve over http
			https: false,

			// serve over httpS
			// to generate localhost certificate follow the link:
			// https://github.com/FiloSottile/mkcert - Windows, MacOS and Linux supported - Browsers Chrome, Chromium and Firefox (FF MacOS and Linux only)
			// installation example on Windows 10:
			// > choco install mkcert (this will install mkcert)
			// > mkcert -install (global one time install)
			// > mkcert localhost (in project folder files localhost-key.pem & localhost.pem will be created)
			// uncomment below to enable https
			// https: {
			//  key: fs.readFileSync('localhost-key.pem'),
			//  cert: fs.readFileSync('localhost.pem'),
			// },

			hmr: {
				port: localPort,
				host: 'localhost',
				protocol: 'ws'
			}
		},
		css: {
			devSourcemap: true,
			preprocessorOptions: {
				// scss: {
				//     api: 'modern-compiler',
				// },
			}
		},
		plugins: [// Live reload php
			liveReload(`${ __dirname }/**/*.php`, {
				log: true
			}),

			// SVG sprite generation
			VitePluginSvgSpritemap('assets/_src/images/svg-icons/*.svg', {
				prefix: '',
				route: '__defspritemap',
				injectSVGOnDev: true,
				output: {
					filename: '[name][extname].php',
					use: false,
					view: true
				}
			}),

			// SVG sprite generation for svg-icons-wc
			VitePluginSvgSpritemap('assets/_src/images/svg-icons-wc/*.svg', {
				prefix: '',
				route: '__wcspritemap',
				injectSVGOnDev: true,
				output: {
					filename: '[name]-wc[extname].php',
					use: false,
					view: true
				}
			})]
	};
});
