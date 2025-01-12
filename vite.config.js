import prism from 'vite-plugin-prismjs';

/** @type {import('vite').UserConfig} */
export default {
    plugins: [
        prism({
            languages: ["php", "bash", "html", "json5", "diff", "sql", "javascript"],
            plugins: [],
            theme: null,
            css: false
        }),
    ],
    assetsInclude: ['resources/images/**.*'],
    publicDir: false,
    build: {
        assetsInlineLimit: false,
        outDir: "public/assets",
        rollupOptions: {
            input: ["resources/site.js", "resources/site.css"],
            output: {
                assetFileNames: "[name][extname]",
                entryFileNames: "[name].js",
                noConflict: false,
            },
        },
    },
}
