import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/bs-rtl.css', 'resources/css/bs-ltr.css', 'resources/css/app.css', 'resources/js/app.js', 'resources/js/admin-qr.js', 'resources/js/room.js', 'resources/js/checkin.js'],
            refresh: true,
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
