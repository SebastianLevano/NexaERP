<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { CheckCircle2, AlertCircle, Info, X } from 'lucide-vue-next';
import type { AppPageProps } from '@/types';

type Variant = 'success' | 'error' | 'info';
interface Toast {
    id: number;
    variant: Variant;
    message: string;
}

const page = usePage<AppPageProps>();
const toasts = ref<Toast[]>([]);
let counter = 0;

function pushToast(variant: Variant, message: string) {
    const id = ++counter;
    toasts.value.push({ id, variant, message });
    setTimeout(() => dismiss(id), 4000);
}

function dismiss(id: number) {
    const idx = toasts.value.findIndex((t) => t.id === id);
    if (idx !== -1) toasts.value.splice(idx, 1);
}

const flash = computed(() => page.props.flash);

watch(
    () => flash.value?.success,
    (v) => v && pushToast('success', v),
);
watch(
    () => flash.value?.error,
    (v) => v && pushToast('error', v),
);
watch(
    () => flash.value?.info,
    (v) => v && pushToast('info', v),
);

const variantStyles: Record<Variant, { icon: typeof CheckCircle2; classes: string }> = {
    success: {
        icon: CheckCircle2,
        classes: 'border-emerald-500/30 bg-emerald-500/10 text-emerald-200',
    },
    error: {
        icon: AlertCircle,
        classes: 'border-rose-500/30 bg-rose-500/10 text-rose-200',
    },
    info: {
        icon: Info,
        classes: 'border-violet-500/30 bg-violet-500/10 text-violet-200',
    },
};
</script>

<template>
    <Teleport to="body">
        <div
            class="fixed bottom-4 right-4 z-[60] flex flex-col-reverse gap-2 pointer-events-none w-[calc(100vw-2rem)] max-w-sm"
        >
            <TransitionGroup
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 translate-x-4"
                enter-to-class="opacity-100 translate-x-0"
                leave-active-class="transition duration-150 ease-in absolute right-0"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0 translate-x-4"
                move-class="transition-transform duration-200"
            >
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    role="status"
                    :aria-live="toast.variant === 'error' ? 'assertive' : 'polite'"
                    :class="[
                        'pointer-events-auto rounded-lg border px-3 py-2.5 backdrop-blur-md shadow-lg shadow-black/30',
                        'flex items-start gap-2 text-sm',
                        variantStyles[toast.variant].classes,
                    ]"
                >
                    <component
                        :is="variantStyles[toast.variant].icon"
                        class="h-4 w-4 mt-0.5 shrink-0"
                        :stroke-width="1.5"
                    />
                    <p class="flex-1 leading-snug">{{ toast.message }}</p>
                    <button
                        @click="dismiss(toast.id)"
                        class="shrink-0 -mr-1 -mt-1 p-1 rounded hover:bg-white/5 transition-colors"
                        aria-label="Cerrar notificación"
                    >
                        <X class="h-3 w-3" :stroke-width="1.5" />
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>
