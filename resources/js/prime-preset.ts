import Aura from '@primeuix/themes/aura'

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const aura: any = Aura
const light = aura.semantic?.colorScheme?.light ?? {}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export const CvPreset: any = {
    ...aura,
    primitive: {
        ...(aura.primitive ?? {}),
        borderRadius: {
            none: '0',
            xs: '0',
            sm: '0',
            md: '0',
            lg: '0',
            xl: '0',
        },
    },
    semantic: {
        ...(aura.semantic ?? {}),
        primary: {
            50: '#f5f5f5',
            100: '#e8e8e8',
            200: '#d4d4d4',
            300: '#bbb',
            400: '#999',
            500: '#555',
            600: '#333',
            700: '#111',
            800: '#000',
            900: '#000',
            950: '#000',
        },
        formField: {
            ...(aura.semantic?.formField ?? {}),
            borderRadius: '0',
        },
        content: {
            ...(aura.semantic?.content ?? {}),
            borderRadius: '0',
        },
        overlay: {
            ...(aura.semantic?.overlay ?? {}),
            borderRadius: '0',
        },
        list: {
            ...(aura.semantic?.list ?? {}),
            borderRadius: '0',
        },
        colorScheme: {
            light: {
                ...light,
                surface: {
                    0: '#ffffff',
                    50: '#fcfcf8',
                    100: '#f5f5f0',
                    200: '#eee',
                    300: '#ddd',
                    400: '#bbb',
                    500: '#999',
                    600: '#555',
                    700: '#333',
                    800: '#111',
                    900: '#000',
                    950: '#000',
                },
            },
        },
    },
}
