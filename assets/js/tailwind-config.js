tailwind.config = {
    theme: {
        extend: {
            fontFamily: {
                sans: ['Geist', 'ui-sans-serif', 'system-ui', '-apple-system', 'sans-serif'],
                mono: ['Geist Mono', 'ui-monospace', 'SFMono-Regular', 'monospace'],
            },
            colors: {
                primary: '#34d399',
                primaryHover: '#6ee7b7',
                primaryDeep: '#10b981',
                surface: {
                    DEFAULT: '#0b0c0e',
                    card: '#101114',
                    raised: '#18181b',
                    line: '#27272a',
                },
            },
            boxShadow: {
                'soft': '0 4px 24px -6px rgba(0, 0, 0, 0.5)',
                'glow': '0 10px 40px -5px rgba(52, 211, 153, 0.25)',
                'glow-sm': '0 0 20px -5px rgba(52, 211, 153, 0.35)',
            },
        },
    },
};