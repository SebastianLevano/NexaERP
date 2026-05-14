<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { TrendingUp, ShoppingBag, Wallet, AlertTriangle } from 'lucide-vue-next';

const kpis = [
    { label: 'Ventas hoy', value: 'S/ 0.00', delta: '+0%', icon: TrendingUp },
    { label: 'Ventas del mes', value: 'S/ 0.00', delta: '+0%', icon: Wallet },
    { label: 'Ticket promedio', value: 'S/ 0.00', delta: '—', icon: ShoppingBag },
    { label: 'Bajo mínimo', value: '0', delta: 'productos', icon: AlertTriangle },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout>
        <div class="p-6 lg:p-8 space-y-8">
            <header>
                <h1 class="text-2xl font-semibold tracking-tight">Dashboard</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Resumen general de tu operación.
                </p>
            </header>

            <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div
                    v-for="kpi in kpis"
                    :key="kpi.label"
                    class="rounded-xl border border-border bg-surface/40 p-5"
                >
                    <div class="flex items-start justify-between">
                        <span class="text-xs uppercase tracking-wide text-muted-foreground">
                            {{ kpi.label }}
                        </span>
                        <component
                            :is="kpi.icon"
                            class="h-4 w-4 text-muted-foreground"
                            :stroke-width="1.5"
                        />
                    </div>
                    <p class="mt-3 text-2xl font-semibold font-mono tracking-tight">
                        {{ kpi.value }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">{{ kpi.delta }}</p>
                </div>
            </section>

            <section
                class="rounded-xl border border-border bg-surface/40 p-8 flex flex-col items-center justify-center text-center"
            >
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-lg bg-surface-elevated mb-3"
                >
                    <TrendingUp class="h-5 w-5 text-muted-foreground" :stroke-width="1.5" />
                </div>
                <h2 class="text-sm font-medium">Aún no hay datos</h2>
                <p class="mt-1 text-xs text-muted-foreground max-w-xs">
                    Los KPIs y gráficos aparecerán cuando registres tus primeras ventas.
                </p>
            </section>
        </div>
    </AppLayout>
</template>
