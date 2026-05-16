<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Card from '@/Components/ui/Card.vue';
import Badge from '@/Components/ui/Badge.vue';
import Kbd from '@/Components/ui/Kbd.vue';
import Skeleton from '@/Components/ui/Skeleton.vue';
import EmptyState from '@/Components/data/EmptyState.vue';
import PaymentDialog from '@/Components/sales/PaymentDialog.vue';
import { useSaleCart, type CartProduct, type CartCustomer } from '@/composables/useSaleCart';
import { useShortcuts } from '@/composables/useShortcuts';
import { formatCurrency } from '@/lib/formatters';
import {
    Search,
    Plus,
    Minus,
    Trash2,
    ShoppingCart,
    UserPlus,
    X,
} from 'lucide-vue-next';

const props = defineProps<{
    paymentMethods: { value: string; label: string }[];
}>();

const cart = useSaleCart();

// ── Productos ────────────────────────────────────────────────────────────────
const searchInput = ref<HTMLInputElement | null>(null);
const searchTerm = ref('');
const products = ref<CartProduct[]>([]);
const loadingProducts = ref(false);

async function loadProducts() {
    loadingProducts.value = true;
    try {
        const { data } = await axios.get<{ data: CartProduct[] }>('/api/products/search', {
            params: { q: searchTerm.value },
        });
        products.value = data.data;
    } finally {
        loadingProducts.value = false;
    }
}

let searchTimer: ReturnType<typeof setTimeout> | null = null;
watch(searchTerm, () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(loadProducts, 250);
});

onMounted(() => {
    loadProducts();
    setTimeout(() => searchInput.value?.focus(), 50);
});

// ── Clientes ─────────────────────────────────────────────────────────────────
const customerInput = ref('');
const customerResults = ref<CartCustomer[]>([]);
const customerOpen = ref(false);
const loadingCustomers = ref(false);

async function searchCustomers() {
    loadingCustomers.value = true;
    try {
        const { data } = await axios.get<{ data: CartCustomer[] }>('/api/customers/search', {
            params: { q: customerInput.value },
        });
        customerResults.value = data.data;
    } finally {
        loadingCustomers.value = false;
    }
}

let customerTimer: ReturnType<typeof setTimeout> | null = null;
watch(customerInput, () => {
    if (customerTimer) clearTimeout(customerTimer);
    customerTimer = setTimeout(searchCustomers, 250);
});

function pickCustomer(c: CartCustomer) {
    cart.setCustomer(c);
    customerOpen.value = false;
    customerInput.value = '';
    customerResults.value = [];
}

function clearCustomer() {
    cart.setCustomer(null);
}

// ── Atajos ───────────────────────────────────────────────────────────────────
const paymentOpen = ref(false);

useShortcuts([
    {
        key: 'k',
        mod: true,
        handler: (e) => {
            e.preventDefault();
            searchInput.value?.focus();
            searchInput.value?.select();
        },
    },
    {
        key: 'enter',
        when: () =>
            !paymentOpen.value &&
            !cart.isEmpty.value &&
            !customerOpen.value &&
            document.activeElement === searchInput.value,
        handler: (e) => {
            const first = products.value[0];
            if (first) {
                e.preventDefault();
                cart.addProduct(first);
            }
        },
    },
]);

// ── Carrito ──────────────────────────────────────────────────────────────────
function onProductClick(product: CartProduct) {
    if (product.stock <= 0) return;
    cart.addProduct(product);
}

function openPayment() {
    if (!cart.isEmpty.value) paymentOpen.value = true;
}

function onPaymentSuccess() {
    cart.clear();
}
</script>

<template>
    <Head title="Punto de venta" />

    <AppLayout>
        <div class="flex flex-col lg:flex-row h-[calc(100vh-3.5rem)]">
            <!-- Columna izquierda: productos -->
            <div class="flex-1 min-w-0 flex flex-col border-b lg:border-b-0 lg:border-r border-border">
                <!-- Buscador -->
                <div class="p-4 border-b border-border">
                    <div class="relative">
                        <Search
                            class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground"
                            :stroke-width="1.5"
                        />
                        <Input
                            ref="searchInput"
                            v-model="searchTerm"
                            placeholder="Buscar producto por nombre o SKU..."
                            class="pl-9 h-11"
                        />
                        <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-1">
                            <Kbd>⌘</Kbd>
                            <Kbd>K</Kbd>
                        </div>
                    </div>
                </div>

                <!-- Grid de productos -->
                <div class="flex-1 overflow-y-auto p-4">
                    <div
                        v-if="loadingProducts && products.length === 0"
                        class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3"
                    >
                        <div
                            v-for="i in 8"
                            :key="i"
                            class="rounded-xl border border-border bg-surface/40 p-3 space-y-3"
                        >
                            <div class="flex items-start justify-between">
                                <Skeleton class="h-3 w-16" />
                                <Skeleton class="h-4 w-10" />
                            </div>
                            <Skeleton class="h-4 w-3/4" />
                            <Skeleton class="h-3 w-1/2" />
                            <Skeleton class="h-5 w-20" />
                        </div>
                    </div>
                    <div
                        v-else-if="products.length === 0"
                        class="text-center text-xs text-muted-foreground py-12"
                    >
                        No se encontraron productos para "{{ searchTerm }}".
                    </div>
                    <div v-else class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3">
                        <button
                            v-for="p in products"
                            :key="p.id"
                            type="button"
                            @click="onProductClick(p)"
                            :disabled="p.stock <= 0"
                            class="text-left rounded-xl border border-border bg-surface/40 p-3 transition-colors duration-150 hover:border-accent hover:bg-surface disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:border-border focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/40"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <span class="text-[10px] font-mono text-muted-foreground truncate">
                                    {{ p.sku }}
                                </span>
                                <Badge
                                    :variant="p.stock <= 0 ? 'danger' : p.stock < 5 ? 'warning' : 'success'"
                                >
                                    {{ p.stock }}
                                </Badge>
                            </div>
                            <h3 class="mt-2 text-sm font-medium leading-tight line-clamp-2">
                                {{ p.name }}
                            </h3>
                            <p
                                v-if="p.category"
                                class="mt-1 text-[11px] text-muted-foreground"
                            >
                                {{ p.category }}
                            </p>
                            <p class="mt-3 text-base font-mono font-medium">
                                {{ formatCurrency(p.price) }}
                            </p>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Columna derecha: carrito -->
            <aside class="w-full lg:w-[420px] shrink-0 flex flex-col bg-background/60">
                <!-- Cliente -->
                <div class="p-4 border-b border-border">
                    <div v-if="cart.state.customer" class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-[11px] uppercase tracking-wide text-muted-foreground">
                                Cliente
                            </p>
                            <p class="text-sm font-medium truncate mt-0.5">
                                {{ cart.state.customer.name }}
                            </p>
                            <p class="text-[11px] text-muted-foreground font-mono">
                                {{ cart.state.customer.document }}
                            </p>
                        </div>
                        <button
                            @click="clearCustomer"
                            class="p-1.5 rounded-md text-muted-foreground hover:bg-surface hover:text-foreground transition-colors"
                            title="Quitar cliente"
                        >
                            <X class="h-4 w-4" :stroke-width="1.5" />
                        </button>
                    </div>

                    <div v-else>
                        <button
                            v-if="!customerOpen"
                            type="button"
                            @click="customerOpen = true; setTimeout(() => searchCustomers(), 0)"
                            class="w-full flex items-center justify-center gap-2 h-10 rounded-lg border border-dashed border-border-strong text-xs text-muted-foreground hover:border-accent hover:text-foreground transition-colors"
                        >
                            <UserPlus class="h-4 w-4" :stroke-width="1.5" />
                            Agregar cliente (opcional)
                        </button>

                        <div v-else class="space-y-2">
                            <Input
                                v-model="customerInput"
                                placeholder="Nombre o documento..."
                                autofocus
                            />
                            <div
                                v-if="loadingCustomers"
                                class="text-xs text-muted-foreground py-2 text-center"
                            >
                                Buscando…
                            </div>
                            <div
                                v-else-if="customerResults.length"
                                class="rounded-lg border border-border bg-surface max-h-48 overflow-y-auto"
                            >
                                <button
                                    v-for="c in customerResults"
                                    :key="c.id"
                                    type="button"
                                    @click="pickCustomer(c)"
                                    class="w-full text-left px-3 py-2 hover:bg-surface-elevated transition-colors border-b border-border/60 last:border-b-0"
                                >
                                    <p class="text-sm font-medium truncate">
                                        {{ c.name }}
                                    </p>
                                    <p class="text-[11px] text-muted-foreground font-mono">
                                        {{ c.document }}
                                    </p>
                                </button>
                            </div>
                            <button
                                type="button"
                                @click="customerOpen = false"
                                class="text-xs text-muted-foreground hover:text-foreground"
                            >
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Líneas -->
                <div class="flex-1 overflow-y-auto">
                    <EmptyState
                        v-if="cart.isEmpty.value"
                        :icon="ShoppingCart"
                        title="Carrito vacío"
                        description="Selecciona productos a la izquierda"
                    />
                    <ul v-else class="divide-y divide-border">
                        <li
                            v-for="line in cart.state.lines"
                            :key="line.product.id"
                            class="p-4"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium leading-tight line-clamp-2">
                                        {{ line.product.name }}
                                    </p>
                                    <p class="mt-0.5 text-[11px] font-mono text-muted-foreground">
                                        {{ line.product.sku }} · {{ formatCurrency(line.product.price) }}
                                    </p>
                                </div>
                                <button
                                    @click="cart.removeProduct(line.product.id)"
                                    class="p-1 rounded text-muted-foreground hover:text-destructive transition-colors"
                                >
                                    <Trash2 class="h-3.5 w-3.5" :stroke-width="1.5" />
                                </button>
                            </div>
                            <div class="mt-3 flex items-center justify-between">
                                <div class="flex items-center gap-1">
                                    <button
                                        @click="cart.decrement(line.product.id)"
                                        class="h-7 w-7 rounded-md border border-border bg-surface flex items-center justify-center hover:bg-surface-elevated"
                                    >
                                        <Minus class="h-3 w-3" :stroke-width="1.5" />
                                    </button>
                                    <span class="font-mono text-sm w-8 text-center">
                                        {{ line.quantity }}
                                    </span>
                                    <button
                                        @click="cart.increment(line.product.id)"
                                        :disabled="line.quantity >= line.product.stock"
                                        class="h-7 w-7 rounded-md border border-border bg-surface flex items-center justify-center hover:bg-surface-elevated disabled:opacity-30"
                                    >
                                        <Plus class="h-3 w-3" :stroke-width="1.5" />
                                    </button>
                                </div>
                                <span class="font-mono text-sm font-medium">
                                    {{ formatCurrency(line.product.price * line.quantity) }}
                                </span>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Total + acción -->
                <div class="border-t border-border p-4 space-y-3">
                    <div class="flex items-baseline justify-between">
                        <span class="text-xs uppercase tracking-wide text-muted-foreground">
                            Total
                        </span>
                        <span class="text-3xl font-semibold font-mono tabular-nums">
                            {{ formatCurrency(cart.total.value) }}
                        </span>
                    </div>
                    <Button
                        size="lg"
                        class="w-full"
                        :disabled="cart.isEmpty.value"
                        @click="openPayment"
                    >
                        Cobrar
                    </Button>
                </div>
            </aside>
        </div>

        <PaymentDialog
            v-model:open="paymentOpen"
            :total="cart.total.value"
            :lines="cart.state.lines"
            :customer="cart.state.customer"
            :paymentMethods="paymentMethods"
            @success="onPaymentSuccess"
        />
    </AppLayout>
</template>
