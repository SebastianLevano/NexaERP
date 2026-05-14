<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';

type Variant = 'default' | 'outline' | 'ghost' | 'destructive';
type Size = 'sm' | 'md' | 'lg' | 'icon';

const props = withDefaults(
    defineProps<{
        variant?: Variant;
        size?: Size;
        type?: 'button' | 'submit' | 'reset';
        disabled?: boolean;
    }>(),
    { variant: 'default', size: 'md', type: 'button', disabled: false },
);

const base =
    'inline-flex items-center justify-center gap-2 font-medium rounded-lg ' +
    'transition-colors duration-150 ease-out outline-none ' +
    'focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background ' +
    'disabled:pointer-events-none disabled:opacity-50';

const variants: Record<Variant, string> = {
    default:
        'bg-accent text-accent-foreground hover:bg-accent-hover shadow-sm',
    outline:
        'border border-border-strong bg-transparent hover:bg-surface text-foreground',
    ghost: 'hover:bg-surface text-foreground',
    destructive:
        'bg-destructive text-destructive-foreground hover:opacity-90',
};

const sizes: Record<Size, string> = {
    sm: 'h-8 px-3 text-xs',
    md: 'h-9 px-4 text-sm',
    lg: 'h-11 px-6 text-sm',
    icon: 'h-9 w-9',
};

const classes = computed(() =>
    cn(base, variants[props.variant], sizes[props.size]),
);
</script>

<template>
    <button :type="type" :class="classes" :disabled="disabled">
        <slot />
    </button>
</template>
