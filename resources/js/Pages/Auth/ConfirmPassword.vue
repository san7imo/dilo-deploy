<script setup>
import { ref } from "vue";
import { Head, useForm } from "@inertiajs/vue3";
import AuthenticationCardLogo from "@/Components/AuthenticationCardLogo.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";

const form = useForm({
    password: "",
});

const passwordInput = ref(null);

const submit = () => {
    form.post(route("password.confirm"), {
        onFinish: () => {
            form.reset();
            passwordInput.value.focus();
        },
    });
};
</script>

<template>
    <Head title="Área segura - Dilo Records" />

    <div
        class="min-h-screen flex items-center justify-center bg-[#000000] text-white relative overflow-hidden px-4 sm:px-0"
    >
        <!-- Fondo -->
        <div
            class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,#1d1d1b_0%,#000000_100%)] opacity-95"
        ></div>

        <!-- Card principal -->
        <div
            class="relative z-10 w-full max-w-sm sm:max-w-md p-6 sm:p-8 bg-[#1d1d1b]/90 backdrop-blur-md border border-[#2a2a2a] rounded-2xl shadow-2xl"
        >
            <!-- Logo -->
            <div class="flex justify-center mb-6 sm:mb-8">
                <AuthenticationCardLogo />
            </div>

            <h2
                class="text-center text-xl sm:text-2xl font-bold mb-6 text-[#ffa236]"
            >
                Confirmar contraseña
            </h2>

            <div
                class="mb-6 text-sm text-gray-400 text-center leading-relaxed"
            >
                Esta es un área segura de la aplicación.  
                Por favor confirma tu contraseña antes de continuar.
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <InputLabel
                        for="password"
                        value="Contraseña"
                        class="text-gray-300 text-sm"
                    />
                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="mt-2 block w-full bg-[#111111] border border-[#2c2c2c] text-gray-100 focus:ring-[#ffa236] focus:border-[#ffa236] rounded-lg px-3 py-2 sm:py-3 text-sm sm:text-base"
                        required
                        autocomplete="current-password"
                        autofocus
                    />
                    <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <div class="flex justify-center mt-6">
                    <PrimaryButton
                        class="w-full justify-center py-2.5 sm:py-3 bg-[#ffa236] text-black font-semibold rounded-lg hover:bg-[#ffb54d] focus:ring-4 focus:ring-[#ffa236]/40 transition-all duration-300 text-sm sm:text-base"
                        :class="{ 'opacity-50': form.processing }"
                        :disabled="form.processing"
                    >
                        Confirmar acceso
                    </PrimaryButton>
                </div>
            </form>

            <p class="text-center text-xs text-gray-500 mt-6 sm:mt-8">
                © 2025 Dilo Records
            </p>
        </div>
    </div>
</template>
