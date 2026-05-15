import { computed, reactive, readonly } from 'vue';

export interface CartProduct {
    id: number;
    sku: string;
    name: string;
    price: number;
    stock: number;
}

export interface CartCustomer {
    id: number;
    name: string;
    document: string;
}

export interface CartLine {
    product: CartProduct;
    quantity: number;
}

interface CartState {
    customer: CartCustomer | null;
    lines: CartLine[];
}

export function useSaleCart() {
    const state = reactive<CartState>({
        customer: null,
        lines: [],
    });

    function addProduct(product: CartProduct, quantity = 1) {
        const existing = state.lines.find((l) => l.product.id === product.id);
        const desired = (existing?.quantity ?? 0) + quantity;
        const capped = Math.min(desired, product.stock);
        if (capped <= 0) return;

        if (existing) {
            existing.quantity = capped;
        } else {
            state.lines.push({ product, quantity: capped });
        }
    }

    function setQuantity(productId: number, quantity: number) {
        const line = state.lines.find((l) => l.product.id === productId);
        if (!line) return;
        const capped = Math.max(0, Math.min(quantity, line.product.stock));
        if (capped === 0) {
            removeProduct(productId);
        } else {
            line.quantity = capped;
        }
    }

    function increment(productId: number) {
        const line = state.lines.find((l) => l.product.id === productId);
        if (!line) return;
        if (line.quantity < line.product.stock) line.quantity += 1;
    }

    function decrement(productId: number) {
        const line = state.lines.find((l) => l.product.id === productId);
        if (!line) return;
        if (line.quantity <= 1) removeProduct(productId);
        else line.quantity -= 1;
    }

    function removeProduct(productId: number) {
        const idx = state.lines.findIndex((l) => l.product.id === productId);
        if (idx !== -1) state.lines.splice(idx, 1);
    }

    function clear() {
        state.lines.splice(0, state.lines.length);
        state.customer = null;
    }

    function setCustomer(customer: CartCustomer | null) {
        state.customer = customer;
    }

    const subtotal = computed(() =>
        state.lines.reduce((acc, l) => acc + l.product.price * l.quantity, 0),
    );
    const total = computed(() => subtotal.value);
    const itemCount = computed(() =>
        state.lines.reduce((acc, l) => acc + l.quantity, 0),
    );
    const isEmpty = computed(() => state.lines.length === 0);

    return {
        state: readonly(state),
        addProduct,
        setQuantity,
        increment,
        decrement,
        removeProduct,
        clear,
        setCustomer,
        subtotal,
        total,
        itemCount,
        isEmpty,
    };
}
