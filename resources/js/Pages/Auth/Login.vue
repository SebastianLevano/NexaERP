<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import { Loader2 } from 'lucide-vue-next';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <Head title="Iniciar sesión" />

    <div class="min-h-screen flex items-center justify-center bg-background px-4">
        <!-- subtle ambient glow -->
        <div
            class="pointer-events-none fixed inset-0 -z-10 overflow-hidden"
            aria-hidden="true"
        >
            <div
                class="absolute top-1/3 left-1/2 -translate-x-1/2 h-[420px] w-[640px] rounded-full blur-[140px] opacity-30"
                style="background: radial-gradient(closest-side, oklch(0.55 0.218 285), transparent)"
            />
        </div>

        <div class="w-full max-w-sm">
            <div class="mb-8 flex flex-col items-center text-center">
                <div
                    class="mb-4 flex h-10 w-10 items-center justify-center rounded-xl bg-accent text-accent-foreground text-base font-bold"
                >
                    N
                </div>
                <h1 class="text-xl font-semibold tracking-tight">
                    Bienvenido a NexaERP
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Ingresa con tu cuenta para continuar
                </p>
            </div>

            <form
                @submit.prevent="submit"
                class="rounded-xl border border-border bg-surface/50 backdrop-blur p-6 shadow-2xl shadow-black/30"
            >
                <div class="space-y-4">
                    <div class="space-y-1.5">
                        <Label for="email">Correo electrónico</Label>
                        <Input
                            id="email"
                            type="email"
                            autocomplete="email"
                            v-model="form.email"
                            placeholder="tu@empresa.com"
                            autofocus
                        />
                        <p v-if="form.errors.email" class="text-xs text-destructive">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="password">Contraseña</Label>
                        <Input
                            id="password"
                            type="password"
                            autocomplete="current-password"
                            v-model="form.password"
                            placeholder="••••••••"
                        />
                        <p v-if="form.errors.password" class="text-xs text-destructive">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <label class="flex items-center gap-2 text-xs text-muted-foreground cursor-pointer">
                        <input
                            type="checkbox"
                            v-model="form.remember"
                            class="h-3.5 w-3.5 rounded border-border bg-surface text-accent focus:ring-ring/40"
                        />
                        Mantener sesión iniciada
                    </label>

                    <Button
                        type="submit"
                        size="lg"
                        class="w-full"
                        :disabled="form.processing"
                    >
                        <Loader2
                            v-if="form.processing"
                            class="h-4 w-4 animate-spin"
                            :stroke-width="1.5"
                        />
                        <span>Iniciar sesión</span>
                    </Button>
                </div>
            </form>

            <p class="mt-6 text-center text-xs text-muted-foreground">
                ¿Problemas para entrar? Contacta al administrador del sistema.
            </p>
        </div>
    </div>
</template>
