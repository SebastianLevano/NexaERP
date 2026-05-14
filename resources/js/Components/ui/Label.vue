<script setup lang="ts">
defineProps<{ for?: string }>();
</script>

<template>
    <label
        :for="for"
        class="text-xs font-medium uppercase tracking-wide text-muted-foreground"
    >
        <slot />
    </label>
</template>
