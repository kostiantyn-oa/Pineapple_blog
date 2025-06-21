// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
    compatibilityDate: '2025-05-15',
    devtools: { enabled: true },
    css: ['~/assets/css/tailwind.css'],
    modules: ['@nuxt/ui'],
    vite: {
        server: {
            proxy: {
                '/api': {
                    target: 'http://localhost',
                    changeOrigin: true,
                    secure: false,
                },
            },
        },
    },
})
