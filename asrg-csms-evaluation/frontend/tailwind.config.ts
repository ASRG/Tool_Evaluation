import type { Config } from 'tailwindcss'

const config: Config = {
  content: ['./src/**/*.{ts,tsx}'],
  theme: {
    extend: {
      colors: {
        asrg: {
          red: '#E71E25',
          purple: '#AAA4EF',
          dark: '#32373C',
          black: '#000000',
          white: '#FFFFFF',
        },
        score: {
          fully: '#16a34a',       // green-600
          partially: '#d97706',   // amber-600
          'does-not': '#dc2626',  // red-600
        },
      },
      fontFamily: {
        sans: ['Roboto', 'system-ui', 'sans-serif'],
      },
      maxWidth: {
        content: '800px',
        wide: '1200px',
      },
    },
  },
  plugins: [],
}

export default config
