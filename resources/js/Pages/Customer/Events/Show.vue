<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import { api } from '@/services/api';
import { formatRupiah, formatDate } from '@/stores/auth';

const props = defineProps({
    eventId: {
        type: Number,
        required: true,
    },
});

const event = ref(null);
const artists = ref([]);
const ticketCategories = ref([]);
const loading = ref(true);
const error = ref(null);

// Ticket quantity selections
const quantities = ref({});

async function fetchEventDetails() {
    loading.value = true;
    error.value = null;
    try {
        const eventRes = await api.getEvent(props.eventId);
        event.value = eventRes.data;
        artists.value = event.value.artists || [];
        ticketCategories.value = event.value.ticket_categories || [];

        if (artists.value.length === 0) {
            try {
                const aRes = await api.getEventArtists(props.eventId);
                artists.value = aRes.data || [];
            } catch {}
        }
        if (ticketCategories.value.length === 0) {
            try {
                const cRes = await api.getTicketCategories(props.eventId);
                ticketCategories.value = cRes.data || [];
            } catch {}
        }

        // Init quantities
        ticketCategories.value.forEach(cat => {
            quantities.value[cat.id] = 0;
        });
    } catch (err) {
        error.value = err.message || 'Gagal memuat detail konser.';
    } finally {
        loading.value = false;
    }
}

function updateQty(catId, delta) {
    const cat = ticketCategories.value.find(c => c.id === catId);
    if (!cat) return;
    const available = cat.quota - cat.sold;
    const current = quantities.value[catId] || 0;
    const next = current + delta;
    if (next < 0 || next > Math.min(available, 5)) return;
    quantities.value[catId] = next;
}

const subtotal = computed(() => {
    return ticketCategories.value.reduce((sum, cat) => {
        return sum + (Number(cat.price) * (quantities.value[cat.id] || 0));
    }, 0);
});

const serviceFee = computed(() => Math.round(subtotal.value * 0.05));
const total = computed(() => subtotal.value + serviceFee.value);

const hasSelection = computed(() => Object.values(quantities.value).some(v => v > 0));

const isAvailableForBooking = computed(() => {
    if (!event.value) return false;
    if (event.value.status !== 'published') return false;
    return ticketCategories.value.some(cat => (cat.quota - cat.sold) > 0);
});

function getCategoryBadgeClass(cat) {
    const remaining = cat.quota - cat.sold;
    if (remaining <= 0) return 'bg-rose-100 text-rose-700 border-rose-200';
    if (remaining < 50) return 'bg-amber-100 text-amber-700 border-amber-200';
    return 'bg-emerald-100 text-emerald-700 border-emerald-200';
}

function getCategoryBadgeLabel(cat) {
    const remaining = cat.quota - cat.sold;
    if (remaining <= 0) return 'Habis Terjual';
    if (remaining < 50) return 'Sisa Sedikit';
    return 'Tersedia';
}

// Check if first available category
const firstAvailCatId = computed(() => {
    const found = ticketCategories.value.find(c => (c.quota - c.sold) > 0);
    return found?.id || null;
});

onMounted(() => {
    fetchEventDetails();
});
</script>

<template>
    <CustomerLayout>
        <Head :title="event ? `${event.name} - Tiketin` : 'Detail Event Konser'" />

        <!-- Breadcrumbs -->
        <div class="border-b border-slate-100 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
                <nav class="flex items-center gap-1.5 text-xs text-slate-400 font-medium">
                    <Link href="/" class="hover:text-slate-700 transition">Beranda</Link>
                    <span>/</span>
                    <span class="hover:text-slate-700 cursor-default">Konser Musik</span>
                    <span>/</span>
                    <span class="text-slate-700 font-semibold truncate max-w-[200px]">{{ event?.name || 'Memuat...' }}</span>
                </nav>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="animate-pulse space-y-6">
                <div class="h-80 bg-slate-100 rounded-2xl"></div>
                <div class="grid grid-cols-3 gap-4">
                    <div class="h-8 bg-slate-200 rounded col-span-2"></div>
                    <div class="h-8 bg-slate-100 rounded"></div>
                </div>
            </div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="max-w-md mx-auto my-16 p-6 bg-rose-50 border border-rose-200 rounded-2xl text-center">
            <h2 class="text-sm font-bold text-rose-900 mb-1">Gagal Memuat Event</h2>
            <p class="text-xs text-rose-600 mb-4">{{ error }}</p>
            <Link href="/" class="inline-block px-4 py-2 bg-slate-900 text-white rounded-xl text-xs font-semibold hover:bg-slate-800 transition">
                Kembali ke Beranda
            </Link>
        </div>

        <!-- Event Detail Body -->
        <div v-else-if="event" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start">
                <!-- Left Column -->
                <div class="lg:col-span-8 space-y-6">
                    <!-- Hero Poster -->
                    <div class="rounded-2xl overflow-hidden bg-slate-900 relative shadow-xs">
                        <img
                            v-if="event.poster"
                            :src="event.poster"
                            :alt="event.name"
                            class="w-full max-h-[440px] object-cover"
                            @error="$event.target.style.display='none'"
                        />
                        <div v-else class="h-72 sm:h-[360px] flex flex-col items-center justify-center text-white bg-gradient-to-br from-slate-900 via-slate-800 to-sky-950 p-6 text-center">
                            <div class="w-14 h-14 rounded-xl bg-sky-500/20 border border-sky-400/30 flex items-center justify-center mb-3 text-sky-300">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
                            </div>
                            <span class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight mb-1">{{ event.name }}</span>
                            <span class="text-xs text-sky-300/80 font-medium">Official Concert Event &bull; Tiketin</span>
                        </div>

                        <!-- Action button -->
                        <button type="button" class="absolute top-3 right-3 w-8 h-8 rounded-xl bg-white/90 hover:bg-white flex items-center justify-center text-slate-700 shadow-sm transition" aria-label="Bagikan event">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                        </button>
                    </div>

                    <!-- Event Info -->
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-tight mb-4">
                            {{ event.name }}
                        </h1>

                        <!-- Info Metadata Bar -->
                        <div class="flex flex-wrap items-center gap-y-3 gap-x-6 py-3.5 px-4.5 rounded-2xl bg-slate-50/80 border border-slate-200/70 text-xs">
                            <div class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-sky-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <div>
                                    <span class="text-slate-400 block text-[10px] font-medium">JADWAL KONSER</span>
                                    <span class="font-semibold text-slate-800">{{ formatDate(event.event_date, true) }}</span>
                                </div>
                            </div>
                            <div class="h-6 w-px bg-slate-200 hidden sm:block"></div>
                            <div class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-sky-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <div>
                                    <span class="text-slate-400 block text-[10px] font-medium">LOKASI VENUE</span>
                                    <span class="font-semibold text-slate-800">{{ event.location }}</span>
                                </div>
                            </div>
                            <div class="h-6 w-px bg-slate-200 hidden sm:block"></div>
                            <div class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                <div>
                                    <span class="text-slate-400 block text-[10px] font-medium">TIKET RESMI</span>
                                    <span class="font-semibold text-slate-800">Garansi Masuk 100%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Artist Lineup -->
                    <div v-if="artists.length > 0" class="p-5 rounded-xl border border-slate-200 bg-white">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-base font-bold text-slate-900">Lineup Artis & Penampil Tamu</h2>
                            <span class="text-xs font-semibold text-slate-500 bg-slate-100 px-2 py-1 rounded-lg">{{ artists.length }} Penampil</span>
                        </div>
                        <p class="text-xs text-slate-500 mb-4">Artis legendaris pop-rock Indonesia dan musisi kolaborasi panggung dunia.</p>
                        <div class="flex items-center gap-4 overflow-x-auto pb-1 no-scrollbar">
                            <div
                                v-for="(artist, idx) in artists"
                                :key="artist.id"
                                class="flex flex-col items-center gap-2 shrink-0 min-w-[70px] text-center"
                            >
                                <div class="relative">
                                    <img
                                        v-if="artist.photo"
                                        :src="artist.photo"
                                        :alt="artist.name"
                                        class="w-14 h-14 rounded-full object-cover border-2 border-slate-200"
                                        @error="$event.target.style.display='none'"
                                    />
                                    <div v-else class="w-14 h-14 rounded-full bg-gradient-to-br from-sky-100 to-sky-200 flex items-center justify-center text-sky-700 font-bold text-xl border-2 border-sky-200">
                                        {{ artist.name?.charAt(0)?.toUpperCase() }}
                                    </div>
                                    <span v-if="idx === 0" class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-sky-600 text-white text-[9px] font-bold flex items-center justify-center">1</span>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-slate-900 line-clamp-1">{{ artist.name }}</p>
                                    <p class="text-[10px] text-slate-400">{{ idx === 0 ? 'Headliner' : idx === 1 ? 'Special Guest' : 'Opening Act' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="p-5 rounded-xl border border-slate-200 bg-white">
                        <h2 class="text-base font-bold text-slate-900 mb-3">Tentang Konser Ini</h2>
                        <div class="text-sm text-slate-600 leading-relaxed whitespace-pre-line">
                            {{ event.description || 'Deskripsi event belum tersedia.' }}
                        </div>
                    </div>

                    <!-- Terms & Conditions -->
                    <div class="p-5 rounded-xl border border-slate-200 bg-slate-50">
                        <div class="flex items-center gap-2 mb-3">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <h2 class="text-sm font-bold text-slate-900">Syarat & Ketentuan Masuk</h2>
                        </div>
                        <ul class="space-y-2">
                            <li class="flex items-start gap-2 text-xs text-slate-600">
                                <svg class="w-3.5 h-3.5 text-slate-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/></svg>
                                Tiket QR masuk dapat diakses dari halaman Tiket Saya. Bawa ID resmi (KTP/Paspor).
                            </li>
                            <li class="flex items-start gap-2 text-xs text-slate-600">
                                <svg class="w-3.5 h-3.5 text-slate-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/></svg>
                                E-voucher hanya diaktivasi sekali. Pastikan barcode/QR dibuka saat di gerbang masuk.
                            </li>
                            <li class="flex items-start gap-2 text-xs text-slate-600">
                                <svg class="w-3.5 h-3.5 text-slate-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/></svg>
                                Dilarang membawa kamera profesional (DSLR/Mirrorless) ke dalam area festival.
                            </li>
                            <li class="flex items-start gap-2 text-xs text-slate-600">
                                <svg class="w-3.5 h-3.5 text-slate-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/></svg>
                                Pengunjung di bawah usia 12 tahun wajib didampingi orang dewasa dan tiket berlaku sama.
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Right Column: Ticket Selector -->
                <div class="lg:col-span-4 lg:sticky lg:top-24 space-y-4">
                    <div class="p-5 rounded-2xl border border-slate-200/80 bg-white/90 backdrop-blur-md shadow-sm">
                        <div class="flex items-center justify-between mb-1">
                            <h2 class="text-base font-bold text-slate-900">Pilih Kategori Tiket</h2>
                            <span class="text-[10px] font-semibold text-sky-700 bg-sky-50 border border-sky-200/70 px-2 py-0.5 rounded-lg">Resmi & Terverifikasi</span>
                        </div>
                        <p class="text-xs text-slate-400 mb-4">Maksimal 5 tiket per transaksi</p>

                        <!-- No categories -->
                        <div v-if="ticketCategories.length === 0" class="py-6 text-center text-xs text-slate-400">
                            Kategori tiket belum dibuka.
                        </div>

                        <!-- Category List -->
                        <div v-else class="space-y-3 mb-5">
                            <div
                                v-for="cat in ticketCategories"
                                :key="cat.id"
                                class="p-4 rounded-xl border transition"
                                :class="(cat.quota - cat.sold) > 0
                                    ? 'border-slate-200/80 bg-white hover:border-sky-300'
                                    : 'border-slate-100 bg-slate-50/50 opacity-60'"
                            >
                                <div class="flex items-start justify-between gap-2 mb-1">
                                    <div>
                                        <div class="flex items-center gap-2 mb-0.5">
                                            <h3 class="text-sm font-bold text-slate-900">{{ cat.name }}</h3>
                                        </div>
                                        <p class="text-[11px] text-slate-500">{{ (cat.quota - cat.sold) > 0 ? `Tersedia ${cat.quota - cat.sold} tiket` : 'Akses semua area penonton' }}</p>
                                    </div>
                                    <span
                                        class="text-[10px] font-bold px-2 py-0.5 rounded border shrink-0"
                                        :class="getCategoryBadgeClass(cat)"
                                    >
                                        {{ getCategoryBadgeLabel(cat) }}
                                    </span>
                                </div>

                                <div class="flex items-center justify-between mt-2">
                                    <div>
                                        <p class="text-base font-extrabold text-slate-900">{{ formatRupiah(cat.price) }}</p>
                                        <p class="text-[10px] text-slate-400">per tiket</p>
                                    </div>

                                    <!-- Qty Counter -->
                                    <div v-if="(cat.quota - cat.sold) > 0" class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            @click="updateQty(cat.id, -1)"
                                            :disabled="!quantities[cat.id] || quantities[cat.id] <= 0"
                                            class="w-8 h-8 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-100 disabled:opacity-30 disabled:cursor-not-allowed flex items-center justify-center font-bold text-lg transition"
                                            aria-label="Kurangi tiket"
                                        >
                                            <span class="leading-none -mt-0.5">-</span>
                                        </button>
                                        <span class="w-6 text-center text-sm font-bold text-slate-900">{{ quantities[cat.id] || 0 }}</span>
                                        <button
                                            type="button"
                                            @click="updateQty(cat.id, 1)"
                                            :disabled="(quantities[cat.id] || 0) >= Math.min(cat.quota - cat.sold, 5)"
                                            class="w-8 h-8 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-100 disabled:opacity-30 disabled:cursor-not-allowed flex items-center justify-center font-bold text-lg transition"
                                            aria-label="Tambah tiket"
                                        >
                                            <span class="leading-none -mt-0.5">+</span>
                                        </button>
                                    </div>
                                    <span v-else class="text-xs font-semibold text-rose-500 italic">Habis Terjual</span>
                                </div>
                            </div>
                        </div>

                        <!-- Price Breakdown (when selected) -->
                        <div v-if="hasSelection" class="mb-4 p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-2">
                            <p class="font-semibold text-slate-600 mb-2">Rincian Pembayaran</p>
                            <template v-for="cat in ticketCategories" :key="cat.id">
                                <div v-if="quantities[cat.id] > 0" class="flex justify-between">
                                    <span class="text-slate-600">{{ cat.name }} x{{ quantities[cat.id] }}</span>
                                    <span class="font-semibold text-slate-900">{{ formatRupiah(Number(cat.price) * quantities[cat.id]) }}</span>
                                </div>
                            </template>
                            <div class="flex justify-between text-[11px] text-slate-400">
                                <span>Biaya Layanan & Pajak (5%)</span>
                                <span>{{ formatRupiah(serviceFee) }}</span>
                            </div>
                            <div class="flex justify-between font-bold text-slate-900 pt-2 border-t border-slate-200">
                                <span>Total</span>
                                <span class="text-sky-600">{{ formatRupiah(total) }}</span>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <Link
                            v-if="isAvailableForBooking"
                            :href="`/checkout/${event.id}`"
                            class="block w-full py-3.5 px-4 bg-sky-600 hover:bg-sky-700 text-white font-bold text-sm rounded-xl text-center transition shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
                        >
                            Beli Sekarang
                            <span class="ml-1">&rsaquo;</span>
                        </Link>
                        <button
                            v-else
                            disabled
                            class="w-full py-3.5 px-4 bg-slate-100 text-slate-400 font-bold text-sm rounded-xl cursor-not-allowed border border-slate-200"
                        >
                            {{ event.status !== 'published' ? 'Pemesanan Belum Dibuka' : 'Tiket Habis Terjual' }}
                        </button>

                        <!-- Trust badges -->
                        <div class="mt-3 flex items-center justify-center gap-4 text-[10px] text-slate-400">
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                100% Tiket Asli
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                Konfirmasi Instan
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                                Garansi Uang Kembali
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>
