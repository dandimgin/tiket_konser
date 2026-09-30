<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import QrTicketScannerModal from '@/Components/QrTicketScannerModal.vue';
import { api } from '@/services/api';
import { formatRupiah } from '@/stores/auth';

const stats = ref(null);
const loading = ref(true);
const error = ref(null);
const showScannerModal = ref(false);

async function fetchStats() {
    loading.value = true;
    error.value = null;
    try {
        const res = await api.getDashboard();
        stats.value = res.data;
    } catch (err) {
        error.value = err.message || 'Gagal memuat statistik dashboard admin.';
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    fetchStats();
});
</script>

<template>
    <AdminLayout>
        <Head title="Ringkasan Dashboard - Admin Tiketin" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-xs text-slate-400 font-medium mb-1">
                        <svg class="w-4 h-4 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        Sistem Pengelolaan &bull; Tiketin Admin
                    </div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Ringkasan Sistem</h1>
                    <p class="text-sm text-slate-500 mt-0.5">Statistik performa penjualan tiket, omzet, dan aktivitas pembayaran hari ini</p>
                </div>
                <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
                    <button
                        @click="showScannerModal = true"
                        type="button"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-white bg-sky-600 hover:bg-sky-700 border border-sky-600 rounded-lg transition shadow-sm"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                        Pindai QR Gate
                    </button>
                    <button
                        @click="fetchStats"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition shadow-sm"
                    >
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Segarkan Data
                    </button>
                    <button
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition shadow-sm"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        Export Laporan
                    </button>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 animate-pulse">
                <div v-for="n in 8" :key="n" class="p-5 bg-white border border-slate-200 rounded-xl space-y-2">
                    <div class="h-3 bg-slate-200 rounded w-1/2"></div>
                    <div class="h-6 bg-slate-100 rounded w-2/3"></div>
                </div>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="p-6 bg-rose-50 border border-rose-200 rounded-xl text-center max-w-lg mx-auto">
                <p class="text-sm font-bold text-rose-800 mb-2">{{ error }}</p>
                <button @click="fetchStats" class="px-4 py-2 text-xs font-semibold bg-white border border-rose-300 text-rose-700 rounded-lg hover:bg-rose-100 transition">
                    Coba Lagi
                </button>
            </div>

            <!-- Stats Content -->
            <div v-else-if="stats" class="space-y-5">
                <!-- KPI Cards Row 1: Primary Metrics -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Total Revenue (Harmonized) -->
                    <div class="p-5 bg-white border border-slate-200/80 rounded-2xl shadow-xs">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Pendapatan</span>
                            <div class="w-8 h-8 rounded-xl bg-sky-50 border border-sky-100 flex items-center justify-center text-sky-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                        <div class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                            {{ formatRupiah(stats.total_revenue) }}
                        </div>
                        <p class="text-xs text-slate-400 mt-1 flex items-center gap-1">
                            <svg class="w-3 h-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                            Akumulasi dari transaksi terverifikasi
                        </p>
                    </div>

                    <!-- Tiket Terjual -->
                    <div class="p-5 bg-white border border-slate-200/80 rounded-2xl shadow-xs">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Tiket Terjual</span>
                            <div class="w-8 h-8 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                            </div>
                        </div>
                        <div class="text-2xl font-extrabold text-slate-900 tracking-tight">
                            {{ stats.total_tickets_sold }} <span class="text-base font-semibold text-slate-400">Lembar</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">Total e-tiket resmi yang diterbitkan</p>
                    </div>

                    <!-- Total Orders -->
                    <div class="p-5 bg-white border border-slate-200/80 rounded-2xl shadow-xs">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Transaksi</span>
                            <div class="w-8 h-8 rounded-xl bg-sky-50 border border-sky-100 flex items-center justify-center text-sky-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            </div>
                        </div>
                        <div class="text-2xl font-extrabold text-slate-900 tracking-tight">
                            {{ stats.total_orders }} <span class="text-base font-semibold text-slate-400">Pesanan</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">Jumlah pesanan masuk dari seluruh customer</p>
                    </div>

                    <!-- Pending Payments (alert card) -->
                    <div
                        class="p-5 rounded-2xl border shadow-xs transition"
                        :class="stats.pending_payments > 0 ? 'bg-amber-50/70 border-amber-200' : 'bg-white border-slate-200/80'"
                    >
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-[11px] font-bold uppercase tracking-wider" :class="stats.pending_payments > 0 ? 'text-amber-800' : 'text-slate-400'">
                                Pembayaran Pending
                            </span>
                            <div class="w-8 h-8 rounded-xl flex items-center justify-center" :class="stats.pending_payments > 0 ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-400'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                        <div class="text-2xl font-extrabold tracking-tight" :class="stats.pending_payments > 0 ? 'text-amber-900' : 'text-slate-900'">
                            {{ stats.pending_payments }}
                        </div>
                        <div class="flex items-center justify-between mt-1">
                            <p class="text-xs" :class="stats.pending_payments > 0 ? 'text-amber-700' : 'text-slate-400'">
                                {{ stats.pending_payments > 0 ? 'Perlu verifikasi segera' : 'Semua terverifikasi' }}
                            </p>
                            <Link
                                href="/admin/payments"
                                class="text-xs font-bold underline"
                                :class="stats.pending_payments > 0 ? 'text-amber-900' : 'text-slate-500'"
                            >
                                Verifikasi →
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- KPI Row 2: Secondary Metrics -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                    <div class="p-4 bg-white border border-slate-200 rounded-xl">
                        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide mb-1">Total Event</p>
                        <p class="text-2xl font-bold text-slate-900">{{ stats.total_events }}</p>
                        <Link href="/admin/events" class="text-xs font-semibold text-sky-600 hover:text-sky-700 mt-1 inline-block transition">
                            Kelola →
                        </Link>
                    </div>
                    <div class="p-4 bg-white border border-slate-200 rounded-xl">
                        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide mb-1">Total Artis</p>
                        <p class="text-2xl font-bold text-slate-900">{{ stats.total_artists }}</p>
                        <Link href="/admin/artists" class="text-xs font-semibold text-sky-600 hover:text-sky-700 mt-1 inline-block transition">
                            Kelola Lineup →
                        </Link>
                    </div>
                    <div class="p-4 bg-white border border-slate-200 rounded-xl">
                        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide mb-1">Total Customer</p>
                        <p class="text-2xl font-bold text-slate-900">{{ stats.total_customers }}</p>
                        <span class="text-xs text-slate-400 mt-1 block">Pengguna terdaftar</span>
                    </div>
                    <div class="p-4 bg-white border border-slate-200 rounded-xl">
                        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide mb-1">Pesanan Pending</p>
                        <p class="text-2xl font-bold text-slate-900">{{ stats.pending_orders }}</p>
                        <Link href="/admin/orders" class="text-xs font-semibold text-sky-600 hover:text-sky-700 mt-1 inline-block transition">
                            Lihat Pesanan →
                        </Link>
                    </div>
                    <div class="p-4 bg-white border border-slate-200 rounded-xl">
                        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide mb-1">Conversion Rate</p>
                        <p class="text-2xl font-bold text-slate-900">
                            {{ stats.total_orders > 0 ? Math.round((stats.total_orders - (stats.pending_orders || 0)) / stats.total_orders * 100) : 0 }}%
                        </p>
                        <span class="text-xs text-emerald-600 font-semibold mt-1 block">Pesanan selesai</span>
                    </div>
                </div>

                <!-- Quick Navigation -->
                <div class="bg-white border border-slate-200 rounded-xl p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-bold text-slate-900">Navigasi Pengelolaan Cepat</h2>
                        <span class="text-xs text-slate-400">Admin Tools</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <Link
                            href="/admin/events"
                            class="flex items-center gap-3 p-4 rounded-xl border border-slate-200 hover:border-sky-300 hover:bg-sky-50/50 transition group"
                        >
                            <div class="w-10 h-10 rounded-lg bg-sky-50 border border-sky-100 flex items-center justify-center text-sky-600 group-hover:bg-sky-600 group-hover:text-white transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-900 group-hover:text-sky-700 transition">Kelola Event</p>
                                <p class="text-xs text-slate-500">Buat jadwal dan lokasi konser</p>
                            </div>
                        </Link>

                        <Link
                            href="/admin/artists"
                            class="flex items-center gap-3 p-4 rounded-xl border border-slate-200 hover:border-sky-300 hover:bg-sky-50/50 transition group"
                        >
                            <div class="w-10 h-10 rounded-lg bg-sky-50 border border-sky-100 flex items-center justify-center text-sky-600 group-hover:bg-sky-600 group-hover:text-white transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-900 group-hover:text-sky-700 transition">Kelola Artis</p>
                                <p class="text-xs text-slate-500">Daftar musisi dan vokalis</p>
                            </div>
                        </Link>

                        <Link
                            href="/admin/ticket-categories"
                            class="flex items-center gap-3 p-4 rounded-xl border border-slate-200 hover:border-sky-300 hover:bg-sky-50/50 transition group"
                        >
                            <div class="w-10 h-10 rounded-lg bg-sky-50 border border-sky-100 flex items-center justify-center text-sky-600 group-hover:bg-sky-600 group-hover:text-white transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-900 group-hover:text-sky-700 transition">Kategori Tiket</p>
                                <p class="text-xs text-slate-500">Atur harga dan kuota kursi</p>
                            </div>
                        </Link>

                        <Link
                            href="/admin/payments"
                            class="flex items-center gap-3 p-4 rounded-xl border border-slate-200 hover:border-amber-300 hover:bg-amber-50/50 transition group"
                            :class="stats.pending_payments > 0 ? 'border-amber-200 bg-amber-50/30' : ''"
                        >
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center transition"
                                :class="stats.pending_payments > 0
                                    ? 'bg-amber-100 text-amber-700 border border-amber-200 group-hover:bg-amber-600 group-hover:text-white'
                                    : 'bg-sky-50 border border-sky-100 text-sky-600 group-hover:bg-sky-600 group-hover:text-white'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold transition" :class="stats.pending_payments > 0 ? 'text-amber-900' : 'text-slate-900 group-hover:text-sky-700'">
                                    Verifikasi Pembayaran
                                    <span v-if="stats.pending_payments > 0" class="ml-1.5 px-1.5 py-0.5 bg-amber-600 text-white text-[10px] rounded-full font-bold">{{ stats.pending_payments }}</span>
                                </p>
                                <p class="text-xs text-slate-500">Periksa bukti transfer customer</p>
                            </div>
                        </Link>

                        <button
                            type="button"
                            @click="showScannerModal = true"
                            class="flex items-center gap-3 p-4 rounded-xl border border-sky-200 bg-sky-50/40 hover:bg-sky-100/60 hover:border-sky-300 transition group text-left"
                        >
                            <div class="w-10 h-10 rounded-lg bg-sky-600 text-white flex items-center justify-center shadow-sm group-hover:bg-sky-700 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-sky-950 group-hover:text-sky-700 transition">Scanner QR Gate</p>
                                <p class="text-xs text-sky-700/80">Validasi tiket penonton</p>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scanner Gate Modal -->
        <QrTicketScannerModal :show="showScannerModal" @close="showScannerModal = false" />
    </AdminLayout>
</template>
