import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },

        host: '0.0.0.0', // すべての接続を許可
        port: 5173,      // 5173で起動することを強制する
        strictPort: true, // 5173が使われていたらエラーにする（勝手に5174にしない）
        hmr: {
            host: 'localhost', // ブラウザからはlocalhostとしてアクセスさせる
        },
    },
});
