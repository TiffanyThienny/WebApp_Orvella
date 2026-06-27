/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
    ],
    theme: {
        extend: {
            colors: {
                medical: {
                    light: '#E0F2FE', // Soft Blue
                    blue: '#0EA5E9',  // Blue Accent
                    deep: '#1E3A8A',  // Deep Medical Blue
                    navy: '#1E293B',  // Navy
                }
            }
        },
    },
    plugins: [],
};
