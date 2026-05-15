<script setup lang="ts">
import { cn } from '@/lib/utils';

defineProps<{ padded?: boolean }>();
</script>

<template>
    <div :class="cn(
        'rounded-xl border border-border bg-surface/40',
        padded === false ? '' : 'p-5',
    )">
        <slot />
    </div>
</template>
