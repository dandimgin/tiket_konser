<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { auth } from '@/stores/auth';
import { searchQuery, selectedCategory } from '@/stores/search';

const mobileMenuOpen = ref(false);
const userDropdownOpen = ref(false);
const categoryDropdownOpen = ref(false);

const page = usePage();

const categories = [
    { id: '', name: 'Semua Kategori' },
    { id: 'konser', name: 'Konser Musik' },
    { id: 'festival', name: 'Festival Musik' },
    { id: 'pop', name: 'Pop & Band' },
    { id: 'indie', name: 'Indie & Jazz' },
];

// Close dropdowns when clicking outside
function handleClickOutside(event) {
    const el = event.target;
    if (!el.closest('.user-dropdown-wrapper')) {
        userDropdownOpen.value = false;
    }
}

if (typeof window !== 'undefined') {
    document.addEventListener('click', handleClickOutside);
}

const navLinks = [
    { href: '/', label: 'Jelajah Event' },
    { href: '/#katalog-event', label: 'Event Populer' },
    { href: '/orders', label: 'Pesanan Saya', requireAuth: true },
    { href: '/tickets', label: 'Tiket Saya', requireAuth: true },
];

const currentUrl = computed(() => page.url);

function isActiveLink(href) {
    if (href === '/') return currentUrl.value === '/';
    return currentUrl.value.startsWith(href);
}

function selectCategory(catId) {
    selectedCategory.value = catId;
    categoryDropdownOpen.value = false;
    if (page.url !== '/') {
        router.visit('/');
    }
}

function handleSearchSubmit() {
    if (page.url !== '/') {
        router.visit('/');
    }
}

function handleLogout() {
    userDropdownOpen.value = false;
    mobileMenuOpen.value = false;
    auth.logout();
}
</script>

<template>
    <header class="sticky top-0 z-40 w-full px-3 sm:px-6 lg:px-8 pt-3 pb-1 pointer-events-none">
        <div class="max-w-7xl mx-auto bg-white/85 backdrop-blur-md border border-slate-200/70 shadow-[0_4px_24px_-4px_rgba(0,0,0,0.05)] rounded-2xl px-4 sm:px-5 pointer-events-auto transition-all duration-200">
            <div class="flex items-center justify-between h-15 sm:h-16 gap-3 sm:gap-4">
                <!-- Logo -->
                <Link
                    href="/"
                    class="flex items-center gap-2 shrink-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 rounded-lg"
                >
                    <img
                        src="/images/tiketin-logo.png"
                        alt="Tiketin"
                        class="h-7 sm:h-8 w-auto object-contain"
                    />
                </Link>

                <!-- Search Bar (Center) -->
                <div class="hidden sm:flex relative grow max-w-xs lg:max-w-sm items-center">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input
                        v-model="searchQuery"
                        type="text"
                        @keydown.enter="handleSearchSubmit"
                        placeholder="Cari artis, konser, venue..."
                        class="w-full pl-9.5 pr-4 py-1.5 sm:py-2 bg-slate-50/80 hover:bg-slate-100/80 focus:bg-white border border-slate-200/60 focus:border-sky-300 rounded-xl text-xs sm:text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-100 transition"
                    />
                </div>

                <!-- Navigation Links (Desktop) -->
                <nav class="hidden md:flex items-center gap-1">
                    <Link
                        href="/"
                        class="px-3.5 py-1.5 rounded-lg text-xs sm:text-sm font-medium transition"
                        :class="isActiveLink('/') && !isActiveLink('/orders') && !isActiveLink('/tickets')
                            ? 'text-sky-700 bg-sky-50/90 font-semibold'
                            : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50/80'"
                    >
                        Jelajah Event
                    </Link>
                    <Link
                        href="/#katalog-event"
                        class="px-3.5 py-1.5 rounded-lg text-xs sm:text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-50/80 transition"
                    >
                        Event Populer
                    </Link>
                    <Link
                        v-if="auth.isAuthenticated.value"
                        href="/orders"
                        class="px-3.5 py-1.5 rounded-lg text-xs sm:text-sm font-medium transition"
                        :class="isActiveLink('/orders') || isActiveLink('/tickets') ? 'text-sky-700 bg-sky-50/90 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50/80'"
                    >
                        Pesanan & E-Tiket
                    </Link>
                </nav>

                <!-- User Account -->
                <div class="hidden md:flex items-center gap-2 shrink-0">
                    <!-- Authenticated User -->
                    <div v-if="auth.isAuthenticated.value" class="relative user-dropdown-wrapper">
                        <button
                            type="button"
                            @click="userDropdownOpen = !userDropdownOpen"
                            class="flex items-center gap-2.5 pl-2 pr-3 py-1.5 rounded-lg hover:bg-slate-100 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
                        >
                            <div class="w-8 h-8 rounded-full bg-sky-600 text-white flex items-center justify-center font-bold text-sm shrink-0">
                                {{ auth.user.value?.name?.charAt(0)?.toUpperCase() || 'U' }}
                            </div>
                            <div class="text-left hidden lg:block">
                                <p class="text-xs font-bold text-slate-900 leading-tight truncate max-w-[100px]">{{ auth.user.value?.name }}</p>
                                <p class="text-[10px] text-slate-400">Verified Member</p>
                            </div>
                            <svg class="w-3.5 h-3.5 text-slate-400 transition-transform" :class="{ 'rotate-180': userDropdownOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <!-- Dropdown -->
                        <div
                            v-if="userDropdownOpen"
                            class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-lg border border-slate-200 py-1.5 z-50"
                        >
                            <div class="px-4 py-3 border-b border-slate-100">
                                <p class="text-sm font-bold text-slate-900 truncate">{{ auth.user.value?.name }}</p>
                                <p class="text-xs text-slate-500 truncate mt-0.5">{{ auth.user.value?.email }}</p>
                                <span class="inline-block mt-1.5 px-2 py-0.5 rounded text-[10px] font-semibold bg-sky-50 text-sky-700 border border-sky-100">
                                    {{ auth.role.value }}
                                </span>
                            </div>

                            <Link
                                v-if="auth.isAdmin.value"
                                href="/admin/dashboard"
                                @click="userDropdownOpen = false"
                                class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-800 hover:bg-slate-50 hover:text-sky-700 font-semibold transition"
                            >
                                Panel Admin
                            </Link>
                            <Link
                                href="/orders"
                                @click="userDropdownOpen = false"
                                class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 hover:text-sky-700 transition"
                            >
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                                </svg>
                                Pesanan & E-Tiket Saya
                            </Link>

                            <div class="border-t border-slate-100 my-1"></div>
                            <button
                                type="button"
                                @click="handleLogout"
                                class="w-full text-left px-4 py-2.5 text-sm text-rose-600 hover:bg-rose-50 font-medium transition"
                            >
                                Keluar
                            </button>
                        </div>
                    </div>

                    <!-- Guest -->
                    <template v-else>
                        <Link
                            href="/login"
                            class="px-4 py-2 text-sm font-semibold text-slate-700 border border-slate-200 rounded-lg hover:bg-slate-50 transition"
                        >
                            Masuk
                        </Link>
                        <Link
                            href="/register"
                            class="px-4 py-2 text-sm font-semibold text-white bg-sky-600 hover:bg-sky-700 rounded-lg transition shadow-sm"
                        >
                            Daftar
                        </Link>
                    </template>
                </div>

                <!-- Mobile menu toggle -->
                <button
                    @click="mobileMenuOpen = !mobileMenuOpen"
                    class="md:hidden p-2 rounded-xl text-slate-700 hover:bg-slate-100/80 min-w-[40px] min-h-[40px] flex items-center justify-center focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
                    aria-label="Menu"
                >
                    <svg v-if="!mobileMenuOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Mobile Search Bar (Inside floating bar) -->
            <div class="sm:hidden pb-3 pt-1">
                <div class="relative flex items-center">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari artis, konser, venue..."
                        class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200/70 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 transition"
                    />
                </div>
            </div>
        </div>

        <!-- Mobile Drawer (Liquid Glass Floating Panel) -->
        <div
            v-if="mobileMenuOpen"
            class="md:hidden max-w-7xl mx-auto mt-2 bg-white/95 backdrop-blur-md border border-slate-200/80 rounded-2xl shadow-xl px-4 pt-3 pb-5 space-y-1 pointer-events-auto transition-all"
        >
            <Link href="/" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-xl text-sm font-medium text-slate-800 hover:bg-slate-50 transition">
                Jelajah Event
            </Link>
            <Link href="/#katalog-event" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-xl text-sm font-medium text-slate-600 hover:bg-slate-50 transition">
                Event Populer
            </Link>

            <template v-if="auth.isAuthenticated.value">
                <Link href="/orders" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-xl text-sm font-medium text-slate-800 hover:bg-slate-50 transition">
                    Pesanan & E-Tiket
                </Link>
                <Link v-if="auth.isAdmin.value" href="/admin/dashboard" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-xl text-sm font-semibold bg-slate-900 text-white transition">
                    Panel Admin
                </Link>

                <div class="pt-3 border-t border-slate-100">
                    <div class="flex items-center justify-between px-3 py-2">
                        <div>
                            <p class="text-sm font-bold text-slate-900">{{ auth.user.value?.name }}</p>
                            <p class="text-xs text-slate-400">{{ auth.user.value?.email }}</p>
                        </div>
                        <button @click="handleLogout" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-rose-600 hover:bg-rose-50 transition">
                            Keluar
                        </button>
                    </div>
                </div>
            </template>

            <template v-else>
                <div class="pt-2 grid grid-cols-2 gap-2">
                    <Link href="/login" @click="mobileMenuOpen = false" class="text-center px-4 py-2.5 text-xs font-semibold text-slate-700 border border-slate-200 rounded-xl hover:bg-slate-50 transition">
                        Masuk
                    </Link>
                    <Link href="/register" @click="mobileMenuOpen = false" class="text-center px-4 py-2.5 text-xs font-semibold text-white bg-sky-600 hover:bg-sky-700 rounded-xl transition">
                        Daftar
                    </Link>
                </div>
            </template>
        </div>
    </header>
</template>
