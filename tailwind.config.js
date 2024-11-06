/** @type {import('tailwindcss').Config} */
export default {
  content: ["./index.php"],
  safelist: [
    'min-w-[18rem]',
    {
      pattern: /decoration-(red-700|green-800|blue-800|pink-700|lime-500|cyan-600|amber-600|yellow-400|purple-700|rose-400)/,
    },
    {
      pattern: /text-(red-700|green-800|blue-800|pink-700|lime-500|cyan-600|amber-600|yellow-400|purple-700|rose-400)\/100/,
    },
    {
      pattern: /bg-(tag-red|tag-yellow|tag-blue|tag-orange|tag-cyan|tag-pink|tag-green|tag-lime|tag-navy|tag-purple)\/100/,
    },
  ],
  theme: {
    extend: {
      colors: {
        'tag-red': '#751C1C',
        'tag-yellow': '#9b8e03',
        'tag-blue': '#1C5875',
        'tag-orange': '#b1640a',
        'tag-cyan': '#1C7556',
        'tag-pink': '#6d0958',
        'tag-green': '#264b0e',
        'tag-lime': '#185504',
        'tag-navy': '#2E1C75',
        'tag-purple': '#5B1C75',
        'tag-positive': '#0a291a',
        'tag-negative': '#280b1a',
        'dark' : '#141624',
        'darker': '#0e0f18',
        'subtle': '#333344',
      },
    }
  },
  plugins: [],
}

