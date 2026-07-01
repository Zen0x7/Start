/// <reference types="vite/client" />

declare module '*.vue' {
    import type { DefineComponent } from 'vue'
    const component: DefineComponent<object, object, unknown>
    export default component
}

declare module 'qrcode' {
    export function toDataURL(
        text: string,
        options?: Record<string, unknown>,
    ): Promise<string>
    export function toString(
        text: string,
        options?: Record<string, unknown>,
    ): Promise<string>
}

