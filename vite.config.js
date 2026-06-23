import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css', 
                'resources/js/app.jsx',
                'resources/js/merchant-app.jsx',
                'resources/js/sales-app.jsx',
                'resources/js/rolesApp.jsx',
                'resources/js/usersApp.jsx'
            ],
            refresh: true,
        }),
        react(),
    ],
});
