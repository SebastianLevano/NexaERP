<script setup lang="ts">
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Dialog from '@/Components/ui/Dialog.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';

const props = defineProps<{
    open: boolean;
    saleId: number;
    saleNumber: string;
}>();

const emit = defineEmits<{
    (e: 'update:open', v: boolean): void;
}>();

const form = useForm({ reason: '' });

watch(
    () => props.open,
    (v) => {
        if (v) form.reason = '';
    },
);

function submit() {
    form.post(`/sales/${props.saleId}/cancel`, {
        preserveScroll: true,
        onSuccess: () => emit('update:open', false),
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)" title="Anular venta">
        <form @submit.prevent="submit" class="space-y-4">
            <p class="text-sm text-muted-foreground">
                Vas a anular la venta
                <span class="font-mono text-foreground">{{ saleNumber }}</span>.
                El stock vendido será restaurado mediante un movimiento de
                <span class="text-foreground">entrada</span> auditado.
            </p>

            <div class="space-y-1.5">
                <Label for="reason">Motivo (opcional)</Label>
                <Input
                    id="reason"
                    v-model="form.reason"
                    placeholder="Ej: error en producto, cliente desistió..."
                />
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <Button
                    type="button"
                    variant="ghost"
                    @click="emit('update:open', false)"
                    :disabled="form.processing"
                >
                    Volver
                </Button>
                <Button type="submit" variant="destructive" :disabled="form.processing">
                    <span v-if="form.processing">Anulando…</span>
                    <span v-else>Anular venta</span>
                </Button>
            </div>
        </form>
    </Dialog>
</template>
