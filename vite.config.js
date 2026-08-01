import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            // Fontlar derleme sırasında indirilip kendi sunucumuzdan servis edilir.
            // Çalışma anında hiçbir dış isteğe çıkılmaz.
            fonts: [
                bunny('Fraunces', {
                    weights: [400, 500, 600, 700],
                    styles: ['normal', 'italic'],
                }),
                bunny('Manrope', {
                    weights: [400, 500, 600, 700, 800],
                }),
            ],
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
