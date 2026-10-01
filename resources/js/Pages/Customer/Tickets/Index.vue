<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Modal from '@/Components/Modal.vue';

import { api } from '@/services/api';
import { auth, formatDate, formatRupiah } from '@/stores/auth';
import { useToast } from '@/composables/useToast';
import QRCode from 'qrcode';

const toast = useToast();

const tickets = ref([]);
const userOrders = ref([]);
const events = ref([]);
const selectedOrderId = ref(null);

const loading = ref(true);
const error = ref(null);

const activeTab = ref('active'); // 'active' | 'history' | 'pending'
const selectedTicket = ref(null);
const showTicketModal = ref(false);


const ticketQrs = ref({});
const modalQrUrl = ref('');
const countdown = ref(45);
let timerInterval = null;

const eventMap = computed(() => {
    const map = new Map();
    events.value.forEach(e => map.set(e.id, e));
    return map;
});

const ordersWithTickets = computed(() => {
    const orderIds = new Set(tickets.value.map(t => t.order_id).filter(Boolean));
    return userOrders.value.filter(o => orderIds.has(o.id));
});

const activeOrder = computed(() => {
    if (selectedOrderId.value) {
        return userOrders.value.find(o => o.id === selectedOrderId.value) || null;
    }
    return null;
});

const currentEvent = computed(() => {
    if (activeOrder.value) {
        const eventId = activeOrder.value.order_items?.[0]?.ticket_category?.event_id;
        return eventMap.value.get(eventId) || activeOrder.value.order_items?.[0]?.ticket_category?.event || null;
    }
    return null;
});

function getOrderConcertName(order) {
    const directEvent = order.order_items?.[0]?.ticket_category?.event;
    if (directEvent?.name) return directEvent.name;
    const eventId = order.order_items?.[0]?.ticket_category?.event_id;
    if (eventId && eventMap.value.has(eventId)) {
        return eventMap.value.get(eventId).name;
    }
    return 'Konser Musik';
}

async function generateQrs() {
    for (const t of tickets.value) {
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

async function fetchTickets() {
    loading.value = true;
    error.value = null;
    try {
        const [ticketsRes, ordersRes, eventsRes] = await Promise.all([
            api.getTickets(),
            api.getOrders().catch(() => ({ data: [] })),
            api.getEvents().catch(() => ({ data: [] })),
        ]);
        tickets.value = ticketsRes.data || [];
        userOrders.value = ordersRes.data || [];
        events.value = eventsRes.data || [];

        // Check URL query param for order_id
        if (typeof window !== 'undefined') {
            const urlParams = new URLSearchParams(window.location.search);
            const oid = urlParams.get('order_id');
            if (oid) {
                selectedOrderId.value = Number(oid);
            }
        }

        // Attach event info to tickets if missing
        tickets.value.forEach(t => {
            if (!t.ticket_category?.event && t.ticket_category?.event_id) {
                const foundEvent = events.value.find(e => e.id === t.ticket_category.event_id);
                if (foundEvent) {
                    if (!t.ticket_category) t.ticket_category = {};
                    t.ticket_category.event = foundEvent;
                }
            }
        });

        await generateQrs();
    } catch (err) {
        error.value = err.message || 'Gagal memuat daftar tiket Anda.';
    } finally {
        loading.value = false;
    }
}

const activeTickets = computed(() => {
    let list = tickets.value;
    if (selectedOrderId.value) list = list.filter(t => t.order_id === selectedOrderId.value);
    return list.filter(t => t.status === 'active' || t.status === 'valid');
});

const historyTickets = computed(() => {
    let list = tickets.value;
    if (selectedOrderId.value) list = list.filter(t => t.order_id === selectedOrderId.value);
    return list.filter(t => t.status === 'used' || t.status === 'expired');
});

const pendingTickets = computed(() => {
    let list = tickets.value;
    if (selectedOrderId.value) list = list.filter(t => t.order_id === selectedOrderId.value);
    return list.filter(t => t.status === 'pending');
});

const currentTickets = computed(() => {
    let list = tickets.value;

    // Strict filter: ONLY show tickets for the selected order
    if (selectedOrderId.value) {
        list = list.filter(t => t.order_id === selectedOrderId.value);
    }

    if (activeTab.value === 'active') return list.filter(t => t.status === 'active' || t.status === 'valid');
    if (activeTab.value === 'history') return list.filter(t => t.status === 'used' || t.status === 'expired');
    if (activeTab.value === 'pending') return list.filter(t => t.status === 'pending');
    return list;
});

async function viewTicket(ticket) {
    selectedTicket.value = ticket;
    try {
        modalQrUrl.value = await QRCode.toDataURL(ticket.ticket_code, {
            width: 360,
            margin: 2,
            color: { dark: '#0284c7', light: '#ffffff' },
            errorCorrectionLevel: 'H',
        });
    } catch (e) {
        modalQrUrl.value = ticketQrs.value[ticket.id] || '';
    }
    showTicketModal.value = true;
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

function getStatusLabel(status) {
    const map = {
        active: 'Aktif',
        valid: 'Lunas & Siap Digunakan',
        used: 'Sudah Digunakan',
        expired: 'Kedaluwarsa',
        pending: 'Menunggu Verifikasi',
    };
    return map[status] || status;
}

function getStatusClass(status) {
    if (status === 'active' || status === 'valid') return 'bg-emerald-100 text-emerald-700 border-emerald-200';
    if (status === 'used') return 'bg-slate-100 text-slate-500 border-slate-200';
    if (status === 'expired') return 'bg-rose-100 text-rose-600 border-rose-200';
    if (status === 'pending') return 'bg-amber-100 text-amber-700 border-amber-200';
    return 'bg-slate-100 text-slate-600 border-slate-200';
}

function downloadTicketQr(ticket) {
    const qrData = ticketQrs.value[ticket.id] || modalQrUrl.value;
    if (!qrData) return;
    const a = document.createElement('a');
    a.href = qrData;
    a.download = `Tiketin-Pass-${ticket.ticket_code}.png`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    toast?.success?.('QR Code E-Tiket berhasil diunduh!');
}

function shareToWhatsApp(ticket) {
    const text = encodeURIComponent(
        `Halo! Ini E-Tiket resmi saya di Tiketin:\n` +
        `Konser: ${ticket.ticket_category?.event?.name || 'Konser Musik'}\n` +
        `Kategori: ${ticket.ticket_category?.name || 'Tiket'}\n` +
        `Kode Tiket: ${ticket.ticket_code}\n` +
        `Tanggal: ${formatDate(ticket.ticket_category?.event?.event_date)}\n` +
        `Venue: ${ticket.ticket_category?.event?.location || 'Venue'}\n` +
        `Tunjukkan QR code ini di gerbang masuk!`
    );
    window.open(`https://wa.me/?text=${text}`, '_blank');
}

onMounted(() => {
    if (auth.isAuthenticated.value) {
        fetchTickets();
    } else {
        loading.value = false;
    }
});
</script>

<template>
    <CustomerLayout>
        <Head title="E-Ticket & Tiket Saya - Tiketin" />

        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header (Specific Order View or All Orders View) -->
            <div class="mb-6">
                <!-- If filtered by a specific order -->
                <div v-if="activeOrder" class="space-y-3">
                    <Link
                        href="/orders"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-sky-600 hover:text-sky-700 hover:underline transition"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Kembali ke Semua Pesanan
                    </Link>

                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 bg-white border border-slate-200/80 rounded-2xl shadow-xs">
                        <div class="flex items-start gap-4 min-w-0">
                            <img
                                v-if="currentEvent?.poster"
                                :src="currentEvent.poster"
                                :alt="currentEvent.name"
                                class="w-16 h-16 rounded-xl object-cover shrink-0 border border-slate-200 shadow-2xs"
                                @error="$event.target.style.display='none'"
                            />
                            <div class="min-w-0">
                                <span class="text-[10px] font-bold text-sky-700 uppercase tracking-wider block">E-Tiket Pesanan Konser</span>
                                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight leading-tight truncate">
                                    {{ currentEvent?.name || 'Konser Musik' }}
                                </h1>
                                <p class="text-xs text-slate-500 mt-1 flex items-center gap-2 flex-wrap">
                                    <span>{{ formatDate(currentEvent?.event_date) }}</span>
                                    <span>&bull;</span>
                                    <span>{{ currentEvent?.location || 'Venue' }}</span>
                                    <span>&bull;</span>
                                    <span class="font-mono font-bold text-slate-700 bg-slate-100 px-2 py-0.5 rounded text-[11px]">#{{ activeOrder.order_code }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Action buttons removed for cleaner UX -->
                    </div>

                    <!-- Notice banner -->
                    <div class="px-4 py-2.5 bg-sky-50/70 border border-sky-200/70 rounded-xl flex items-center justify-between text-xs text-sky-800">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-sky-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Menampilkan tiket khusus untuk pesanan <strong>#{{ activeOrder.order_code }}</strong>.
                        </span>
                        <Link href="/orders" class="font-bold underline text-sky-900 hover:text-sky-700 shrink-0">
                            Pilih Pesanan Lain &rarr;
                        </Link>
                    </div>
                </div>

                <!-- If NO specific order selected (All Tickets View) -->
                <div v-else>
                    <div class="flex items-center gap-2 text-xs text-slate-400 font-medium mb-2">
                        <svg class="w-4 h-4 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                        E-Voucher & Pass Masuk Resmi &bull; Tiketin
                    </div>
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">E-Ticket & Pass Masuk Saya</h1>
                            <p class="text-sm text-slate-500 mt-0.5">Tunjukkan QR code resmi untuk verifikasi turnstile gate atau gunakan scanner kamera.</p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0 flex-wrap">

                            <button
                                type="button"
                                @click="fetchTickets"
                                class="flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition shadow-2xs"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Sinkronkan
                            </button>
                        </div>
                    </div>

                    <!-- Order Switcher Pills (If user has multiple orders) -->
                    <div v-if="ordersWithTickets.length > 1" class="mt-4 p-2 bg-slate-100/80 rounded-2xl flex items-center gap-1.5 overflow-x-auto">
                        <span class="text-[11px] text-slate-400 font-bold uppercase tracking-wider px-2 shrink-0">Filter Konser:</span>
                        <button
                            type="button"
                            @click="selectedOrderId = null"
                            class="px-3 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition"
                            :class="!selectedOrderId ? 'bg-white text-slate-900 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900'"
                        >
                            Semua Konser ({{ tickets.length }})
                        </button>
                        <button
                            v-for="ord in ordersWithTickets"
                            :key="ord.id"
                            type="button"
                            @click="selectedOrderId = ord.id"
                            class="px-3 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition flex items-center gap-1.5"
                            :class="selectedOrderId === ord.id ? 'bg-white text-sky-800 shadow-xs font-bold border border-sky-200' : 'text-slate-600 hover:text-slate-900'"
                        >
                            <span>{{ getOrderConcertName(ord) }}</span>
                            <span class="font-mono text-[10px] text-slate-400 font-normal">#{{ ord.order_code }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Not Logged In -->
            <div v-if="!auth.isAuthenticated.value" class="p-8 bg-white border border-slate-200 rounded-2xl text-center max-w-md mx-auto my-12 shadow-sm">
                <div class="w-12 h-12 rounded-xl bg-sky-50 border border-sky-100 flex items-center justify-center text-sky-600 mx-auto mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                </div>
                <h2 class="text-base font-bold text-slate-900 mb-1">Masuk untuk Mengakses E-Tiket</h2>
                <p class="text-sm text-slate-500 mb-5">Silakan masuk untuk melihat tiket konser yang sudah aktif dan siap digunakan.</p>
                <Link href="/login" class="inline-block px-5 py-2.5 bg-sky-600 text-white rounded-xl text-sm font-bold hover:bg-sky-700 transition shadow-sm">
                    Masuk Sekarang
                </Link>
            </div>

            <!-- Logged In Content -->
            <div v-else>
                <!-- Tabs -->
                <div class="flex items-center gap-1 mb-5 bg-slate-100 rounded-xl p-1 w-fit overflow-x-auto">
                    <button
                        type="button"
                        @click="activeTab = 'active'"
                        class="px-4 py-2 rounded-lg text-sm font-semibold transition whitespace-nowrap"
                        :class="activeTab === 'active' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                    >
                        Tiket Aktif ({{ activeTickets.length }})
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'history'"
                        class="px-4 py-2 rounded-lg text-sm font-semibold transition whitespace-nowrap flex items-center gap-1.5"
                        :class="activeTab === 'history' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Riwayat Tiket Selesai ({{ historyTickets.length }})
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'pending'"
                        class="px-4 py-2 rounded-lg text-sm font-semibold transition whitespace-nowrap flex items-center gap-1.5"
                        :class="activeTab === 'pending' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        Menunggu Pembayaran ({{ pendingTickets.length }})
                    </button>
                </div>

                <!-- Loading -->
                <div v-if="loading" class="space-y-4">
                    <div v-for="n in 2" :key="n" class="bg-white border border-slate-200 rounded-2xl animate-pulse overflow-hidden shadow-sm">
                        <div class="h-48 bg-slate-100"></div>
                        <div class="p-5 space-y-3">
                            <div class="h-4 bg-slate-200 rounded w-1/3"></div>
                            <div class="h-6 bg-slate-100 rounded w-1/2"></div>
                            <div class="h-20 bg-slate-50 rounded-xl"></div>
                        </div>
                    </div>
                </div>

                <!-- Error -->
                <div v-else-if="error" class="p-6 bg-rose-50 border border-rose-200 rounded-2xl text-center max-w-md mx-auto shadow-sm">
                    <p class="text-xs font-bold text-rose-800 mb-2">{{ error }}</p>
                    <button
                        @click="fetchTickets"
                        class="px-4 py-2 text-xs font-semibold bg-white border border-rose-300 text-rose-700 rounded-lg hover:bg-rose-100 transition shadow-xs"
                    >
                        Coba Lagi
                    </button>
                </div>

                <!-- Empty -->
                <EmptyState
                    v-else-if="currentTickets.length === 0"
                    title="Tidak Ada Tiket"
                    :description="activeTab === 'active'
                        ? 'Anda belum memiliki tiket konser aktif saat ini. Beli tiket sekarang untuk menikmati konser favorit Anda!'
                        : 'Tidak ada tiket di kategori ini.'"
                    actionText="Jelajah Event Konser"
                    @action="$inertia.visit('/')"
                />

                <!-- Two-column layout: Tickets + Sidebar -->
                <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Tickets List (left) -->
                    <div class="lg:col-span-2 space-y-5">
                        <!-- Gate Info Banner -->
                        <div v-if="activeTab === 'active' && activeTickets.length > 0" class="p-4 bg-sky-50 border border-sky-200 rounded-2xl flex items-start justify-between gap-4 shadow-xs">
                            <div class="flex items-start gap-2.5">
                                <svg class="w-5 h-5 text-sky-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <div>
                                    <p class="text-xs font-bold text-sky-950">E-Tiket QR Dinamis Tiketin Resmi</p>
                                    <p class="text-xs text-sky-800 mt-0.5">Penukaran wristband fisik tidak diperlukan. Langsung pindai kode QR di bawah ini pada barcode scanner turnstile venue.</p>
                                </div>
                            </div>
                            <span class="text-xs font-bold text-sky-700 bg-sky-100 border border-sky-200 px-2.5 py-1 rounded-lg shrink-0">Live Gate Pass</span>
                        </div>

                        <!-- Ticket Cards (Boarding Pass Layout) -->
                        <div
                            v-for="ticket in currentTickets"
                            :key="ticket.id"
                            class="bg-white border border-slate-200/80 rounded-2xl overflow-hidden hover:border-sky-300 hover:shadow-md transition-all shadow-xs"
                        >
                            <!-- Event Image Header Banner -->
                            <div class="relative h-36 bg-gradient-to-br from-slate-900 to-sky-950 overflow-hidden">
                                <img
                                    v-if="ticket.ticket_category?.event?.poster"
                                    :src="ticket.ticket_category.event.poster"
                                    :alt="ticket.ticket_category?.event?.name"
                                    class="w-full h-full object-cover opacity-75"
                                    @error="$event.target.style.display='none'"
                                />
                                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/85 via-slate-950/40 to-transparent"></div>

                                <!-- Top Badges -->
                                <div class="absolute top-3 left-3">
                                    <span class="text-[10px] font-semibold px-2.5 py-1 bg-white/90 text-slate-900 rounded-lg shadow-xs backdrop-blur-xs">
                                        Tiketin Pass Resmi
                                    </span>
                                </div>
                                <div class="absolute top-3 right-3">
                                    <span class="text-[10px] font-semibold px-2.5 py-1 rounded-full border shadow-xs" :class="getStatusClass(ticket.status)">
                                        {{ getStatusLabel(ticket.status) }}
                                    </span>
                                </div>

                                <!-- Event title & category overlay -->
                                <div class="absolute bottom-3 left-4 right-4 flex items-end justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-[10px] font-medium text-sky-300 uppercase tracking-wider">Pass Masuk Konser</p>
                                        <h3 class="text-base sm:text-lg font-bold text-white leading-tight truncate">
                                            {{ ticket.ticket_category?.event?.name || 'Konser Musik' }}
                                        </h3>
                                    </div>
                                    <span class="text-xs font-bold text-sky-700 bg-sky-50 border border-sky-200/80 px-2.5 py-1 rounded-lg shrink-0">
                                        {{ ticket.ticket_category?.name || 'CAT 1' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Specs Info Bar -->
                            <div class="p-4 sm:p-5 pb-3">
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                                    <div>
                                        <p class="text-[10px] text-slate-400 font-medium uppercase tracking-wider mb-0.5">Tanggal</p>
                                        <p class="font-bold text-slate-900 truncate">{{ formatDate(ticket.ticket_category?.event?.event_date) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-slate-400 font-medium uppercase tracking-wider mb-0.5">Lokasi Venue</p>
                                        <p class="font-bold text-slate-900 truncate">{{ ticket.ticket_category?.event?.location || 'Venue' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-slate-400 font-medium uppercase tracking-wider mb-0.5">Pemegang Tiket</p>
                                        <p class="font-bold text-slate-900 truncate">{{ auth.user.value?.name || 'Customer' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-slate-400 font-medium uppercase tracking-wider mb-0.5">Kode Booking</p>
                                        <p class="font-mono font-bold text-sky-700 truncate">{{ ticket.ticket_code }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Perforation Divider with Notches -->
                            <div class="relative flex items-center justify-between">
                                <div class="w-3.5 h-6 bg-slate-50 border-r border-y border-slate-200 rounded-r-full -ml-px"></div>
                                <div class="grow border-t-2 border-dashed border-slate-200 mx-3"></div>
                                <div class="w-3.5 h-6 bg-slate-50 border-l border-y border-slate-200 rounded-l-full -mr-px"></div>
                            </div>

                            <!-- QR Gate & Actions Section -->
                            <div class="p-4 sm:p-5 pt-3">
                                <div class="flex flex-col sm:flex-row items-center gap-5 p-3.5 bg-slate-50/70 border border-slate-100 rounded-2xl">
                                    <!-- QR Image -->
                                    <div class="shrink-0 text-center">
                                        <button
                                            type="button"
                                            @click="viewTicket(ticket)"
                                            class="block w-24 h-24 sm:w-28 sm:h-28 bg-white border border-slate-200 rounded-2xl p-1.5 hover:border-sky-400 hover:shadow-xs transition group relative overflow-hidden"
                                            title="Klik untuk perbesar QR Code"
                                        >
                                            <img
                                                v-if="ticketQrs[ticket.id]"
                                                :src="ticketQrs[ticket.id]"
                                                :alt="ticket.ticket_code"
                                                class="w-full h-full object-contain"
                                            />
                                            <div v-else class="w-full h-full flex items-center justify-center text-xs text-slate-400 animate-pulse">
                                                Memuat QR...
                                            </div>
                                            <div class="absolute inset-0 bg-sky-900/10 opacity-0 group-hover:opacity-100 flex items-center justify-center transition">
                                                <span class="text-[9px] font-semibold bg-white/90 text-sky-900 px-1.5 py-0.5 rounded shadow-xs">Perbesar</span>
                                            </div>
                                        </button>
                                        <p class="text-[10px] text-slate-400 mt-1 flex items-center justify-center gap-1 font-medium">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Siap di-scan
                                        </p>
                                    </div>

                                    <!-- Gate Instructions -->
                                    <div class="grow text-center sm:text-left min-w-0">
                                        <div class="flex items-center justify-center sm:justify-start gap-2 mb-1">
                                            <span class="text-xs font-bold text-slate-900">QR Gate Pass Turnstile</span>
                                            <span class="text-[10px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">Tervalidasi</span>
                                        </div>
                                        <p class="text-xs text-slate-500 leading-relaxed mb-3">
                                            Tunjukkan kode QR ini langsung pada sensor gate turnstile di pintu masuk venue konser.
                                        </p>
                                        <div class="inline-flex items-center gap-2 px-3 py-1 bg-white border border-slate-200 rounded-lg text-xs">
                                            <span class="text-[10px] text-slate-400 uppercase font-medium">Kode Gate:</span>
                                            <span class="font-mono font-bold text-slate-800 tracking-wider">{{ ticket.ticket_code }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex items-center gap-2 mt-4 pt-3 border-t border-slate-100 flex-wrap">
                                    <button
                                        type="button"
                                        @click="viewTicket(ticket)"
                                        class="flex items-center gap-1.5 px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white text-xs font-semibold rounded-xl transition shadow-xs"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                        </svg>
                                        Tampilkan QR Gate
                                    </button>

                                    <button
                                        type="button"
                                        @click="downloadTicketQr(ticket)"
                                        class="flex items-center gap-1.5 px-3 py-2 bg-white border border-slate-200 text-slate-700 text-xs font-semibold rounded-xl hover:bg-slate-50 transition shadow-2xs"
                                    >
                                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                        Unduh QR
                                    </button>

                                    <button
                                        type="button"
                                        @click="shareToWhatsApp(ticket)"
                                        class="flex items-center gap-1.5 px-3 py-2 bg-white border border-slate-200 text-slate-700 text-xs font-semibold rounded-xl hover:bg-slate-50 transition shadow-2xs"
                                    >
                                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86.174.086.275.073.376-.044.101-.116.433-.506.549-.68.116-.173.231-.145.39-.086s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.1.824z"/></svg>
                                        Bagikan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Sidebar: Venue & QR Gate Info -->
                    <div class="space-y-4">
                        <!-- Venue Guide -->
                        <div class="p-5 bg-white border border-slate-200 rounded-2xl shadow-sm">
                            <div class="flex items-center gap-2 mb-3">
                                <svg class="w-4 h-4 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Panduan Masuk Venue</h3>
                            </div>
                            <div class="space-y-3 text-xs text-slate-600">
                                <div class="flex items-start gap-2.5">
                                    <span class="w-5 h-5 rounded-full bg-sky-100 text-sky-700 font-bold text-[10px] flex items-center justify-center shrink-0 mt-0.5">1</span>
                                    <p>Tunjukkan QR Code langsung dari layar ponsel Anda kepada petugas turnstile gate.</p>
                                </div>
                                <div class="flex items-start gap-2.5">
                                    <span class="w-5 h-5 rounded-full bg-sky-100 text-sky-700 font-bold text-[10px] flex items-center justify-center shrink-0 mt-0.5">2</span>
                                    <p>Tingkatkan kecerahan layar ponsel Anda hingga 100% untuk mempermudah barcode scanner.</p>
                                </div>
                                <div class="flex items-start gap-2.5">
                                    <span class="w-5 h-5 rounded-full bg-sky-100 text-sky-700 font-bold text-[10px] flex items-center justify-center shrink-0 mt-0.5">3</span>
                                    <p>Satu QR Code hanya berlaku untuk satu kali masuk (single entry pass).</p>
                                </div>
                            </div>
                        </div>


                    </div>
                </div>
            </div>
        </div>

        <!-- Full-screen QR Check-In Modal -->
        <Modal
            :show="showTicketModal"
            title="E-Tiket Check-In Gate Pass"
            maxWidth="sm"
            @close="showTicketModal = false"
        >
            <div v-if="selectedTicket" class="text-center py-2 space-y-4">
                <p class="text-xs font-bold text-sky-600 uppercase tracking-wider">
                    {{ selectedTicket.ticket_category?.event?.name || 'Konser Musik' }}
                </p>
                <h4 class="text-lg font-extrabold text-slate-900 leading-tight">
                    {{ selectedTicket.ticket_category?.name }}
                </h4>
                <p class="text-xs text-slate-500">
                    Arahkan kode QR ini ke kamera / scanner turnstile gate venue konser.
                </p>

                <!-- REAL DYNAMIC HIGH-RES QR CODE -->
                <div class="inline-block p-4 bg-white rounded-2xl border-2 border-sky-400 shadow-md">
                    <img
                        v-if="modalQrUrl"
                        :src="modalQrUrl"
                        :alt="selectedTicket.ticket_code"
                        class="w-48 h-48 mx-auto object-contain"
                    />
                    <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                        <span class="font-mono font-extrabold text-slate-900">{{ selectedTicket.ticket_code }}</span>
                        <span class="text-[10px] text-emerald-600 font-bold flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            Terverifikasi
                        </span>
                    </div>
                </div>

                <!-- Security timer & brightness tip -->
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs space-y-1.5">
                    <div class="flex items-center justify-between text-slate-600">
                        <span class="text-[11px] font-medium">Validasi Keamanan Dinamis:</span>
                        <span class="font-mono font-bold text-sky-600">{{ countdown }} detik</span>
                    </div>
                    <p class="text-[11px] text-slate-400 leading-tight">
                        Tingkatkan kecerahan layar ponsel Anda agar pemindaian lebih cepat di gate.
                    </p>
                </div>

                <!-- Status indicator -->
                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full border text-xs font-bold" :class="getStatusClass(selectedTicket.status)">
                        <span class="w-2 h-2 rounded-full" :class="selectedTicket.status === 'valid' || selectedTicket.status === 'active' ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                        {{ getStatusLabel(selectedTicket.status) }}
                    </span>
                </div>
            </div>

            <template #footer>
                <div class="flex items-center justify-between w-full gap-2">
                    <button
                        type="button"
                        @click="downloadTicketQr(selectedTicket)"
                        class="px-3.5 py-2 text-xs font-semibold text-sky-700 bg-sky-50 border border-sky-200 rounded-xl hover:bg-sky-100 transition shadow-2xs"
                    >
                        Unduh Gambar QR
                    </button>
                    <button
                        type="button"
                        @click="showTicketModal = false"
                        class="px-5 py-2 text-xs font-bold text-white bg-slate-900 rounded-xl hover:bg-slate-800 transition"
                    >
                        Tutup
                    </button>
                </div>
            </template>
        </Modal>


    </CustomerLayout>
</template>
