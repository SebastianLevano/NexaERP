<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import RecordPaymentDialog from '@/Components/sales/RecordPaymentDialog.vue';
import CancelSaleDialog from '@/Components/sales/CancelSaleDialog.vue';
import { formatCurrency, formatDate } from '@/lib/formatters';
import {
    ArrowLeft,
    CheckCircle2,
    AlertCircle,
    Download,
    XCircle,
    Plus,
} from 'lucide-vue-next';
import type { AppPageProps } from '@/types';

interface SaleProps {
    id: number;
    number: string;
    status: 'draft' | 'confirmed' | 'paid' | 'cancelled';
    status_label: string;
    subtotal: number;
    tax: number;
    total: number;
    paid_amount: number;
    balance: number;
    issued_at: string;
    notes: string | null;
    customer: { id: number; name: string; document: string } | null;
    seller: { id: number; name: string } | null;
    items: {
        id: number;
        product: { id: number; sku: string; name: string };
        quantity: number;
        unit_price: number;
        line_total: number;
    }[];
    payments: {
        id: number;
        method: string;
        method_label: string;
        amount: number;
        paid_at: string;
        reference: string | null;
    }[];
}

const props = defineProps<{
    sale: SaleProps;
    paymentMethods: { value: string; label: string }[];
    canCancel: boolean;
}>();

const page = usePage<AppPageProps>();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const paymentOpen = ref(false);
const cancelOpen = ref(false);

const statusVariant = (status: string) =>
    ({
        draft: 'default',
        confirmed: 'info',
        paid: 'success',
        cancelled: 'danger',
    })[status] as 'default' | 'info' | 'success' | 'danger';

const canRegisterPayment = computed(
    () => props.sale.status !== 'cancelled' && props.sale.balance > 0,
);
const canShowCancel = computed(
    () => props.canCancel && props.sale.status !== 'cancelled',
);
</script>

<template>
    <Head :title="`Venta ${sale.number}`" />

    <AppLayout>
        <div class="p-6 lg:p-8 max-w-4xl space-y-6">
            <!-- Flash messages -->
            <div
                v-if="flashSuccess"
                class="flex items-center gap-2 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-sm text-emerald-300"
            >
                <CheckCircle2 class="h-4 w-4" :stroke-width="1.5" />
                {{ flashSuccess }}
            </div>
            <div
                v-if="flashError"
                class="flex items-center gap-2 rounded-lg border border-rose-500/30 bg-rose-500/10 px-3 py-2 text-sm text-rose-300"
            >
                <AlertCircle class="h-4 w-4" :stroke-width="1.5" />
                {{ flashError }}
            </div>

            <!-- Header -->
            <header class="flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <Link
                        href="/sales"
                        class="inline-flex items-center gap-1.5 text-xs text-muted-foreground hover:text-foreground transition-colors mb-3"
                    >
                        <ArrowLeft class="h-3 w-3" :stroke-width="1.5" />
                        Volver a ventas
                    </Link>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-semibold font-mono tracking-tight">
                            {{ sale.number }}
                        </h1>
                        <Badge :variant="statusVariant(sale.status)">
                            {{ sale.status_label }}
                        </Badge>
                    </div>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ formatDate(sale.issued_at) }}
                        <span v-if="sale.seller"> · Atendió {{ sale.seller.name }}</span>
                    </p>
                </div>

                <!-- Acciones -->
                <div class="flex items-center gap-2">
                    <a :href="`/sales/${sale.id}/pdf`" target="_blank">
                        <Button variant="outline" size="sm">
                            <Download class="h-3.5 w-3.5" :stroke-width="1.5" />
                            PDF
                        </Button>
                    </a>
                    <Button
                        v-if="canRegisterPayment"
                        size="sm"
                        @click="paymentOpen = true"
                    >
                        <Plus class="h-3.5 w-3.5" :stroke-width="1.5" />
                        Registrar pago
                    </Button>
                    <Button
                        v-if="canShowCancel"
                        variant="destructive"
                        size="sm"
                        @click="cancelOpen = true"
                    >
                        <XCircle class="h-3.5 w-3.5" :stroke-width="1.5" />
                        Anular
                    </Button>
                </div>
            </header>

            <!-- Totales en cards (resumen rápido) -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <Card>
                    <p class="text-[11px] uppercase tracking-wide text-muted-foreground">Total</p>
                    <p class="mt-1 text-2xl font-semibold font-mono">{{ formatCurrency(sale.total) }}</p>
                </Card>
                <Card>
                    <p class="text-[11px] uppercase tracking-wide text-muted-foreground">Pagado</p>
                    <p class="mt-1 text-2xl font-semibold font-mono text-emerald-400">
                        {{ formatCurrency(sale.paid_amount) }}
                    </p>
                </Card>
                <Card>
                    <p class="text-[11px] uppercase tracking-wide text-muted-foreground">Saldo</p>
                    <p
                        class="mt-1 text-2xl font-semibold font-mono"
                        :class="sale.balance > 0 ? 'text-amber-400' : 'text-muted-foreground'"
                    >
                        {{ formatCurrency(sale.balance) }}
                    </p>
                </Card>
            </div>

            <!-- Cliente -->
            <Card v-if="sale.customer">
                <p class="text-[11px] uppercase tracking-wide text-muted-foreground">Cliente</p>
                <p class="mt-1 text-sm font-medium">{{ sale.customer.name }}</p>
                <p class="text-xs font-mono text-muted-foreground">{{ sale.customer.document }}</p>
            </Card>

            <!-- Ítems -->
            <Card :padded="false">
                <div class="px-5 py-3 border-b border-border">
                    <h2 class="text-sm font-medium">Detalle</h2>
                </div>
                <table class="w-full text-sm">
                    <thead class="text-[11px] text-muted-foreground uppercase tracking-wide">
                        <tr>
                            <th class="px-5 py-2 text-left font-medium">Producto</th>
                            <th class="px-5 py-2 text-right font-medium">Cant.</th>
                            <th class="px-5 py-2 text-right font-medium">P. Unit</th>
                            <th class="px-5 py-2 text-right font-medium">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="item in sale.items" :key="item.id">
                            <td class="px-5 py-3">
                                <p class="font-medium">{{ item.product.name }}</p>
                                <p class="text-xs font-mono text-muted-foreground">{{ item.product.sku }}</p>
                            </td>
                            <td class="px-5 py-3 text-right font-mono">{{ item.quantity }}</td>
                            <td class="px-5 py-3 text-right font-mono">{{ formatCurrency(item.unit_price) }}</td>
                            <td class="px-5 py-3 text-right font-mono font-medium">{{ formatCurrency(item.line_total) }}</td>
                        </tr>
                    </tbody>
                    <tfoot class="text-sm">
                        <tr class="border-t border-border">
                            <td colspan="3" class="px-5 py-2 text-right text-muted-foreground">Subtotal</td>
                            <td class="px-5 py-2 text-right font-mono">{{ formatCurrency(sale.subtotal) }}</td>
                        </tr>
                        <tr v-if="sale.tax > 0">
                            <td colspan="3" class="px-5 py-2 text-right text-muted-foreground">IGV</td>
                            <td class="px-5 py-2 text-right font-mono">{{ formatCurrency(sale.tax) }}</td>
                        </tr>
                        <tr class="border-t border-border">
                            <td colspan="3" class="px-5 py-3 text-right font-medium">Total</td>
                            <td class="px-5 py-3 text-right font-mono font-semibold text-base">
                                {{ formatCurrency(sale.total) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </Card>

            <!-- Pagos -->
            <Card v-if="sale.payments.length">
                <h2 class="text-sm font-medium mb-3">Pagos</h2>
                <ul class="space-y-2">
                    <li
                        v-for="payment in sale.payments"
                        :key="payment.id"
                        class="flex items-center justify-between text-sm"
                    >
                        <div>
                            <span class="font-medium">{{ payment.method_label }}</span>
                            <span
                                v-if="payment.reference"
                                class="ml-2 text-xs text-muted-foreground font-mono"
                            >
                                Ref: {{ payment.reference }}
                            </span>
                            <span class="ml-2 text-xs text-muted-foreground">
                                {{ formatDate(payment.paid_at) }}
                            </span>
                        </div>
                        <span class="font-mono">{{ formatCurrency(payment.amount) }}</span>
                    </li>
                </ul>
            </Card>
        </div>

        <RecordPaymentDialog
            v-model:open="paymentOpen"
            :saleId="sale.id"
            :balance="sale.balance"
            :paymentMethods="paymentMethods"
        />
        <CancelSaleDialog
            v-model:open="cancelOpen"
            :saleId="sale.id"
            :saleNumber="sale.number"
        />
    </AppLayout>
</template>
