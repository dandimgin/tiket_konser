<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import Badge from '@/Components/Badge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Modal from '@/Components/Modal.vue';
import { api } from '@/services/api';
import { auth, formatRupiah, formatDate } from '@/stores/auth';
import { useToast } from '@/composables/useToast';
import QRCode from 'qrcode';

const toast = useToast();

const orders = ref([]);
const events = ref([]);
const allTickets = ref([]);
const ticketQrs = ref({});

const loading = ref(true);
const error = ref(null);
const activeFilter = ref('all'); // 'all' | 'pending' | 'paid' | 'cancelled'
const searchQuery = ref('');

const showDetailModal = ref(false);
const selectedOrder = ref(null);
const copiedCode = ref(false);

// Order-specific E-Ticket Modal
const showTicketModal = ref(false);
const ticketOrder = ref(null);

// Fullscreen Gate Pass Modal
const showFullscreenQrModal = ref(false);
const selectedTicketFullscreen = ref(null);
const fullscreenQrUrl = ref('');
const countdown = ref(45);
let timerInterval = null;

const eventMap = computed(() => {
    const map = new Map();
    events.value.forEach(e => map.set(e.id, e));
    return map;
});

function getConcertForOrder(order) {
    if (!order) return null;
    const directEvent = order.order_items?.[0]?.ticket_category?.event;
    if (directEvent) return directEvent;
    const eventId = order.order_items?.[0]?.ticket_category?.event_id;
    if (eventId && eventMap.value.has(eventId)) {
        return eventMap.value.get(eventId);
    }
    return null;
}

function getTicketsForOrder(orderId) {
    if (!orderId) return [];
    return allTickets.value.filter(t => t.order_id === orderId);
}

async function generateQrsForTickets() {
    for (const t of allTickets.value) {
        if (!ticketQrs.value[t.id]) {
            try {
                ticketQrs.value[t.id] = await QRCode.toDataURL(t.ticket_code, {
                    width: 256,
                    margin: 1,
                    color: { dark: '#0f172a', light: '#ffffff' },
                    errorCorrectionLevel: 'M',
                });
            } catch (err) {
                console.error('QR Gen error:', err);
            }
        }
    }
}

async function fetchOrders() {
    loading.value = true;
    error.value = null;
    try {
        const [ordersRes, eventsRes, ticketsRes] = await Promise.all([
            api.getOrders(),
            api.getEvents().catch(() => ({ data: [] })),
            api.getTickets().catch(() => ({ data: [] })),
        ]);
        orders.value = ordersRes.data || [];
        events.value = eventsRes.data || [];
        allTickets.value = ticketsRes.data || [];
        await generateQrsForTickets();
    } catch (err) {
        error.value = err.message || 'Gagal memuat daftar pesanan Anda.';
    } finally {
        loading.value = false;
    }
}

const pendingCount = computed(() => orders.value.filter(o => o.status === 'pending').length);
const paidCount = computed(() => orders.value.filter(o => o.status === 'paid').length);
const cancelledCount = computed(() => orders.value.filter(o => o.status === 'cancelled').length);

const filteredOrders = computed(() => {
    let list = orders.value;
    if (activeFilter.value !== 'all') {
        list = list.filter(o => o.status === activeFilter.value);
    }
    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase().trim();
        list = list.filter(o => {
            const concert = getConcertForOrder(o);
            const matchConcert = concert?.name?.toLowerCase().includes(q) || concert?.location?.toLowerCase().includes(q);
            const matchCode = o.order_code?.toLowerCase().includes(q);
            const matchItem = o.order_items?.some(it =>
                it.ticket_category?.name?.toLowerCase().includes(q)
            );
            return matchConcert || matchCode || matchItem;
        });
    }
    return list;
});

function openDetail(order) {
    selectedOrder.value = order;
    showDetailModal.value = true;
}

function openOrderTickets(order) {
    ticketOrder.value = order;
    showTicketModal.value = true;
}

async function openFullscreenQr(ticket) {
    selectedTicketFullscreen.value = ticket;
    try {
        fullscreenQrUrl.value = await QRCode.toDataURL(ticket.ticket_code, {
            width: 360,
            margin: 2,
            color: { dark: '#0284c7', light: '#ffffff' },
            errorCorrectionLevel: 'H',
        });
    } catch (e) {
        fullscreenQrUrl.value = ticketQrs.value[ticket.id] || '';
    }
    showFullscreenQrModal.value = true;
    startSecurityTimer();
}

function startSecurityTimer() {
    countdown.value = 45;
    if (timerInterval) clearInterval(timerInterval);
    timerInterval = setInterval(() => {
        if (countdown.value > 1) {
            countdown.value--;
        } else {
            countdown.value = 45;
        }
    }, 1000);
}

function downloadTicketQr(ticket) {
    const qrData = ticketQrs.value[ticket.id] || fullscreenQrUrl.value;
    if (!qrData) return;
    const a = document.createElement('a');
    a.href = qrData;
    a.download = `Tiketin-Pass-${ticket.ticket_code}.png`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    toast?.success?.('QR Code E-Tiket berhasil diunduh.');
}

function shareToWhatsApp(ticket, concertName) {
    const text = encodeURIComponent(
        `Halo! Ini E-Tiket resmi saya di Tiketin:\n` +
        `Konser: ${concertName || 'Konser Musik'}\n` +
        `Kategori: ${ticket.ticket_category?.name || 'Tiket'}\n` +
        `Kode Tiket: ${ticket.ticket_code}\n` +
        `Tunjukkan QR code ini di gerbang masuk venue!`
    );
    window.open(`https://wa.me/?text=${text}`, '_blank');
}

function copyCode(code) {
    if (typeof navigator !== 'undefined' && navigator.clipboard) {
        navigator.clipboard.writeText(code);
        copiedCode.value = true;
        toast?.success?.('Kode pesanan disalin!');
        setTimeout(() => {
            copiedCode.value = false;
        }, 2000);
    }
}

onMounted(() => {
    if (auth.isAuthenticated.value) {
        fetchOrders();
    } else {
        loading.value = false;
    }
});
</script>

<template>
    <CustomerLayout>
        <Head title="Pesanan & E-Tiket - Tiketin" />

        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10">
            <!-- Breadcrumb Badge -->
            <div class="flex items-center gap-2 text-xs text-slate-400 font-medium mb-2">
                <svg class="w-4 h-4 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                Riwayat Transaksi & E-Tiket Resmi
            </div>

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Pesanan & E-Tiket</h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Kelola transaksi tiket konser, pantau status pembayaran, dan buka E-Tiket resmi Anda langsung dari setiap pesanan.
                    </p>
                </div>
                <div class="flex items-center gap-2.5 shrink-0">
                    <button
                        type="button"
                        @click="fetchOrders"
                        class="flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition shadow-sm"
                    >
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Segarkan Status
                    </button>
                    <Link
                        href="/"
                        class="flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-sky-700 bg-sky-50 border border-sky-200 rounded-lg hover:bg-sky-100 transition shadow-sm"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Beli Tiket Baru
                    </Link>
                </div>
            </div>

            <!-- If Not Logged In -->
            <div v-if="!auth.isAuthenticated.value" class="p-8 bg-white border border-slate-200 rounded-2xl text-center max-w-md mx-auto my-12 shadow-sm">
                <div class="w-12 h-12 rounded-xl bg-sky-50 border border-sky-100 flex items-center justify-center text-sky-600 mx-auto mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                </div>
                <h2 class="text-base font-bold text-slate-900 mb-1">Masuk untuk Melihat Riwayat Pesanan</h2>
                <p class="text-sm text-slate-500 mb-5">Silakan masuk dengan akun Anda untuk melihat status pemesanan tiket konser.</p>
                <Link
                    href="/login"
                    class="inline-block px-5 py-2.5 bg-sky-600 text-white rounded-xl text-sm font-bold hover:bg-sky-700 transition shadow-sm"
                >
                    Masuk Sekarang
                </Link>
            </div>

            <!-- Logged In Content -->
            <div v-else>
                <!-- Filters & Quick Search -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                    <!-- Segmented Tabs (Matches Tickets/Index) -->
                    <div class="flex items-center gap-1 bg-slate-100/90 rounded-xl p-1 overflow-x-auto w-full md:w-fit">
                        <button
                            type="button"
                            @click="activeFilter = 'all'"
                            class="px-4 py-2 rounded-lg text-xs font-semibold whitespace-nowrap transition"
                            :class="activeFilter === 'all'
                                ? 'bg-white text-slate-900 shadow-sm font-bold'
                                : 'text-slate-500 hover:text-slate-700'"
                        >
                            Semua Pesanan ({{ orders.length }})
                        </button>
                        <button
                            type="button"
                            @click="activeFilter = 'pending'"
                            class="px-4 py-2 rounded-lg text-xs font-semibold whitespace-nowrap transition flex items-center gap-1.5"
                            :class="activeFilter === 'pending'
                                ? 'bg-white text-slate-900 shadow-sm font-bold'
                                : 'text-slate-500 hover:text-slate-700'"
                        >
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            Menunggu Pembayaran ({{ pendingCount }})
                        </button>
                        <button
                            type="button"
                            @click="activeFilter = 'paid'"
                            class="px-4 py-2 rounded-lg text-xs font-semibold whitespace-nowrap transition flex items-center gap-1.5"
                            :class="activeFilter === 'paid'
                                ? 'bg-white text-slate-900 shadow-sm font-bold'
                                : 'text-slate-500 hover:text-slate-700'"
                        >
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            Lunas / Selesai ({{ paidCount }})
                        </button>
                        <button
                            type="button"
                            @click="activeFilter = 'cancelled'"
                            class="px-4 py-2 rounded-lg text-xs font-semibold whitespace-nowrap transition flex items-center gap-1.5"
                            :class="activeFilter === 'cancelled'
                                ? 'bg-white text-slate-900 shadow-sm font-bold'
                                : 'text-slate-500 hover:text-slate-700'"
                        >
                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                            Dibatalkan ({{ cancelledCount }})
                        </button>
                    </div>

                    <!-- Search Input -->
                    <div class="relative w-full md:w-64">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari nomor order / tiket..."
                            class="w-full pl-9 pr-3 py-2 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100 transition shadow-xs"
                        />
                    </div>
                </div>

                <!-- Two-Column Layout: Orders List + Informational Sidebar -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                    <!-- Orders List Column (2 cols) -->
                    <div class="lg:col-span-2 space-y-4">
                        <!-- Pending Alert Notice -->
                        <div
                            v-if="pendingCount > 0 && (activeFilter === 'all' || activeFilter === 'pending')"
                            class="p-4 bg-amber-50 border border-amber-200 rounded-2xl flex items-start justify-between gap-3 shadow-xs"
                        >
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center text-amber-700 shrink-0 mt-0.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-amber-900">Ada {{ pendingCount }} Transaksi Menunggu Pembayaran</p>
                                    <p class="text-[11px] text-amber-700 mt-0.5">
                                        Segera selesaikan transfer dan upload bukti pembayaran agar tiket tidak kedaluwarsa dan diterbitkan ke E-Tiket Anda.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Loading Skeleton -->
                        <div v-if="loading" class="space-y-4">
                            <div v-for="n in 3" :key="n" class="p-6 bg-white border border-slate-200 rounded-2xl animate-pulse space-y-4 shadow-sm">
                                <div class="flex justify-between items-center">
                                    <div class="h-5 bg-slate-200 rounded w-1/3"></div>
                                    <div class="h-6 bg-slate-100 rounded-lg w-20"></div>
                                </div>
                                <div class="h-16 bg-slate-50 rounded-xl"></div>
                                <div class="flex justify-between items-center pt-2">
                                    <div class="h-4 bg-slate-100 rounded w-1/4"></div>
                                    <div class="h-8 bg-slate-200 rounded-lg w-28"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Error State -->
                        <div v-else-if="error" class="p-6 bg-rose-50 border border-rose-200 rounded-2xl text-center max-w-md mx-auto shadow-sm">
                            <p class="text-xs font-bold text-rose-800 mb-2">{{ error }}</p>
                            <button
                                @click="fetchOrders"
                                class="px-4 py-2 text-xs font-semibold bg-white border border-rose-300 text-rose-700 rounded-xl hover:bg-rose-100 transition shadow-xs"
                            >
                                Coba Lagi
                            </button>
                        </div>

                        <!-- Empty State -->
                        <EmptyState
                            v-else-if="filteredOrders.length === 0"
                            title="Belum Ada Pesanan"
                            description="Anda belum memiliki transaksi tiket konser pada status atau pencarian ini."
                            actionText="Jelajah Konser Sekarang"
                            @action="$inertia.visit('/')"
                        />

                        <!-- Orders Cards -->
                        <div v-else class="space-y-4">
                            <div
                                v-for="order in filteredOrders"
                                :key="order.id"
                                class="bg-white border border-slate-200/80 rounded-2xl p-5 sm:p-6 hover:border-sky-300 hover:shadow-md transition-all shadow-xs flex flex-col justify-between gap-4"
                            >
                                <!-- Top: Concert Header, Poster, Venue, Date & Badges -->
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-3.5 border-b border-slate-100">
                                    <div class="flex items-start sm:items-center gap-3.5 min-w-0">
                                        <!-- Concert Poster Thumbnail -->
                                        <img
                                            v-if="getConcertForOrder(order)?.poster"
                                            :src="getConcertForOrder(order).poster"
                                            :alt="getConcertForOrder(order).name"
                                            class="w-14 h-14 sm:w-16 sm:h-16 rounded-xl object-cover shrink-0 border border-slate-200/80 shadow-2xs"
                                            @error="$event.target.style.display='none'"
                                        />
                                        <div v-else class="w-14 h-14 sm:w-16 sm:h-16 rounded-xl bg-gradient-to-br from-sky-100 to-sky-200 border border-sky-200/80 flex items-center justify-center text-sky-700 shrink-0">
                                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                                            </svg>
                                        </div>

                                        <div class="min-w-0">
                                            <!-- Concert Title -->
                                            <h3 class="text-base sm:text-lg font-bold text-slate-900 leading-snug truncate">
                                                {{ getConcertForOrder(order)?.name || 'Konser Musik' }}
                                            </h3>
                                            <!-- Location & Event Date -->
                                            <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500 mt-1">
                                                <span class="flex items-center gap-1 font-medium">
                                                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                    </svg>
                                                    {{ formatDate(getConcertForOrder(order)?.event_date) }}
                                                </span>
                                                <span class="text-slate-300">•</span>
                                                <span class="truncate">{{ getConcertForOrder(order)?.location || 'Venue Konser' }}</span>
                                            </div>
                                            <!-- Booking Code & Order Date -->
                                            <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">No. Order:</span>
                                                    <span class="font-mono font-bold text-xs text-slate-800 bg-slate-100 px-2 py-0.5 rounded-md">
                                                        {{ order.order_code }}
                                                    </span>
                                                    <button
                                                        type="button"
                                                        @click="copyCode(order.order_code)"
                                                        title="Salin kode booking"
                                                        class="text-slate-400 hover:text-sky-600 transition"
                                                    >
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                        </svg>
                                                    </button>
                                                </div>
                                                <span class="text-slate-300 text-xs hidden sm:inline">&bull;</span>
                                                <span class="text-[11px] text-slate-400 hidden sm:inline">
                                                    {{ formatDate(order.ordered_at || order.created_at, true) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Status Badges -->
                                    <div class="flex items-center gap-2 self-start sm:self-center shrink-0">
                                        <Badge :status="order.status" size="md" />
                                        <Badge v-if="order.payment" :status="order.payment.status" size="md" />
                                    </div>
                                </div>

                                <!-- Middle: Ticket Items List -->
                                <div class="bg-slate-50/80 border border-slate-100 rounded-xl p-3.5 space-y-2">
                                    <div
                                        v-for="item in order.order_items"
                                        :key="item.id"
                                        class="flex items-center justify-between text-xs gap-3"
                                    >
                                        <div class="flex items-center gap-2 min-w-0">
                                            <span class="w-1.5 h-1.5 rounded-full bg-sky-500 shrink-0"></span>
                                            <span class="font-bold text-slate-800 truncate">
                                                {{ item.ticket_category?.name || 'Tiket Masuk' }}
                                            </span>
                                            <span class="text-slate-500 shrink-0">&times; {{ item.quantity }} tiket</span>
                                            <span class="text-slate-400 hidden sm:inline shrink-0">(@ {{ formatRupiah(item.price) }})</span>
                                        </div>
                                        <span class="font-bold text-slate-900 shrink-0">{{ formatRupiah(item.subtotal) }}</span>
                                    </div>
                                </div>

                                <!-- Bottom: Payment Summary & Action Buttons -->
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-1">
                                    <!-- Payment Info / Status Note -->
                                    <div class="text-xs text-slate-500">
                                        <div v-if="order.payment" class="flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                            </svg>
                                            <span class="font-medium text-slate-700">{{ order.payment.payment_method || 'Metode Transfer' }}</span>
                                        </div>
                                        <div v-else-if="order.status === 'pending'" class="flex items-center gap-1.5 text-amber-700 font-medium">
                                            <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                            <span>Menunggu pembayaran & upload bukti</span>
                                        </div>
                                        <div v-else class="text-slate-400">
                                            Status pesanan: <span class="capitalize font-medium text-slate-600">{{ order.status }}</span>
                                        </div>
                                    </div>

                                    <!-- Total Tagihan & Action Buttons -->
                                    <div class="flex items-center justify-between sm:justify-end gap-3 sm:gap-4 flex-wrap">
                                        <div class="text-left sm:text-right">
                                            <span class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider block">Total Tagihan</span>
                                            <span class="text-lg sm:text-xl font-extrabold text-slate-900 leading-none">
                                                {{ formatRupiah(order.total_amount) }}
                                            </span>
                                        </div>

                                        <div class="flex items-center gap-2 shrink-0 flex-wrap">
                                            <button
                                                type="button"
                                                @click="openDetail(order)"
                                                class="px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition shadow-2xs"
                                            >
                                                Rincian
                                            </button>

                                            <!-- Pending: Bayar Sekarang -->
                                            <Link
                                                v-if="order.status === 'pending'"
                                                :href="`/payment/${order.id}`"
                                                class="px-4 py-2 text-xs font-bold text-white bg-sky-600 hover:bg-sky-700 rounded-xl transition shadow-xs flex items-center gap-1.5"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
                                                Bayar Sekarang
                                            </Link>

                                            <!-- Paid: Buka E-Tiket (Langsung ke Halaman Penuh) -->
                                            <Link
                                                v-else-if="order.status === 'paid'"
                                                :href="`/tickets?order_id=${order.id}`"
                                                class="px-4 py-2 text-xs font-bold text-white bg-sky-600 hover:bg-sky-700 rounded-xl transition shadow-xs flex items-center gap-1.5"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                                                </svg>
                                                <span>Buka E-Tiket</span>
                                                <span v-if="getTicketsForOrder(order.id).length > 0" class="px-1.5 py-0.5 bg-white/20 rounded-md text-[10px]">
                                                    {{ getTicketsForOrder(order.id).length }}
                                                </span>
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar Column (1 col, consistent with Tickets/Index) -->
                    <div class="space-y-5">
                        <!-- Alur Pemesanan Card -->
                        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-4 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                                Alur Pembelian Tiket
                            </h3>
                            <div class="space-y-4 text-xs">
                                <div class="flex items-start gap-3">
                                    <div class="w-6 h-6 rounded-full bg-sky-100 text-sky-700 font-bold flex items-center justify-center shrink-0 text-[11px]">1</div>
                                    <div>
                                        <p class="font-bold text-slate-800">Checkout & Booking Tiket</p>
                                        <p class="text-slate-500 text-[11px] mt-0.5">Kuota tiket terkunci sementara untuk Anda.</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div class="w-6 h-6 rounded-full bg-sky-100 text-sky-700 font-bold flex items-center justify-center shrink-0 text-[11px]">2</div>
                                    <div>
                                        <p class="font-bold text-slate-800">Pembayaran Bank / QRIS</p>
                                        <p class="text-slate-500 text-[11px] mt-0.5">Transfer sesuai nominal dan kirim bukti transfer.</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div class="w-6 h-6 rounded-full bg-sky-100 text-sky-700 font-bold flex items-center justify-center shrink-0 text-[11px]">3</div>
                                    <div>
                                        <p class="font-bold text-slate-800">Verifikasi Tiketin</p>
                                        <p class="text-slate-500 text-[11px] mt-0.5">Admin / sistem memverifikasi transaksi Anda.</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 font-bold flex items-center justify-center shrink-0 text-[11px]">4</div>
                                    <div>
                                        <p class="font-bold text-slate-800">E-Tiket Terbit Otomatis</p>
                                        <p class="text-slate-500 text-[11px] mt-0.5">Siap di-scan di gate barcode venue konser.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Jaminan Keamanan Card -->
                        <div class="bg-gradient-to-br from-sky-50 to-blue-50/40 border border-sky-100 rounded-2xl p-5 shadow-xs">
                            <div class="flex items-center gap-2 mb-2 text-sky-900 font-bold text-xs uppercase tracking-wide">
                                <svg class="w-4 h-4 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                Garansi Tiket Resmi 100%
                            </div>
                            <p class="text-xs text-sky-800 leading-relaxed mb-3">
                                Semua pesanan tiket yang diterbitkan melalui platform Tiketin terjamin keasliannya dan langsung terintegrasi dengan promotor acara.
                            </p>
                            <div class="text-[11px] text-sky-700 flex items-center gap-1 font-medium">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Terdaftar dan Dilindungi Sistem Anti-Fraud
                            </div>
                        </div>

                        <!-- Bantuan Customer Service Card -->
                        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-2">Butuh Bantuan Pesanan?</h3>
                            <p class="text-xs text-slate-500 mb-4">
                                Kendala upload bukti pembayaran atau pertanyaan seputar pesanan? Tim kami siap melayani Anda 24/7.
                            </p>
                            <a
                                href="https://wa.me/6281234567890?text=Halo%20Tiketin%20saya%20butuh%20bantuan%20pesanan"
                                target="_blank"
                                rel="noopener"
                                class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-xs"
                            >
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86.174.086.275.073.376-.044.101-.116.433-.506.549-.68.116-.173.231-.145.39-.086s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.1.824z"/>
                                </svg>
                                WhatsApp Support Tiketin
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Modal -->
        <Modal
            :show="showDetailModal"
            :title="selectedOrder ? `Rincian Pesanan #${selectedOrder.order_code}` : 'Rincian Pesanan'"
            maxWidth="md"
            @close="showDetailModal = false"
        >
            <div v-if="selectedOrder" class="space-y-4 text-xs">
                <!-- Order Header Info -->
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <span class="text-slate-400 block mb-0.5 text-[11px] font-medium">Nomor Booking Transaksi</span>
                        <div class="flex items-center gap-2">
                            <span class="font-mono font-extrabold text-slate-900 text-sm">{{ selectedOrder.order_code }}</span>
                            <button
                                type="button"
                                @click="copyCode(selectedOrder.order_code)"
                                class="text-sky-600 hover:text-sky-700 text-[11px] font-semibold flex items-center gap-1"
                            >
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                Salin
                            </button>
                        </div>
                    </div>
                    <Badge :status="selectedOrder.status" />
                </div>

                <!-- Purchased Items -->
                <div>
                    <h4 class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">Item Tiket Dipesan</h4>
                    <div class="space-y-2 bg-slate-50 border border-slate-200/80 p-3.5 rounded-xl">
                        <div
                            v-for="item in selectedOrder.order_items"
                            :key="item.id"
                            class="flex justify-between items-center text-xs"
                        >
                            <div>
                                <p class="font-bold text-slate-800">{{ item.ticket_category?.name || 'Tiket' }} &times; {{ item.quantity }}</p>
                                <p class="text-slate-400 text-[11px]">@ {{ formatRupiah(item.price) }}</p>
                            </div>
                            <span class="font-bold text-slate-900">{{ formatRupiah(item.subtotal) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Total Amount Banner -->
                <div class="bg-sky-50/70 border border-sky-100 rounded-xl p-3.5 flex items-baseline justify-between">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-sky-700 tracking-wider block">Total Pembayaran</span>
                        <span class="text-xs text-sky-600">Termasuk pajak & biaya layanan</span>
                    </div>
                    <span class="text-xl font-extrabold text-sky-900">{{ formatRupiah(selectedOrder.total_amount) }}</span>
                </div>

                <!-- Payment Details -->
                <div v-if="selectedOrder.payment" class="pt-2 border-t border-slate-100 text-xs space-y-2">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Status Pembayaran:</span>
                        <span class="font-bold capitalize text-slate-800">{{ selectedOrder.payment.status }}</span>
                    </div>
                    <div v-if="selectedOrder.payment.payment_method" class="flex justify-between">
                        <span class="text-slate-500">Metode Pembayaran:</span>
                        <span class="font-medium text-slate-800">{{ selectedOrder.payment.payment_method }}</span>
                    </div>
                    <div v-if="selectedOrder.payment.proof" class="flex justify-between">
                        <span class="text-slate-500">Bukti / No. Ref:</span>
                        <span class="font-mono font-medium text-slate-800 truncate max-w-[180px]">{{ selectedOrder.payment.proof }}</span>
                    </div>
                    <div v-if="selectedOrder.payment.paid_at" class="flex justify-between">
                        <span class="text-slate-500">Waktu Verifikasi:</span>
                        <span class="font-medium text-slate-800">{{ formatDate(selectedOrder.payment.paid_at, true) }}</span>
                    </div>
                </div>
            </div>

            <template #footer>
                <button
                    type="button"
                    @click="showDetailModal = false"
                    class="px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition"
                >
                    Tutup
                </button>
                <Link
                    v-if="selectedOrder && selectedOrder.status === 'pending'"
                    :href="`/payment/${selectedOrder.id}`"
                    class="px-5 py-2 text-xs font-bold text-white bg-sky-600 hover:bg-sky-700 rounded-xl transition shadow-sm"
                >
                    Lanjutkan Pembayaran
                </Link>
                <button
                    v-else-if="selectedOrder && selectedOrder.status === 'paid'"
                    type="button"
                    @click="showDetailModal = false; openOrderTickets(selectedOrder)"
                    class="px-5 py-2 text-xs font-bold text-white bg-sky-600 hover:bg-sky-700 rounded-xl transition shadow-xs"
                >
                    Buka E-Tiket
                </button>
            </template>
        </Modal>

        <!-- Order-Specific E-Ticket Modal -->
        <Modal
            :show="showTicketModal"
            :title="ticketOrder ? `E-Tiket: ${getConcertForOrder(ticketOrder)?.name || 'Konser Musik'}` : 'E-Tiket Pesanan'"
            maxWidth="xl"
            @close="showTicketModal = false"
        >
            <div v-if="ticketOrder" class="space-y-4 text-xs">
                <!-- Order Concert Header Card -->
                <div class="p-4 bg-slate-50 border border-slate-200/80 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <img
                            v-if="getConcertForOrder(ticketOrder)?.poster"
                            :src="getConcertForOrder(ticketOrder).poster"
                            :alt="getConcertForOrder(ticketOrder).name"
                            class="w-12 h-12 rounded-xl object-cover shrink-0 border border-slate-200"
                            @error="$event.target.style.display='none'"
                        />
                        <div class="min-w-0">
                            <span class="text-[10px] font-bold text-sky-700 uppercase tracking-wider block">Tiket Resmi Pesanan Ini</span>
                            <h4 class="text-sm sm:text-base font-bold text-slate-900 leading-tight truncate">
                                {{ getConcertForOrder(ticketOrder)?.name || 'Konser Musik' }}
                            </h4>
                            <p class="text-xs text-slate-500 mt-0.5">
                                {{ formatDate(getConcertForOrder(ticketOrder)?.event_date) }} &bull; {{ getConcertForOrder(ticketOrder)?.location || 'Venue' }}
                            </p>
                        </div>
                    </div>
                    <div class="text-left sm:text-right shrink-0">
                        <span class="text-[10px] text-slate-400 block font-medium uppercase tracking-wider">No. Order</span>
                        <span class="font-mono font-bold text-xs text-slate-800 bg-white border border-slate-200 px-2 py-0.5 rounded-lg inline-block mt-0.5">
                            {{ ticketOrder.order_code }}
                        </span>
                    </div>
                </div>

                <!-- Tickets List Strictly for this Order -->
                <div v-if="getTicketsForOrder(ticketOrder.id).length > 0" class="space-y-3 pt-1">
                    <div class="flex items-center justify-between text-xs text-slate-500 font-medium">
                        <span>Menampilkan {{ getTicketsForOrder(ticketOrder.id).length }} e-tiket untuk pesanan ini:</span>
                        <span class="text-[11px] text-emerald-600 font-semibold flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            Siap di-scan di gate
                        </span>
                    </div>

                    <!-- Ticket Boarding Pass Card -->
                    <div
                        v-for="(ticket, idx) in getTicketsForOrder(ticketOrder.id)"
                        :key="ticket.id"
                        class="bg-white border border-slate-200/80 rounded-2xl overflow-hidden shadow-xs hover:border-sky-300 transition"
                    >
                        <!-- Header of Pass -->
                        <div class="px-4 py-2.5 bg-slate-900 text-white flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-bold bg-sky-600 text-white px-2 py-0.5 rounded-md uppercase">
                                    Pass #{{ idx + 1 }}
                                </span>
                                <span class="font-bold text-xs text-white">
                                    {{ ticket.ticket_category?.name || 'Area Masuk' }}
                                </span>
                            </div>
                            <span class="text-[10px] font-semibold text-emerald-400 bg-emerald-950/60 border border-emerald-800 px-2 py-0.5 rounded-full">
                                {{ ticket.status === 'used' ? 'Sudah Digunakan' : 'Aktif / Berlaku' }}
                            </span>
                        </div>

                        <!-- Ticket Body with Perforation and Real QR -->
                        <div class="p-4">
                            <div class="flex flex-col sm:flex-row items-center gap-4 p-3 bg-slate-50/70 border border-slate-100 rounded-xl">
                                <!-- QR Thumbnail (Click to enlarge) -->
                                <div class="shrink-0 text-center">
                                    <button
                                        type="button"
                                        @click="openFullscreenQr(ticket)"
                                        class="block w-24 h-24 bg-white border border-slate-200 rounded-xl p-1 hover:border-sky-400 transition group relative overflow-hidden shadow-2xs"
                                        title="Klik untuk buka QR layar penuh"
                                    >
                                        <img
                                            v-if="ticketQrs[ticket.id]"
                                            :src="ticketQrs[ticket.id]"
                                            :alt="ticket.ticket_code"
                                            class="w-full h-full object-contain"
                                        />
                                        <div v-else class="w-full h-full flex items-center justify-center text-xs text-slate-400 animate-pulse">
                                            QR...
                                        </div>
                                        <div class="absolute inset-0 bg-sky-950/15 opacity-0 group-hover:opacity-100 flex items-center justify-center transition">
                                            <span class="text-[9px] font-bold bg-white/90 text-sky-900 px-1.5 py-0.5 rounded shadow-2xs">Perbesar</span>
                                        </div>
                                    </button>
                                </div>

                                <!-- Ticket Meta Information -->
                                <div class="grow text-center sm:text-left min-w-0">
                                    <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Kode Booking Gate</p>
                                    <p class="font-mono font-bold text-base text-slate-900 tracking-wider">{{ ticket.ticket_code }}</p>
                                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                        Tunjukkan QR code ini langsung ke scanner gate turnstile di pintu masuk venue konser.
                                    </p>
                                </div>
                            </div>

                            <!-- Ticket Actions -->
                            <div class="flex items-center gap-2 mt-3 pt-3 border-t border-slate-100 flex-wrap">
                                <button
                                    type="button"
                                    @click="openFullscreenQr(ticket)"
                                    class="px-3.5 py-1.5 bg-sky-600 hover:bg-sky-700 text-white text-xs font-semibold rounded-xl transition shadow-xs flex items-center gap-1.5"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                    </svg>
                                    Tampilkan QR Gate
                                </button>
                                <button
                                    type="button"
                                    @click="downloadTicketQr(ticket)"
                                    class="px-3 py-1.5 bg-white border border-slate-200 text-slate-700 text-xs font-semibold rounded-xl hover:bg-slate-50 transition shadow-2xs"
                                >
                                    Unduh QR
                                </button>
                                <button
                                    type="button"
                                    @click="shareToWhatsApp(ticket, getConcertForOrder(ticketOrder)?.name)"
                                    class="px-3 py-1.5 bg-white border border-slate-200 text-slate-700 text-xs font-semibold rounded-xl hover:bg-slate-50 transition flex items-center gap-1 shadow-2xs"
                                >
                                    <svg class="w-3 h-3 text-emerald-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86.174.086.275.073.376-.044.101-.116.433-.506.549-.68.116-.173.231-.145.39-.086s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.1.824z"/></svg>
                                    WhatsApp
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty state for tickets in this order -->
                <div v-else class="p-8 text-center bg-slate-50 border border-slate-200/80 rounded-2xl">
                    <p class="text-sm font-bold text-slate-800 mb-1">Tiket Sedang Dipersiapkan</p>
                    <p class="text-xs text-slate-500">
                        Tiket untuk pesanan #{{ ticketOrder.order_code }} sedang diproses penerbitannya. Silakan segarkan halaman beberapa saat lagi.
                    </p>
                </div>
            </div>

            <template #footer>
                <div class="flex items-center justify-between w-full">
                    <Link
                        v-if="ticketOrder"
                        :href="`/tickets?order_id=${ticketOrder.id}`"
                        class="text-xs font-semibold text-sky-600 hover:text-sky-700 hover:underline flex items-center gap-1"
                    >
                        <span>Buka di Halaman Penuh</span>
                        <span>&rarr;</span>
                    </Link>
                    <button
                        type="button"
                        @click="showTicketModal = false"
                        class="px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition shadow-2xs ml-auto"
                    >
                        Tutup
                    </button>
                </div>
            </template>
        </Modal>

        <!-- Fullscreen Gate Pass Modal -->
        <Modal
            :show="showFullscreenQrModal"
            title="Tiketin Gate Pass - Scan Pintu Masuk"
            maxWidth="md"
            @close="showFullscreenQrModal = false"
        >
            <div v-if="selectedTicketFullscreen" class="text-center space-y-4">
                <div class="p-5 bg-white border-2 border-sky-400 rounded-2xl inline-block shadow-inner mx-auto">
                    <img
                        :src="fullscreenQrUrl"
                        :alt="selectedTicketFullscreen.ticket_code"
                        class="w-56 h-56 mx-auto object-contain"
                    />
                </div>

                <div>
                    <span class="text-[11px] font-bold text-sky-600 uppercase tracking-wider block">Kategori Tiket</span>
                    <h3 class="text-lg font-extrabold text-slate-900">{{ selectedTicketFullscreen.ticket_category?.name || 'Area Konser' }}</h3>
                    <p class="text-xs font-mono font-bold text-slate-600 mt-0.5 tracking-wider">{{ selectedTicketFullscreen.ticket_code }}</p>
                </div>

                <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800 text-left space-y-1">
                    <p class="font-bold flex items-center gap-1">
                        <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Petunjuk Gate Masuk Venue
                    </p>
                    <p class="text-[11px] text-amber-700">
                        Pastikan kecerahan layar ponsel Anda dinaikkan maksimal saat mendekati sensor scanner turnstile gate.
                    </p>
                </div>
            </div>

            <template #footer>
                <div class="flex items-center justify-between w-full">
                    <span class="text-[11px] text-slate-400 font-mono">Kode: {{ selectedTicketFullscreen?.ticket_code }}</span>
                    <button
                        type="button"
                        @click="showFullscreenQrModal = false"
                        class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-xl transition"
                    >
                        Selesai Scan
                    </button>
                </div>
            </template>
        </Modal>
    </CustomerLayout>
</template>
