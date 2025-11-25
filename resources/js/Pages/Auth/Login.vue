<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import AuthenticationCardLogo from "@/Components/AuthenticationCardLogo.vue";
import Checkbox from "@/Components/Checkbox.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        remember: form.remember ? "on" : "",
    })).post(route("login"), {
        onFinish: () => form.reset("password"),
    });
};
</script>

<template>
    <Head title="Iniciar sesión - Dilo Records" />

    <div
        class="min-h-screen flex items-center justify-center bg-[#000000] text-white relative overflow-hidden px-4 sm:px-0"
    >
        <!-- Fondo -->
        <div
            class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,#1d1d1b_0%,#000000_100%)] opacity-95"
        ></div>

        <!-- Card principal -->
        <div
            class="relative z-10 w-full max-w-sm sm:max-w-md p-6 sm:p-8 md:p-10 bg-[#1d1d1b]/90 backdrop-blur-md border border-[#2a2a2a] rounded-2xl shadow-2xl"
        >
            <!-- Logo -->
            <div class="flex justify-center mb-6 sm:mb-8">
                <AuthenticationCardLogo />
            </div>

            <!-- Mensaje de estado -->
            <div v-if="status" class="mb-4 text-sm text-green-400 bg-green-900/20 border border-green-700 rounded-lg p-3">
                {{ status }}
            </div>

            <!-- Formulario -->
            <form @submit.prevent="submit" class="space-y-5 sm:space-y-6">
                <div>
                    <InputLabel
                        for="email"
                        value="Correo electrónico"
                        class="text-gray-300 text-sm"
                    />
                    <TextInput
                        id="email"
                        v-model="form.email"
                        type="email"
                        class="mt-2 block w-full bg-[#111111] border border-[#2c2c2c] text-gray-100 focus:ring-[#ffa236] focus:border-[#ffa236] rounded-lg px-3 py-2 sm:py-3 text-sm sm:text-base"
                        required
                        autofocus
                        autocomplete="username"
                    />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div>
                    <InputLabel
                        for="password"
                        value="Contraseña"
                        class="text-gray-300 text-sm"
                    />
                    <TextInput
                        id="password"
                        v-model="form.password"
                        type="password"
                        class="mt-2 block w-full bg-[#111111] border border-[#2c2c2c] text-gray-100 focus:ring-[#ffa236] focus:border-[#ffa236] rounded-lg px-3 py-2 sm:py-3 text-sm sm:text-base"
                        required
                        autocomplete="current-password"
                    />
                    <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <div class="flex items-center justify-between flex-wrap gap-2">
                    <label class="flex items-center text-sm text-gray-400">
                        <Checkbox
                            v-model:checked="form.remember"
                            name="remember"
                        />
                        <span class="ml-2">Recordarme</span>
                    </label>

                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-sm text-[#ffa236] hover:text-[#ffb54d] transition-colors"
                    >
                        ¿Olvidaste tu contraseña?
                    </Link>
                </div>

                <PrimaryButton
                    class="w-full justify-center py-2.5 sm:py-3 bg-[#ffa236] text-black font-semibold rounded-lg hover:bg-[#ffb54d] focus:ring-4 focus:ring-[#ffa236]/40 transition-all duration-300 text-sm sm:text-base"
                    :class="{ 'opacity-50': form.processing }"
                    :disabled="form.processing"
                >
                    Iniciar sesión
                </PrimaryButton>
            </form>

            <p class="text-center text-xs text-gray-500 mt-6 sm:mt-8">
                © 2025 Dilo Records
            </p>
        </div>
    </div>
</template>