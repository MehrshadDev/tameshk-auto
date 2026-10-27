import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import { fileURLToPath } from 'node:url';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/canvas/main.tsx',
            ],
            refresh: true
        }),
        tailwindcss(),
        react(),
    ],

    resolve: {
        alias: {
            '@canvas': fileURLToPath(new URL('./resources/js/canvas', import.meta.url)),
        },
    },

    build: {
        emptyOutDir: false,
        rollupOptions: {
            output: {
                assetFileNames: (assetInfo) => {
                    const fileName = assetInfo.names?.[0] || assetInfo.name || '';

                    if (/\.(woff|woff2|eot|ttf|otf)$/i.test(fileName)) {
                        return 'assets/[name][extname]';
                    }

                    return 'assets/[name]-[hash][extname]';
                },
            },
        },
        rolldownOptions: {
            output: {
                codeSplitting: {
                    groups: [
                        { name: 'react-vendor', test: /node_modules[\\/](react|react-dom|scheduler)[\\/]/ },
                        { name: 'xyflow-vendor', test: /node_modules[\\/](@xyflow|zustand)[\\/]/ },
                    ],
                },
            },
        },
    },

    server: {
        host: '0.0.0.0',
        port: 5174,
        strictPort: true,
        hmr: {
            host: 'localhost',
            clientPort: 5173,
            protocol: 'wss',
        },
        watch: {
            ignored: [
                '**/storage/**',
                '**/.pnpm-store/**',
                '**/node_modules/**',
                '**/bootstrap/cache/**',
                '**/public/build/**',
            ],
        },
    },
});
