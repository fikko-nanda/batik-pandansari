import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/teknisi.js'],
            refresh: true,
        }),
    ],
    // Gunakan loopback IPv4 agar public/hot tidak berisi http://[::1]:5173
    // (Chrome menolak host IPv6 ber-bracket sebagai source CSP).
    // server: {
    //     host: '127.0.0.1',
    //     port: 5173,
    //     strictPort: true,
    //     hmr: { host: '127.0.0.1' },
    // },
});

