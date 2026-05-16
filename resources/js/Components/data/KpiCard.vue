<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    label: string;
    value: string;
    helper?: string;
    icon?: Component;
}>();
</script>

<template>
    <div class="rounded-xl border border-border bg-surface/40 p-5">
        <div class="flex items-start justify-between">
            <span class="text-xs uppercase tracking-wide text-muted-foreground">
                {{ label }}
            </span>
            <component
                v-if="icon"
                :is="icon"
                class="h-4 w-4 text-muted-foreground"
                :stroke-width="1.5"
            />
        </div>
        <p class="mt-3 text-2xl font-semibold font-mono tracking-tight tabular-nums">
            {{ value }}
        </p>
        <p v-if="helper" class="mt-1 text-xs text-muted-foreground">
            {{ helper }}
        </p>
    </div>
</template>
