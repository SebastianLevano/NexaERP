<script setup lang="ts">
import { cn } from '@/lib/utils';

defineProps<{
    modelValue?: string | number;
    type?: string;
    placeholder?: string;
    autocomplete?: string;
    autofocus?: boolean;
    disabled?: boolean;
    name?: string;
    id?: string;
}>();

defineEmits<{ (e: 'update:modelValue', v: string): void }>();
</script>

<template>
    <input
        :id="id"
        :name="name"
        :type="type ?? 'text'"
        :placeholder="placeholder"
        :autocomplete="autocomplete"
        :autofocus="autofocus"
        :disabled="disabled"
        :value="modelValue"
        @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
        :class="cn(
            'h-10 w-full rounded-lg border border-border bg-surface px-3 text-sm',
            'text-foreground placeholder:text-muted-foreground',
            'transition-colors duration-150 ease-out',
            'focus-visible:border-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/40',
            'disabled:cursor-not-allowed disabled:opacity-50',
        )"
    />
</template>
