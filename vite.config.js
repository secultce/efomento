import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { viteStaticCopy } from 'vite-plugin-static-copy'
import path from 'path'

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');

    return {
        plugins: [
            laravel({
                input: 'resources/js/app.js',
                refresh: true,
            }),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
            viteStaticCopy({
                targets: [
                    { src: 'node_modules/tinymce/*', dest: 'tinymce' }
                ]
            })
        ],
        resolve: {
            alias: {
                '/tinymce': path.resolve(__dirname, 'node_modules/tinymce'),
            }
        },
        server: {
            host: '0.0.0.0',
            port: 5173,
            strictPort: true,

            origin: env.VITE_ORIGIN || 'http://localhost:5173',

            hmr: {
                host: env.VITE_HMR_HOST || 'localhost',
                protocol: 'ws',
                clientPort: 5173,
            },

            cors: {
                origin: [
                    'http://localhost:8080',
                    'http://127.0.0.1:8080',
                    env.APP_URL,
                ].filter(Boolean),
                credentials: true,
            },
        },
    };
});
