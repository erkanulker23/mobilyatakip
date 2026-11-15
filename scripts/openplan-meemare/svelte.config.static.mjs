import adapter from '@sveltejs/adapter-static';

/** Meemare: statik çıktı → public/openplan */
const config = {
	kit: {
		adapter: adapter({
			pages: '../../public/openplan',
			assets: '../../public/openplan',
			fallback: 'index.html',
			strict: false,
		}),
		paths: {
			base: '/openplan',
		},
		prerender: {
			// Meemare embed: yalnızca ana plan editörü; render-lab/API statik taramada değil
			entries: ['/'],
			handleMissingId: 'warn',
			handleHttpError: ({ status }) => {
				if (status === 404) return;
			},
			handleUnseenRoutes: 'ignore',
		},
	},
};

export default config;