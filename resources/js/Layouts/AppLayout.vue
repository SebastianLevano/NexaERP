<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import {
    LayoutDashboard,
    ShoppingCart,
    Receipt,
    LogOut,
    Search,
    PanelLeft,
} from 'lucide-vue-next';
import type { AppPageProps } from '@/types';

const page = usePage<AppPageProps>();
const user = computed(() => page.props.auth.user);

const nav = [
    { name: 'Dashboard', href: '/dashboard', icon: LayoutDashboard },
    { name: 'Punto de venta', href: '/sales/pos', icon: ShoppingCart },
    { name: 'Ventas', href: '/sales', icon: Receipt },
];

function isActive(href: string): boolean {
    if (href === '/dashboard') return page.url === '/dashboard';
    return page.url.startsWith(href);
}

function logout() {
    router.post('/logout');
}

const initials = computed(() => {
    if (!user.value) return '?';
    return user.value.name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();
});
</script>

<template>
    <div class="flex min-h-screen bg-background text-foreground">
        <!-- Sidebar -->
        <aside
            class="hidden md:flex w-60 shrink-0 flex-col border-r border-border bg-background"
        >
            <div class="flex h-14 items-center gap-2 border-b border-border px-4">
                <div
                    class="flex h-6 w-6 items-center justify-center rounded-md bg-accent text-accent-foreground text-xs font-bold"
                >
                    N
                </div>
                <span class="text-sm font-semibold tracking-tight">NexaERP</span>
            </div>
            <nav class="flex-1 px-2 py-3 space-y-0.5">
                <Link
                    v-for="item in nav"
                    :key="item.href"
                    :href="item.href"
                    :class="[
                        'flex items-center gap-2.5 rounded-lg px-2.5 py-1.5 text-sm transition-colors duration-150',
                        isActive(item.href)
                            ? 'bg-surface text-foreground'
                            : 'text-muted-foreground hover:bg-surface hover:text-foreground',
                    ]"
                >
                    <component :is="item.icon" class="h-4 w-4" :stroke-width="1.5" />
                    <span>{{ item.name }}</span>
                </Link>
            </nav>
            <div class="border-t border-border p-3">
                <div class="flex items-center gap-2.5">
                    <div
                        class="flex h-7 w-7 items-center justify-center rounded-full bg-surface-elevated text-xs font-medium text-foreground"
                    >
                        {{ initials }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium truncate">{{ user?.name }}</p>
                        <p class="text-[11px] text-muted-foreground truncate">
                            {{ user?.roles?.[0] ?? '' }}
                        </p>
                    </div>
                    <button
                        @click="logout"
                        class="rounded-md p-1.5 text-muted-foreground hover:bg-surface hover:text-foreground transition-colors"
                        title="Cerrar sesión"
                    >
                        <LogOut class="h-4 w-4" :stroke-width="1.5" />
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main -->
        <div class="flex-1 flex flex-col min-w-0">
            <header
                class="flex h-14 items-center gap-3 border-b border-border bg-background/80 backdrop-blur px-4 sticky top-0 z-10"
            >
                <button class="md:hidden p-1.5 rounded-md hover:bg-surface">
                    <PanelLeft class="h-4 w-4" :stroke-width="1.5" />
                </button>
                <div
                    class="flex h-8 items-center gap-2 rounded-lg border border-border bg-surface px-2.5 text-xs text-muted-foreground flex-1 max-w-md"
                >
                    <Search class="h-3.5 w-3.5" :stroke-width="1.5" />
                    <span>Buscar...</span>
                    <kbd
                        class="ml-auto text-[10px] font-mono px-1.5 py-0.5 rounded border border-border-strong bg-background"
                    >
                        ⌘K
                    </kbd>
                </div>
            </header>
            <main class="flex-1">
                <slot />
            </main>
        </div>
    </div>
</template>
