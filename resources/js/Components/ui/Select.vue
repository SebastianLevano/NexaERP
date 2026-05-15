<script setup lang="ts">
import { cn } from '@/lib/utils';

defineProps<{
    modelValue?: string | number;
    name?: string;
    id?: string;
    disabled?: boolean;
}>();

defineEmits<{ (e: 'update:modelValue', v: string): void }>();
</script>

<template>
    <select
        :id="id"
        :name="name"
        :value="modelValue"
        :disabled="disabled"
        @change="$emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
        :class="cn(
            'h-10 w-full rounded-lg border border-border bg-surface px-3 text-sm',
            'text-foreground placeholder:text-muted-foreground',
            'transition-colors duration-150 ease-out appearance-none',
            'bg-no-repeat bg-right pr-9',
            'focus-visible:border-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/40',
            'disabled:cursor-not-allowed disabled:opacity-50',
        )"
        style="background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2216%22 height=%2216%22 fill=%22none%22 stroke=%22%2371717a%22 stroke-width=%221.5%22 viewBox=%220 0 24 24%22><polyline points=%226 9 12 15 18 9%22/></svg>'); background-position: right 0.75rem center; background-size: 16px;"
    >
        <slot />
    </select>
</template>
