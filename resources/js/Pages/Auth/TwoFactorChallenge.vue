<script setup>
import { nextTick, ref } from "vue";
import { Head, useForm } from "@inertiajs/vue3";
import AuthenticationCardLogo from "@/Components/AuthenticationCardLogo.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";

const recovery = ref(false);

const form = useForm({
    code: "",
    recovery_code: "",
});

const recoveryCodeInput = ref(null);
const codeInput = ref(null);

const toggleRecovery = async () => {
    recovery.value ^= true;

    await nextTick();

    if (recovery.value) {
        recoveryCodeInput.value.focus();
        form.code = "";
    } else {
        codeInput.value.focus();
        form.recovery_code = "";
    }
};

const submit = () => {
    form.post(route("two-factor.login"));
};
</script>

<template>
    <Head title="Autenticación de dos factores - Dilo Records" />

    <div
        class="min-h-screen flex items-center justify-center bg-[#000000] text-white relative overflow-hidden px-4 sm:px-0"
    >
        <!-- Fondo -->
        <div
            class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,#1d1d1b_0%,#000000_100%)] opacity-95"
        ></div>

        <!-- Card -->
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
                Autenticación de dos factores
            </h2>

            <div
                class="mb-6 text-sm text-gray-400 text-center leading-relaxed"
            >
                <template v-if="!recovery">
                    Ingresa el código de autenticación generado por tu aplicación
                    de seguridad para confirmar tu acceso.
                </template>
                <template v-else>
                    Ingresa uno de tus <strong>códigos de recuperación</strong> para acceder a tu cuenta.
                </template>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div v-if="!recovery">
                    <InputLabel
                        for="code"
                        value="Código de autenticación"
                        class="text-gray-300 text-sm"
                    />
                    <TextInput
                        id="code"
                        ref="codeInput"
                        v-model="form.code"
                        type="text"
                        inputmode="numeric"
                        class="mt-2 block w-full bg-[#111111] border border-[#2c2c2c] text-gray-100 focus:ring-[#ffa236] focus:border-[#ffa236] rounded-lg px-3 py-2 sm:py-3 text-sm sm:text-base tracking-widest text-center"
                        required
                        autofocus
                        autocomplete="one-time-code"
                    />
                    <InputError class="mt-2" :message="form.errors.code" />
                </div>

                <div v-else>
                    <InputLabel
                        for="recovery_code"
                        value="Código de recuperación"
                        class="text-gray-300 text-sm"
                    />
                    <TextInput
                        id="recovery_code"
                        ref="recoveryCodeInput"
                        v-model="form.recovery_code"
                        type="text"
                        class="mt-2 block w-full bg-[#111111] border border-[#2c2c2c] text-gray-100 focus:ring-[#ffa236] focus:border-[#ffa236] rounded-lg px-3 py-2 sm:py-3 text-sm sm:text-base tracking-wider text-center"
                        required
                        autocomplete="one-time-code"
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.recovery_code"
                    />
                </div>

                <div
                    class="flex items-center justify-between flex-wrap gap-3 mt-6"
                >
                    <button
                        type="button"
                        class="text-sm text-[#ffa236] hover:text-[#ffb54d] underline transition-colors"
                        @click.prevent="toggleRecovery"
                    >
                        <template v-if="!recovery">
                            Usar un código de recuperación
                        </template>
                        <template v-else>
                            Usar un código de autenticación
                        </template>
                    </button>

                    <PrimaryButton
                        class="w-full sm:w-auto justify-center py-2.5 sm:py-3 bg-[#ffa236] text-black font-semibold rounded-lg hover:bg-[#ffb54d] focus:ring-4 focus:ring-[#ffa236]/40 transition-all duration-300 text-sm sm:text-base"
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
