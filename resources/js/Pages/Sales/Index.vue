<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import Card from '@/Components/ui/Card.vue';
import { formatCurrency, formatDate } from '@/lib/formatters';
import {
    Search,
    Plus,
    ChevronLeft,
    ChevronRight,
    Receipt as ReceiptIcon,
    Download,
} from 'lucide-vue-next';

interface SaleRow {
    id: number;
    number: string;
    status: 'draft' | 'confirmed' | 'paid' | 'cancelled';
    status_label: string;
    total: number;
    paid_amount: number;
    balance: number;
    issued_at: string;
    customer: { id: number; name: string } | null;
    seller: { id: number; name: string } | null;
}

interface Meta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

interface StatusOption {
    value: string;
    label: string;
}

const props = defineProps<{
    sales: {
        data: SaleRow[];
        meta: Meta;
        links: { prev: string | null; next: string | null };
    };
    filters: { q?: string; status?: string; from?: string; until?: string };
    statuses: StatusOption[];
}>();

const q = ref(props.filters.q ?? '');
const status = ref(props.filters.status ?? '');
const from = ref(props.filters.from ?? '');
const until = ref(props.filters.until ?? '');

function applyFilters() {
    router.get(
        '/sales',
        { q: q.value || undefined, status: status.value || undefined, from: from.value || undefined, until: until.value || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

let searchTimer: ReturnType<typeof setTimeout> | null = null;
watch(q, () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 300);
});
watch([status, from, until], applyFilters);

function resetFilters() {
    q.value = '';
    status.value = '';
    from.value = '';
    until.value = '';
}

const statusVariant = (s: string) =>
    ({
        draft: 'default',
        confirmed: 'info',
        paid: 'success',
        cancelled: 'danger',
    })[s] as 'default' | 'info' | 'success' | 'danger';

const hasFilters = computed(
    () => q.value || status.value || from.value || until.value,
);

const exportUrl = computed(() => {
    const params = new URLSearchParams();
    if (q.value) params.append('q', q.value);
    if (status.value) params.append('status', status.value);
    if (from.value) params.append('from', from.value);
    if (until.value) params.append('until', until.value);
    const qs = params.toString();
    return '/sales/export' + (qs ? `?${qs}` : '');
});
</script>

<template>
    <Head title="Ventas" />

    <AppLayout>
        <div class="p-6 lg:p-8 space-y-6">
            <!-- Header -->
            <header class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Ventas</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ sales.meta.total }} venta{{ sales.meta.total === 1 ? '' : 's' }} registrada{{ sales.meta.total === 1 ? '' : 's' }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a :href="exportUrl" download>
                        <Button variant="outline" size="md">
                            <Download class="h-4 w-4" :stroke-width="1.5" />
                            Exportar CSV
                        </Button>
                    </a>
                    <Link href="/sales/pos">
                        <Button size="md">
                            <Plus class="h-4 w-4" :stroke-width="1.5" />
                            Nueva venta
                        </Button>
                    </Link>
                </div>
            </header>

            <!-- Filtros -->
            <Card :padded="false">
                <div class="p-3 grid grid-cols-1 md:grid-cols-12 gap-2">
                    <div class="md:col-span-5 relative">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" :stroke-width="1.5" />
                        <Input v-model="q" placeholder="Buscar por número o cliente..." class="pl-9" />
                    </div>
                    <div class="md:col-span-3">
                        <Select v-model="status">
                            <option value="">Todos los estados</option>
                            <option v-for="s in statuses" :key="s.value" :value="s.value">
                                {{ s.label }}
                            </option>
                        </Select>
                    </div>
                    <div class="md:col-span-2">
                        <Input type="date" v-model="from" placeholder="Desde" />
                    </div>
                    <div class="md:col-span-2">
                        <Input type="date" v-model="until" placeholder="Hasta" />
                    </div>
                </div>
                <div v-if="hasFilters" class="px-3 pb-3 -mt-1">
                    <button
                        type="button"
                        @click="resetFilters"
                        class="text-xs text-muted-foreground hover:text-foreground transition-colors"
                    >
                        Limpiar filtros
                    </button>
                </div>
            </Card>

            <!-- Tabla -->
            <Card :padded="false">
                <div v-if="sales.data.length === 0" class="py-16 text-center">
                    <div class="flex h-10 w-10 mx-auto items-center justify-center rounded-lg bg-surface-elevated mb-3">
                        <ReceiptIcon class="h-5 w-5 text-muted-foreground" :stroke-width="1.5" />
                    </div>
                    <p class="text-sm font-medium">Sin resultados</p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{ hasFilters ? 'Prueba ajustando los filtros.' : 'Aún no hay ventas registradas.' }}
                    </p>
                </div>

                <table v-else class="w-full text-sm">
                    <thead class="text-[11px] text-muted-foreground uppercase tracking-wide">
                        <tr class="border-b border-border">
                            <th class="px-5 py-2.5 text-left font-medium">N°</th>
                            <th class="px-5 py-2.5 text-left font-medium">Fecha</th>
                            <th class="px-5 py-2.5 text-left font-medium">Cliente</th>
                            <th class="px-5 py-2.5 text-left font-medium">Vendedor</th>
                            <th class="px-5 py-2.5 text-right font-medium">Total</th>
                            <th class="px-5 py-2.5 text-right font-medium">Saldo</th>
                            <th class="px-5 py-2.5 text-left font-medium">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/60">
                        <tr
                            v-for="s in sales.data"
                            :key="s.id"
                            class="hover:bg-surface/40 transition-colors cursor-pointer"
                            @click="router.visit(`/sales/${s.id}`)"
                        >
                            <td class="px-5 py-3 font-mono text-xs">
                                <Link :href="`/sales/${s.id}`" class="hover:text-accent">
                                    {{ s.number }}
                                </Link>
                            </td>
                            <td class="px-5 py-3 text-muted-foreground text-xs">
                                {{ formatDate(s.issued_at) }}
                            </td>
                            <td class="px-5 py-3">
                                <span v-if="s.customer">{{ s.customer.name }}</span>
                                <span v-else class="text-muted-foreground">—</span>
                            </td>
                            <td class="px-5 py-3 text-muted-foreground text-xs">
                                {{ s.seller?.name ?? '—' }}
                            </td>
                            <td class="px-5 py-3 text-right font-mono tabular-nums">
                                {{ formatCurrency(s.total) }}
                            </td>
                            <td class="px-5 py-3 text-right font-mono text-xs">
                                <span
                                    v-if="s.balance > 0"
                                    class="text-amber-300"
                                >
                                    {{ formatCurrency(s.balance) }}
                                </span>
                                <span v-else class="text-muted-foreground">—</span>
                            </td>
                            <td class="px-5 py-3">
                                <Badge :variant="statusVariant(s.status)">
                                    {{ s.status_label }}
                                </Badge>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </Card>

            <!-- Paginación -->
            <div
                v-if="sales.meta.last_page > 1"
                class="flex items-center justify-between text-xs text-muted-foreground"
            >
                <p>
                    Mostrando {{ sales.meta.from }}-{{ sales.meta.to }} de {{ sales.meta.total }}
                </p>
                <div class="flex items-center gap-1">
                    <Link
                        v-if="sales.links.prev"
                        :href="sales.links.prev"
                        preserve-scroll
                        class="p-1.5 rounded-md border border-border hover:bg-surface"
                    >
                        <ChevronLeft class="h-3.5 w-3.5" :stroke-width="1.5" />
                    </Link>
                    <span v-else class="p-1.5 rounded-md border border-border opacity-40">
                        <ChevronLeft class="h-3.5 w-3.5" :stroke-width="1.5" />
                    </span>
                    <span class="px-3 font-mono">
                        {{ sales.meta.current_page }} / {{ sales.meta.last_page }}
                    </span>
                    <Link
                        v-if="sales.links.next"
                        :href="sales.links.next"
                        preserve-scroll
                        class="p-1.5 rounded-md border border-border hover:bg-surface"
                    >
                        <ChevronRight class="h-3.5 w-3.5" :stroke-width="1.5" />
                    </Link>
                    <span v-else class="p-1.5 rounded-md border border-border opacity-40">
                        <ChevronRight class="h-3.5 w-3.5" :stroke-width="1.5" />
                    </span>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
