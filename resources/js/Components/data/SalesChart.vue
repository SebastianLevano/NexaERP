<script setup lang="ts">
import { computed, defineAsyncComponent } from 'vue';
import { formatCurrency } from '@/lib/formatters';

const ApexChart = defineAsyncComponent(() => import('vue3-apexcharts'));

const props = defineProps<{
    labels: string[];
    values: number[];
}>();

const series = computed(() => [
    {
        name: 'Ventas',
        data: props.values,
    },
]);

const options = computed(() => ({
    chart: {
        type: 'area',
        height: 260,
        toolbar: { show: false },
        zoom: { enabled: false },
        background: 'transparent',
        sparkline: { enabled: false },
        animations: { speed: 400 },
        fontFamily: 'Inter Variable, sans-serif',
    },
    theme: { mode: 'dark' },
    stroke: { curve: 'smooth', width: 2 },
    colors: ['#8B5CF6'],
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.4,
            opacityTo: 0.0,
            stops: [0, 90, 100],
        },
    },
    dataLabels: { enabled: false },
    grid: {
        borderColor: 'rgba(255,255,255,0.06)',
        strokeDashArray: 4,
        padding: { left: 8, right: 8 },
    },
    xaxis: {
        type: 'datetime',
        categories: props.labels,
        labels: {
            style: { colors: '#71717a', fontSize: '11px' },
            datetimeFormatter: { day: 'dd MMM' },
        },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: {
        labels: {
            style: { colors: '#71717a', fontSize: '11px' },
            formatter: (value: number) => formatCurrency(value),
        },
    },
    tooltip: {
        theme: 'dark',
        x: { format: 'dd MMM yyyy' },
        y: {
            formatter: (value: number) => formatCurrency(value),
            title: { formatter: () => 'Ventas:' },
        },
    },
}));
</script>

<template>
    <ApexChart
        type="area"
        height="260"
        :series="series"
        :options="options"
    />
</template>
