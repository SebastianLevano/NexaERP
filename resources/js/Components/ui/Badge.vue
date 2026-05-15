<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';

type Variant = 'default' | 'success' | 'warning' | 'danger' | 'info';

const props = withDefaults(
    defineProps<{ variant?: Variant }>(),
    { variant: 'default' },
);

const variants: Record<Variant, string> = {
    default: 'bg-surface text-muted-foreground border-border',
    success: 'bg-emerald-500/10 text-emerald-300 border-emerald-500/20',
    warning: 'bg-amber-500/10 text-amber-300 border-amber-500/20',
    danger: 'bg-rose-500/10 text-rose-300 border-rose-500/20',
    info: 'bg-violet-500/10 text-violet-300 border-violet-500/20',
};

const classes = computed(() =>
    cn(
        'inline-flex items-center gap-1 rounded-md border px-1.5 py-0.5 text-[10px] font-medium uppercase tracking-wide',
        variants[props.variant],
    ),
);
</script>

<template>
    <span :class="classes">
        <slot />
    </span>
</template>
