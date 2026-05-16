<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Badge from '@/Components/ui/Badge.vue';
import KpiCard from '@/Components/data/KpiCard.vue';
import SalesChart from '@/Components/data/SalesChart.vue';
import { formatCurrency, formatDate } from '@/lib/formatters';
import {
    TrendingUp,
    Wallet,
    ShoppingBag,
    AlertTriangle,
    Receipt as ReceiptIcon,
    Package,
} from 'lucide-vue-next';

interface Kpis {
    today_count: number;
    today_sum: number;
    month_count: number;
    month_sum: number;
    avg_ticket: number;
    below_minimum: number;
}
interface TopProduct {
    id: number;
    name: string;
    sku: string;
    quantity: number;
    amount: number;
}
interface RecentSale {
    id: number;
    number: string;
    status: string;
    status_label: string;
    total: number;
    issued_at: string;
    customer: string | null;
}

defineProps<{
    kpis: Kpis;
    series: { labels: string[]; values: number[] };
    topProducts: TopProduct[];
    recentSales: RecentSale[];
}>();

const statusVariant = (s: string) =>
    ({
        draft: 'default',
        confirmed: 'info',
        paid: 'success',
        cancelled: 'danger',
    })[s] as 'default' | 'info' | 'success' | 'danger';
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

            <!-- KPIs -->
            <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <KpiCard
                    label="Ventas hoy"
                    :value="formatCurrency(kpis.today_sum)"
                    :helper="`${kpis.today_count} comprobante${kpis.today_count === 1 ? '' : 's'}`"
                    :icon="TrendingUp"
                />
                <KpiCard
                    label="Ventas del mes"
                    :value="formatCurrency(kpis.month_sum)"
                    :helper="`${kpis.month_count} venta${kpis.month_count === 1 ? '' : 's'}`"
                    :icon="Wallet"
                />
                <KpiCard
                    label="Ticket promedio"
                    :value="formatCurrency(kpis.avg_ticket)"
                    helper="Promedio del mes"
                    :icon="ShoppingBag"
                />
                <KpiCard
                    label="Bajo mínimo"
                    :value="String(kpis.below_minimum)"
                    helper="Productos a reponer"
                    :icon="AlertTriangle"
                />
            </section>

            <!-- Chart -->
            <Card :padded="false">
                <div class="px-5 py-3 border-b border-border flex items-center justify-between">
                    <h2 class="text-sm font-medium">Ventas últimos 30 días</h2>
                    <span class="text-xs text-muted-foreground">
                        Total: <span class="font-mono text-foreground">{{ formatCurrency(kpis.month_sum) }}</span>
                    </span>
                </div>
                <div class="px-2 pt-3 pb-2">
                    <SalesChart :labels="series.labels" :values="series.values" />
                </div>
            </Card>

            <!-- Listas -->
            <section class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <!-- Top productos -->
                <Card :padded="false">
                    <div class="px-5 py-3 border-b border-border flex items-center gap-2">
                        <Package class="h-4 w-4 text-muted-foreground" :stroke-width="1.5" />
                        <h2 class="text-sm font-medium">Más vendidos del mes</h2>
                    </div>
                    <div v-if="topProducts.length === 0" class="p-8 text-center text-xs text-muted-foreground">
                        Aún no hay ventas este mes.
                    </div>
                    <ul v-else class="divide-y divide-border">
                        <li v-for="(p, i) in topProducts" :key="p.id" class="px-5 py-3 flex items-center gap-3">
                            <span
                                class="flex h-6 w-6 items-center justify-center rounded-full bg-surface-elevated text-[10px] font-mono text-muted-foreground"
                            >
                                {{ i + 1 }}
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium truncate">{{ p.name }}</p>
                                <p class="text-[11px] font-mono text-muted-foreground">{{ p.sku }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-mono">{{ p.quantity }}<span class="text-muted-foreground text-xs"> u</span></p>
                                <p class="text-[11px] text-muted-foreground font-mono">{{ formatCurrency(p.amount) }}</p>
                            </div>
                        </li>
                    </ul>
                </Card>

                <!-- Últimas ventas -->
                <Card :padded="false">
                    <div class="px-5 py-3 border-b border-border flex items-center gap-2">
                        <ReceiptIcon class="h-4 w-4 text-muted-foreground" :stroke-width="1.5" />
                        <h2 class="text-sm font-medium">Últimas ventas</h2>
                    </div>
                    <div v-if="recentSales.length === 0" class="p-8 text-center text-xs text-muted-foreground">
                        Aún no hay ventas registradas.
                    </div>
                    <ul v-else class="divide-y divide-border">
                        <li v-for="sale in recentSales" :key="sale.id">
                            <Link
                                :href="`/sales/${sale.id}`"
                                class="block px-5 py-3 hover:bg-surface/40 transition-colors"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-mono">{{ sale.number }}</span>
                                            <Badge :variant="statusVariant(sale.status)">
                                                {{ sale.status_label }}
                                            </Badge>
                                        </div>
                                        <p class="mt-0.5 text-xs text-muted-foreground truncate">
                                            {{ sale.customer ?? 'Sin cliente' }} · {{ formatDate(sale.issued_at) }}
                                        </p>
                                    </div>
                                    <span class="text-sm font-mono shrink-0">{{ formatCurrency(sale.total) }}</span>
                                </div>
                            </Link>
                        </li>
                    </ul>
                </Card>
            </section>
        </div>
    </AppLayout>
</template>
