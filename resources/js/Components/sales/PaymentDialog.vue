<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Dialog from '@/Components/ui/Dialog.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import Select from '@/Components/ui/Select.vue';
import { formatCurrency } from '@/lib/formatters';
import type { CartCustomer, CartLine } from '@/composables/useSaleCart';

interface PaymentMethodOption {
    value: string;
    label: string;
}

const props = defineProps<{
    open: boolean;
    total: number;
    lines: readonly CartLine[];
    customer: CartCustomer | null;
    paymentMethods: PaymentMethodOption[];
}>();

const emit = defineEmits<{
    (e: 'update:open', v: boolean): void;
    (e: 'success'): void;
}>();

const form = useForm({
    customer_id: null as number | null,
    items: [] as { product_id: number; quantity: number; unit_price: number }[],
    payment_method: 'cash',
    amount_tendered: 0,
    payment_reference: '',
    notes: '',
});

watch(
    () => props.open,
    (open) => {
        if (open) {
            form.clearErrors();
            form.customer_id = props.customer?.id ?? null;
            form.items = props.lines.map((l) => ({
                product_id: l.product.id,
                quantity: l.quantity,
                unit_price: l.product.price,
            }));
            form.payment_method = props.paymentMethods[0]?.value ?? 'cash';
            form.amount_tendered = Number(props.total.toFixed(2));
            form.payment_reference = '';
            form.notes = '';
        }
    },
);

const change = computed(() => Math.max(0, form.amount_tendered - props.total));
const insufficient = computed(() => form.amount_tendered < props.total);

function submit() {
    form.post('/sales', {
        preserveScroll: true,
        onSuccess: () => {
            emit('success');
            emit('update:open', false);
        },
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)" title="Cobrar venta">
        <form @submit.prevent="submit" class="space-y-5">
            <!-- Resumen -->
            <div class="rounded-lg border border-border bg-background/40 p-4">
                <div class="flex items-baseline justify-between">
                    <span class="text-xs uppercase tracking-wide text-muted-foreground">
                        Total a cobrar
                    </span>
                    <span class="text-2xl font-semibold font-mono">
                        {{ formatCurrency(total) }}
                    </span>
                </div>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ lines.length }} producto{{ lines.length === 1 ? '' : 's' }}
                    <span v-if="customer"> · {{ customer.name }}</span>
                </p>
            </div>

            <!-- Método -->
            <div class="space-y-1.5">
                <Label for="payment_method">Método de pago</Label>
                <Select id="payment_method" v-model="form.payment_method">
                    <option v-for="m in paymentMethods" :key="m.value" :value="m.value">
                        {{ m.label }}
                    </option>
                </Select>
            </div>

            <!-- Monto recibido -->
            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1.5">
                    <Label for="amount_tendered">Monto recibido</Label>
                    <Input
                        id="amount_tendered"
                        type="number"
                        :modelValue="form.amount_tendered"
                        @update:modelValue="(v) => (form.amount_tendered = Number(v) || 0)"
                        autofocus
                    />
                </div>
                <div class="space-y-1.5">
                    <Label>Vuelto</Label>
                    <div
                        class="h-10 flex items-center px-3 rounded-lg border border-border bg-background/30 font-mono text-sm"
                        :class="insufficient ? 'text-destructive' : 'text-emerald-400'"
                    >
                        {{ insufficient ? 'Falta ' + formatCurrency(total - form.amount_tendered) : formatCurrency(change) }}
                    </div>
                </div>
            </div>

            <!-- Referencia (transfer / card) -->
            <div v-if="form.payment_method !== 'cash'" class="space-y-1.5">
                <Label for="payment_reference">Referencia / N° operación</Label>
                <Input
                    id="payment_reference"
                    v-model="form.payment_reference"
                    placeholder="Ej: OP-887, voucher 5544..."
                />
            </div>

            <!-- Acciones -->
            <div class="flex justify-end gap-2 pt-2">
                <Button
                    type="button"
                    variant="ghost"
                    @click="emit('update:open', false)"
                    :disabled="form.processing"
                >
                    Cancelar
                </Button>
                <Button
                    type="submit"
                    :disabled="form.processing || insufficient || lines.length === 0"
                >
                    <span v-if="form.processing">Procesando…</span>
                    <span v-else>Confirmar venta</span>
                </Button>
            </div>
        </form>
    </Dialog>
</template>
