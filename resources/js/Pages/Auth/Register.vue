<script setup>
import { reactive, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { auth } from '@/stores/auth';
import { useToast } from '@/composables/useToast';
import ToastContainer from '@/Components/ToastContainer.vue';

const toast = useToast();

const form = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const errors = ref({});
const submitting = ref(false);

async function handleRegister() {
    submitting.value = true;
    errors.value = {};

    try {
        const res = await auth.register(form);
        toast.success(`Akun berhasil dibuat! Selamat datang, ${res.user.name}.`);
        router.visit('/');
    } catch (err) {
        if (err.status === 422) {
            errors.value = err.errors || {};
        } else {
            errors.value = { general: err.message || 'Pendaftaran gagal. Periksa kembali formulir Anda.' };
        }
        toast.error(errors.value.general || 'Gagal mendaftar');
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <div class="min-h-screen bg-zinc-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-sans">
        <Head title="Daftar Akun - Tiketin" />

        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <Link href="/" class="inline-flex items-center justify-center mb-1">
                <img
                    src="/images/tiketin-logo.png"
                    alt="Tiketin"
                    class="h-10 w-auto object-contain"
                />
            </Link>
            <h1 class="mt-6 text-center text-xl font-bold tracking-tight text-slate-900">
                Buat Akun Customer Baru
            </h1>
            <p class="mt-1 text-center text-xs text-slate-500">
                Sudah memiliki akun?
                <Link href="/login" class="font-semibold text-sky-600 underline hover:text-sky-700 ml-1">
                    Masuk di sini
                </Link>
            </p>
        </div>

        <div class="mt-6 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0">
            <div class="bg-white py-8 px-6 sm:px-8 shadow-xs rounded-2xl border border-slate-200/90">
                <!-- General Error Banner -->
                <div v-if="errors.general" class="mb-5 p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs font-semibold text-rose-700">
                    {{ errors.general }}
                </div>

                <form @submit.prevent="handleRegister" class="space-y-4">
                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                            Nama Lengkap
                        </label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="Contoh: Budi Santoso"
                            class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-900 focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100 transition"
                            :class="{ 'border-rose-400': errors.name }"
                        />
                        <p v-if="errors.name" class="mt-1 text-xs text-rose-600">{{ errors.name[0] }}</p>
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                            Alamat Email
                        </label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            autocomplete="email"
                            required
                            placeholder="nama@email.com"
                            class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-900 focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100 transition"
                            :class="{ 'border-rose-400': errors.email }"
                        />
                        <p v-if="errors.email" class="mt-1 text-xs text-rose-600">{{ errors.email[0] }}</p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                            Kata Sandi (Minimal 8 Karakter)
                        </label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            autocomplete="new-password"
                            required
                            placeholder="••••••••"
                            class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-900 focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100 transition"
                            :class="{ 'border-rose-400': errors.password }"
                        />
                        <p v-if="errors.password" class="mt-1 text-xs text-rose-600">{{ errors.password[0] }}</p>
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="password_confirmation" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                            Ulangi Kata Sandi
                        </label>
                        <input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            required
                            placeholder="••••••••"
                            class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-900 focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100 transition"
                        />
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button
                            type="submit"
                            :disabled="submitting"
                            class="w-full py-3 px-4 bg-sky-600 hover:bg-sky-700 disabled:bg-slate-200 disabled:text-slate-400 text-white font-bold text-sm rounded-xl transition flex items-center justify-center gap-2 shadow-xs focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
                        >
                            <svg v-if="submitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ submitting ? 'Mendaftarkan...' : 'Daftar Sekarang' }}</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Back to Home -->
            <p class="mt-6 text-center text-xs text-slate-400">
                <Link href="/" class="hover:text-sky-600 transition">
                    &larr; Kembali ke Beranda
                </Link>
            </p>
        </div>

        <ToastContainer />
    </div>
</template>
