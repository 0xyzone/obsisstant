import fs from 'node:fs';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

const keyPath = '/Applications/MAMP/conf/apache/ssl/ob.key';
const certPath = '/Applications/MAMP/conf/apache/ssl/ob.crt';
const hasMampSsl = fs.existsSync(keyPath) && fs.existsSync(certPath);

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.js', 'reources/css/app.css'],
            refresh: [
                'resources/views/**',
                'app/**/*.php',
                'routes/**',
                'public/css/**',
                'docs/knowledge-base/**',
            ],
        }),
    ],
    server: {
        host: 'ob',
        port: 5173,
        strictPort: true,
        cors: true,
        https: hasMampSsl
            ? {
                  key: fs.readFileSync(keyPath),
                  cert: fs.readFileSync(certPath),
              }
            : false,
        hmr: {
            host: 'ob',
            protocol: hasMampSsl ? 'wss' : 'ws',
        },
        watch: {
            ignored: ['**/storage/framework/views/**', '**/obs-agent/**'],
        },
    },
});
