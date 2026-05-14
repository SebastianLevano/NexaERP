import type { PageProps as InertiaPageProps } from '@inertiajs/core';

export interface User {
    id: number;
    name: string;
    email: string;
    roles: string[];
}

export interface FlashMessages {
    success?: string;
    error?: string;
    info?: string;
}

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> =
    InertiaPageProps & {
        auth: {
            user: User | null;
        };
        flash: FlashMessages;
    } & T;

declare module 'vue' {
    interface ComponentCustomProperties {
        $route: typeof import('ziggy-js').route;
    }
}

declare module '*.vue' {
    import type { DefineComponent } from 'vue';
    const component: DefineComponent<object, object, unknown>;
    export default component;
}
