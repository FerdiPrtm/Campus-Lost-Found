/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{vue,js}'],
  theme: {
    extend: {
      colors: {
        background: '#F8FAFC',
        primary: {
          DEFAULT: '#4F46E5',
          hover: '#4338CA'
        },
        dark: '#0F172A',
        success: '#16A34A',
        warning: '#F59E0B',
        danger: '#EF4444',
        'text-primary': '#0F172A',
        'text-secondary': '#64748B',
        'text-muted': '#94A3B8'
      },
      borderRadius: {
        '2xl': '1rem'
      },
      boxShadow: {
        card: '0 1px 3px 0 rgb(0 0 0 / 0.08)'
      }
    }
  },
  plugins: []
}