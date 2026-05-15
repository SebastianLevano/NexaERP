<script setup lang="ts">
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Dialog from '@/Components/ui/Dialog.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import Select from '@/Components/ui/Select.vue';
import { formatCurrency } from '@/lib/formatters';

interface MethodOption {
    value: string;
    label: string;
}

const props = defineProps<{
    open: boolean;
    saleId: number;
    balance: number;
    paymentMethods: MethodOption[];
}>();

const emit = defineEmits<{
    (e: 'update:open', v: boolean): void;
}>();

const form = useForm({
    amount: 0,
    method: 'cash',
    reference: '',
});

watch(
    () => props.open,
    (v) => {
        if (v) {
            form.clearErrors();
            form.amount = Number(props.balance.toFixed(2));
            form.method = props.paymentMethods[0]?.value ?? 'cash';
            form.reference = '';
        }
    },
);

function submit() {
    form.post(`/sales/${props.saleId}/payments`, {
        preserveScroll: true,
        onSuccess: () => emit('update:open', false),
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)" title="Registrar pago">
        <form @submit.prevent="submit" class="space-y-4">
            <div class="rounded-lg border border-border bg-background/40 p-3">
                <div class="flex items-baseline justify-between">
                    <span class="text-xs uppercase tracking-wide text-muted-foreground">
                        Saldo pendiente
                    </span>
                    <span class="text-xl font-semibold font-mono">
                        {{ formatCurrency(balance) }}
                    </span>
                </div>
            </div>

            <div class="space-y-1.5">
                <Label for="method">Método</Label>
                <Select id="method" v-model="form.method">
                    <option v-for="m in paymentMethods" :key="m.value" :value="m.value">
                        {{ m.label }}
                    </option>
                </Select>
            </div>

            <div class="space-y-1.5">
                <Label for="amount">Monto</Label>
                <Input
                    id="amount"
                    type="number"
                    :modelValue="form.amount"
                    @update:modelValue="(v) => (form.amount = Number(v) || 0)"
                    autofocus
                />
                <p v-if="form.errors.amount" class="text-xs text-destructive">
                    {{ form.errors.amount }}
                </p>
            </div>

            <div v-if="form.method !== 'cash'" class="space-y-1.5">
                <Label for="reference">Referencia / N° operación</Label>
                <Input id="reference" v-model="form.reference" placeholder="Ej: OP-887..." />
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <Button
                    type="button"
                    variant="ghost"
                    @click="emit('update:open', false)"
                    :disabled="form.processing"
                >
                    Cancelar
                </Button>
                <Button type="submit" :disabled="form.processing || form.amount <= 0">
                    <span v-if="form.processing">Registrando…</span>
                    <span v-else>Registrar pago</span>
                </Button>
            </div>
        </form>
    </Dialog>
</template>
