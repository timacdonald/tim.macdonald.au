/** @type {import('tailwindcss').Config} */
export default {
    content: ["./resources/views/**/*.*"],
    theme: {
        extend: {
            zIndex: {
                '-10': '-10',
            },
            padding: {
                '16/9': '56.25%',
            },
            screens: {
                'dark': {
                    'raw': '(prefers-color-scheme: dark)'
                },
            },
            colors: {
                'near-black': '#0B0819',
                text: {
                    100: '#EBEBEE',
                    200: '#CECDD5',
                    300: '#B0AEBC',
                    400: '#757289',
                    500: '#3A3557',
                    600: '#34304E',
                    700: '#232034',
                    800: '#1A1827',
                    900: '#11101A',
                },
                purple: {
                    100: '#EFECFE',
                    200: '#D7CFFD',
                    300: '#BFB3FB',
                    400: '#8F79F9',
                    500: '#5F40F6',
                    600: '#563ADD',
                    700: '#392694',
                    800: '#2B1D6F',
                    900: '#090428',
                },
                'electric-violet': {
                    '50': '#fafbff',
                    '100': '#ebeefe',
                    '200': '#d3d8fd',
                    '300': '#b0b5fc',
                    '400': '#8b89fb',
                    '500': '#7366f9',
                    '600': '#5f41f6',
                    '700': '#391ac7',
                    '800': '#2f1796',
                    '900': '#261872',
                    '950': '#140c36',
                },
            },
        },
    },
    plugins: [],
}
