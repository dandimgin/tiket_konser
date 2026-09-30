<script setup>
import { reactive, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { auth, getRoleName } from '@/stores/auth';
import { useToast } from '@/composables/useToast';
import ToastContainer from '@/Components/ToastContainer.vue';

const toast = useToast();

const form = reactive({
    email: '',
    password: '',
});

const errors = ref({});
const submitting = ref(false);
const showPassword = ref(false);

async function handleLogin() {
    submitting.value = true;
    errors.value = {};

    try {
        const res = await auth.login(form);
        const role = getRoleName(res.user);
        toast.success(`Selamat datang kembali, ${res.user.name}!`);

        if (role === 'Admin') {
            router.visit('/admin/dashboard');
        } else {
            router.visit('/');
        }
    } catch (err) {
        if (err.status === 422) {
            errors.value = err.errors || {};
        } else {
            errors.value = { general: err.message || 'Login gagal. Periksa kembali email dan kata sandi Anda.' };
        }
        toast.error(errors.value.general || 'Gagal masuk akun');
    } finally {
        submitting.value = false;
    }
}

function fillAdmin() {
    form.email = 'admin@tiketkonser.test';
    form.password = 'admin12345';
}
</script>

<template>
    <div class="min-h-screen bg-zinc-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-sans">
        <Head title="Masuk Akun - Tiketin" />

        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <Link href="/" class="inline-flex items-center justify-center mb-1">
                <img
                    src="/images/tiketin-logo.png"
                    alt="Tiketin"
                    class="h-10 w-auto object-contain"
                />
            </Link>
            <h1 class="mt-6 text-center text-xl font-bold tracking-tight text-slate-900">
                Masuk ke Akun Anda
            </h1>
            <p class="mt-1 text-center text-xs text-slate-500">
                Belum memiliki akun?
                <Link href="/register" class="font-semibold text-sky-600 underline hover:text-sky-700 ml-1">
                    Daftar di sini
                </Link>
            </p>
        </div>

        <div class="mt-6 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0">
            <div class="bg-white py-8 px-6 sm:px-8 shadow-xs rounded-2xl border border-slate-200/90">
                <!-- General Error Banner -->
                <div v-if="errors.general" class="mb-5 p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs font-semibold text-rose-700">
                    {{ errors.general }}
                </div>

                <form @submit.prevent="handleLogin" class="space-y-4">
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
                        <div class="flex items-center justify-between mb-1">
                            <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                Kata Sandi
                            </label>
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="text-xs text-slate-400 hover:text-slate-700"
                            >
                                {{ showPassword ? 'Sembunyikan' : 'Tampilkan' }}
                            </button>
                        </div>
                        <input
                            id="password"
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            autocomplete="current-password"
                            required
                            placeholder="••••••••"
                            class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-900 focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100 transition"
                            :class="{ 'border-rose-400': errors.password }"
                        />
                        <p v-if="errors.password" class="mt-1 text-xs text-rose-600">{{ errors.password[0] }}</p>
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
                            <span>{{ submitting ? 'Memproses...' : 'Masuk ke Akun' }}</span>
                        </button>
                    </div>
                </form>

                <!-- Quick Fill Test Account -->
                <div class="mt-6 pt-5 border-t border-slate-100">
                    <p class="text-[11px] text-slate-400 text-center uppercase tracking-wider mb-2 font-semibold">
                        Akses Cepat Pengujian
                    </p>
                    <button
                        type="button"
                        @click="fillAdmin"
                        class="w-full py-2.5 px-3 bg-slate-50 border border-slate-200 text-slate-700 hover:bg-slate-100 hover:border-slate-300 rounded-xl text-xs font-semibold transition text-center"
                    >
                        Isi Akun Admin (admin@tiketkonser.test)
                    </button>
                </div>
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
