<script setup lang="ts">
import { cn } from '@/lib/utils';

defineProps<{ class?: string }>();
</script>

<template>
    <div
        :class="cn('animate-pulse rounded-md bg-surface-elevated/60', $props.class)"
        aria-hidden="true"
    />
</template>
