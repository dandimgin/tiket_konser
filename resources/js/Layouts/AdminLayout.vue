<script setup>
import { ref, onMounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { auth } from '@/stores/auth';
import ToastContainer from '@/Components/ToastContainer.vue';

const mobileSidebarOpen = ref(false);

const navigation = [
    {
        name: 'Dashboard',
        href: '/admin/dashboard',
        icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
        badge: null,
    },
    {
        name: 'Event',
        href: '/admin/events',
        icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        badge: null,
    },
    {
        name: 'Artist',
        href: '/admin/artists',
        icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
        badge: null,
    },
    {
        name: 'Kategori Tiket',
        href: '/admin/ticket-categories',
        icon: 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z',
        badge: null,
    },
    {
        name: 'Pesanan',
        href: '/admin/orders',
        icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
        badge: null,
    },
    {
        name: 'Pembayaran',
        href: '/admin/payments',
        icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
        badge: 'pending',
    },
];

function isCurrent(href) {
    if (typeof window === 'undefined') return false;
    return window.location.pathname.startsWith(href);
}

function handleLogout() {
    auth.logout();
}

onMounted(() => {
    if (!auth.isAuthenticated.value || !auth.isAdmin.value) {
        router.visit('/login');
    }
});
</script>

<template>
    <div class="min-h-screen bg-slate-50 flex font-sans">
        <!-- Sidebar Desktop -->
        <aside class="hidden lg:flex lg:flex-col w-60 bg-white border-r border-slate-200 shrink-0">
            <!-- Brand -->
            <div class="h-16 flex items-center gap-2.5 px-5 border-b border-slate-200">
                <img
                    src="/images/tiketin-logo.png"
                    alt="Tiketin"
                    class="h-7 w-auto object-contain"
                />
                <span class="text-[9px] font-bold text-sky-700 bg-sky-50 border border-sky-200 px-1.5 py-0.5 rounded">Admin</span>
            </div>

            <!-- Navigation Links -->
            <div class="p-3 space-y-0.5 grow overflow-y-auto">
                <p class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 mt-2">Menu Utama</p>
                <Link
                    v-for="item in navigation"
                    :key="item.name"
                    :href="item.href"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition group"
                    :class="isCurrent(item.href)
                        ? 'bg-sky-600 text-white shadow-sm'
                        : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'"
                >
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                    </svg>
                    <span class="grow">{{ item.name }}</span>
                </Link>
            </div>

            <!-- Bottom Actions -->
            <div class="p-3 border-t border-slate-200 space-y-1">
                <div class="flex items-center gap-3 px-3 py-2.5 mb-2">
                    <div class="w-8 h-8 rounded-full bg-sky-600 text-white flex items-center justify-center font-bold text-sm shrink-0">
                        {{ auth.user.value?.name?.charAt(0)?.toUpperCase() || 'A' }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-bold text-slate-900 truncate leading-tight">{{ auth.user.value?.name || 'Administrator' }}</p>
                        <p class="text-[10px] text-slate-400 truncate">{{ auth.user.value?.email }}</p>
                    </div>
                </div>
                <Link
                    href="/"
                    class="flex items-center justify-center gap-2 w-full px-3 py-2 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                    Kunjungi Website
                </Link>
                <button
                    @click="handleLogout"
                    class="flex items-center justify-center gap-2 w-full px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 rounded-lg transition"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Keluar (Logout)
                </button>
            </div>
        </aside>

        <!-- Main Wrapper -->
        <div class="grow flex flex-col min-w-0">
            <!-- Admin Topbar -->
            <header class="h-16 bg-white border-b border-slate-200 px-4 sm:px-6 flex items-center justify-between shrink-0">
                <!-- Mobile Menu Button -->
                <div class="flex items-center gap-3 lg:hidden">
                    <button
                        @click="mobileSidebarOpen = !mobileSidebarOpen"
                        class="p-2 rounded-lg text-slate-600 hover:bg-slate-100 min-w-[44px] min-h-[44px] flex items-center justify-center"
                        aria-label="Menu Admin"
                    >
                        <svg v-if="!mobileSidebarOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <Link href="/admin/dashboard" class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-sky-600 flex items-center justify-center">
                            <span class="text-white font-extrabold text-xs">TK</span>
                        </div>
                        <span class="font-bold text-slate-900 text-sm">Admin</span>
                    </Link>
                </div>

                <!-- Desktop header info -->
                <div class="hidden lg:flex items-center gap-2 text-xs text-slate-400">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 inline-block"></span>
                    Sistem aktif &bull; Tiketin Admin Panel
                </div>

                <!-- Admin Profile -->
                <div class="flex items-center gap-3">
                    <!-- Notification bell -->
                    <button type="button" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-600 relative transition" aria-label="Notifikasi">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        <span class="absolute top-1 right-1 w-2 h-2 rounded-full bg-rose-500 border border-white"></span>
                    </button>

                    <div class="text-right hidden sm:block">
                        <p class="text-xs font-bold text-slate-900 leading-tight">{{ auth.user.value?.name || 'Administrator' }}</p>
                        <p class="text-[10px] text-slate-400">{{ auth.user.value?.email }}</p>
                    </div>
                    <div class="w-8 h-8 rounded-full bg-sky-600 text-white flex items-center justify-center font-bold text-sm">
                        {{ auth.user.value?.name?.charAt(0).toUpperCase() || 'A' }}
                    </div>
                </div>
            </header>

            <!-- Mobile Drawer -->
            <div v-if="mobileSidebarOpen" class="lg:hidden fixed inset-0 z-50 flex">
                <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm" @click="mobileSidebarOpen = false"></div>
                <div class="relative bg-white w-64 max-w-[80vw] h-full flex flex-col z-10 shadow-xl">
                    <div class="h-16 flex items-center justify-between px-5 border-b border-slate-200">
                        <div class="flex items-center gap-2.5">
                            <img
                                src="/images/tiketin-logo.png"
                                alt="Tiketin"
                                class="h-6 w-auto object-contain"
                            />
                            <span class="text-[9px] font-bold text-sky-700 bg-sky-50 border border-sky-200 px-1.5 py-0.5 rounded">Admin</span>
                        </div>
                        <button @click="mobileSidebarOpen = false" class="p-1.5 text-slate-400 hover:text-slate-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="p-3 space-y-0.5 grow overflow-y-auto">
                        <Link
                            v-for="item in navigation"
                            :key="item.name"
                            :href="item.href"
                            @click="mobileSidebarOpen = false"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition"
                            :class="isCurrent(item.href)
                                ? 'bg-sky-600 text-white'
                                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                            </svg>
                            <span>{{ item.name }}</span>
                        </Link>
                    </div>
                    <div class="p-3 border-t border-slate-200 space-y-1">
                        <Link
                            href="/"
                            class="block text-center w-full px-3 py-2 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition"
                        >
                            Kunjungi Website
                        </Link>
                        <button
                            @click="handleLogout"
                            class="block text-center w-full px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 rounded-lg transition"
                        >
                            Keluar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Page Body -->
            <main class="grow p-4 sm:p-6 overflow-y-auto">
                <slot />
            </main>
        </div>

        <ToastContainer />
    </div>
</template>
