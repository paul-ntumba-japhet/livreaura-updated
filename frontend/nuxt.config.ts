import tailwindcss from '@tailwindcss/vite'

function rewriteProxiedCookies(cookies: string[] | undefined): string[] | undefined {
  if (!cookies) {
    return cookies
  }

  return cookies.map(cookie => cookie
    .replace(/;\s*Secure/gi, '')
    .replace(/;\s*Domain=[^;]*/gi, ''))
}

const laravelOrigin = {
  target: 'http://127.0.0.1:8000',
  changeOrigin: true,
}

const laravelViteProxy = {
  ...laravelOrigin,
  configure(proxy: any) {
    proxy.on('proxyRes', (proxyRes: { headers: Record<string, unknown> }) => {
      const cookies = proxyRes.headers['set-cookie']
      if (Array.isArray(cookies)) {
        proxyRes.headers['set-cookie'] = rewriteProxiedCookies(cookies)
      }
    })
  },
}

export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: false },
  css: ['~/assets/css/main.css'],
  app: {
    head: {
      htmlAttrs: { lang: 'fr' },
      title: 'Livreaura',
    },
  },
  runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://127.0.0.1:8000',
    },
  },
  components: [
    { path: '~/components/ui', pathPrefix: false },
    { path: '~/components/shared', pathPrefix: false },
    { path: '~/components/identity-access', pathPrefix: false },
    '~/components',
  ],
  vite: {
    plugins: [tailwindcss()],
    server: {
      proxy: {
        '/api': laravelViteProxy,
        '/sanctum': laravelViteProxy,
      },
    },
  },
  nitro: {
    devProxy: {
      '/api': laravelOrigin,
      '/sanctum': laravelOrigin,
    },
  },
  routeRules: {
    '/inscription/activation': {
      ssr: false,
      headers: {
        'Referrer-Policy': 'no-referrer',
        'X-Robots-Tag': 'noindex, nofollow, noarchive',
      },
    },
    '/recuperation': {
      ssr: false,
      headers: {
        'Referrer-Policy': 'no-referrer',
        'X-Robots-Tag': 'noindex, nofollow, noarchive',
      },
    },
    '/cloture': {
      ssr: false,
      headers: {
        'Referrer-Policy': 'no-referrer',
        'X-Robots-Tag': 'noindex, nofollow, noarchive',
      },
    },
    '/compte': {
      ssr: false,
      headers: {
        'X-Robots-Tag': 'noindex, nofollow, noarchive',
      },
    },
    '/compte/**': {
      ssr: false,
      headers: {
        'X-Robots-Tag': 'noindex, nofollow, noarchive',
      },
    },
    '/administration': {
      ssr: false,
      headers: {
        'X-Robots-Tag': 'noindex, nofollow, noarchive',
      },
    },
    '/administration/**': {
      ssr: false,
      headers: {
        'X-Robots-Tag': 'noindex, nofollow, noarchive',
      },
    },
  },
})
