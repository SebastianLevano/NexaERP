<script setup lang="ts">
import type { Component } from 'vue';

withDefaults(
    defineProps<{
        title: string;
        description?: string;
        icon?: Component;
        compact?: boolean;
    }>(),
    { compact: false },
);
</script>

<template>
    <div
        :class="[
            'flex flex-col items-center justify-center text-center',
            compact ? 'py-8 px-6' : 'py-16 px-6',
        ]"
    >
        <div
            v-if="icon"
            class="flex h-10 w-10 items-center justify-center rounded-lg bg-surface-elevated mb-3"
        >
            <component :is="icon" class="h-5 w-5 text-muted-foreground" :stroke-width="1.5" />
        </div>
        <p class="text-sm font-medium">{{ title }}</p>
        <p v-if="description" class="mt-1 text-xs text-muted-foreground max-w-xs">
            {{ description }}
        </p>
        <div v-if="$slots.action" class="mt-4">
            <slot name="action" />
        </div>
    </div>
</template>
