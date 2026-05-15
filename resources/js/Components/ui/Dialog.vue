<script setup lang="ts">
import { onMounted, onBeforeUnmount, watch } from 'vue';

const props = defineProps<{ open: boolean; title?: string }>();
const emit = defineEmits<{ (e: 'update:open', v: boolean): void }>();

function close() {
    emit('update:open', false);
}

function onKey(e: KeyboardEvent) {
    if (e.key === 'Escape' && props.open) {
        e.preventDefault();
        close();
    }
}

onMounted(() => document.addEventListener('keydown', onKey));
onBeforeUnmount(() => document.removeEventListener('keydown', onKey));

watch(
    () => props.open,
    (v) => {
        if (typeof document !== 'undefined') {
            document.body.style.overflow = v ? 'hidden' : '';
        }
    },
);
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                @click.self="close"
            >
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" />

                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 translate-y-2 scale-95"
                    enter-to-class="opacity-100 translate-y-0 scale-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0 scale-95"
                    appear
                >
                    <div
                        class="relative w-full max-w-md rounded-xl border border-border bg-surface shadow-2xl shadow-black/40 p-6"
                    >
                        <header v-if="title" class="mb-4">
                            <h2 class="text-base font-semibold tracking-tight">
                                {{ title }}
                            </h2>
                        </header>
                        <slot />
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
