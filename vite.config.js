import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                // Display face: condensed grotesque, used only for headings and figures.
                bunny('Darker Grotesque', {
                    weights: [400, 500, 600, 700, 800, 900],
                    alias: 'fd-display',
                }),
                // Text face: neutral grotesque for running copy and UI.
                bunny('Instrument Sans', {
                    weights: [400, 500, 600, 700],
                    alias: 'fd-sans',
                }),
                // Data face: monospace, used for every amount, date and account number.
                bunny('DM Mono', {
                    weights: [400, 500],
                    alias: 'fd-mono',
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
