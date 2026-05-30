/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./public/**/*.php",
    "./public/frontend/**/*.php",
    "./public/**/*.js",
    "./src/**/*.php",
    "./admin/**/*.php",
  ],
  theme: {
    extend: {
      colors: {
        gold: {
          DEFAULT: '#000000', 
          glow: 'rgba(0, 0, 0, 0.05)',
        },
        gray: {
          950: '#0a0a0a',
          900: '#121212',
          800: '#1d1d1f',
          700: '#333333',
          600: '#444444',
          400: '#86868b',
          200: '#e5e5e7',
          100: '#ededf0',
          50: '#f5f5f7',
        }
      },
      spacing: {
        'royal-1': '0.25rem',
        'royal-2': '0.5rem',
        'royal-3': '0.75rem',
        'royal-4': '1rem',
        'royal-5': '1.25rem',
        'royal-6': '1.5rem',
        'royal-8': '2rem',
        'royal-10': '2.5rem',
        'royal-12': '3rem',
        'royal-16': '4rem',
        'royal-20': '5rem',
        'gap-premium': '4.5rem',
      },
      fontFamily: {
        heading: ['"DM Sans"', 'sans-serif'],
        body: ['Inter', 'sans-serif'],
      },
      borderRadius: {
        'royal-sm': '2px',
        'royal-md': '8px',
        'royal-lg': '16px',
      },
      transitionDuration: {
        'fast': '0.2s',
        'normal': '0.5s',
        'slow': '0.8s',
      },
      transitionTimingFunction: {
        'premium': 'cubic-bezier(0.16, 1, 0.3, 1)',
      }
    },
  },
  plugins: [],
}
