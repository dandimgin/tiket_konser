<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { api } from '@/services/api';
import { formatRupiah, formatDate } from '@/stores/auth';
import { searchQuery, selectedLocation, selectedCategory, resetSearch } from '@/stores/search';

function handleSearchSubmit() {
    // Search is reactive - just navigate to homepage if not already there
}

const events = ref([]);
const loading = ref(true);
const error = ref(null);

// Carousel state
const currentSlide = ref(0);
let slideTimer = null;
const isHovered = ref(false);

async function fetchEvents() {
    loading.value = true;
    error.value = null;
    try {
        const res = await api.getEvents();
        events.value = res.data || [];
    } catch (err) {
        error.value = err.message || 'Gagal memuat daftar event konser dari server.';
    } finally {
        loading.value = false;
    }
}

const carouselEvents = computed(() => {
    const pub = events.value.filter(e => e.status === 'published');
    return pub.length > 0 ? pub.slice(0, 5) : events.value.slice(0, 5);
});

const currentEvent = computed(() => {
    if (carouselEvents.value.length === 0) return null;
    return carouselEvents.value[currentSlide.value % carouselEvents.value.length];
});

function nextSlide() {
    if (carouselEvents.value.length <= 1) return;
    currentSlide.value = (currentSlide.value + 1) % carouselEvents.value.length;
}

function prevSlide() {
    if (carouselEvents.value.length <= 1) return;
    currentSlide.value = (currentSlide.value - 1 + carouselEvents.value.length) % carouselEvents.value.length;
}

function goToSlide(index) {
    currentSlide.value = index;
    resetAutoplay();
}

function startAutoplay() {
    stopAutoplay();
    slideTimer = setInterval(() => {
        if (!isHovered.value && carouselEvents.value.length > 1) {
            nextSlide();
        }
    }, 5000);
}

function stopAutoplay() {
    if (slideTimer) {
        clearInterval(slideTimer);
        slideTimer = null;
    }
}

function resetAutoplay() {
    stopAutoplay();
    startAutoplay();
}

const locations = computed(() => {
    const set = new Set();
    events.value.forEach(e => {
        if (e.location) set.add(e.location);
    });
    return Array.from(set);
});

// Unique artists from events
const popularArtists = computed(() => {
    const artistMap = new Map();
    events.value.forEach(e => {
        if (e.artists) {
            e.artists.forEach(a => {
                if (!artistMap.has(a.id)) {
                    artistMap.set(a.id, { ...a, eventCount: 1 });
                } else {
                    artistMap.get(a.id).eventCount++;
                }
            });
        }
    });
    return Array.from(artistMap.values()).slice(0, 6);
});

const filteredEvents = computed(() => {
    return events.value.filter(e => {
        const q = (searchQuery.value || '').toLowerCase().trim();
        const matchesSearch = !q ||
            (e.name && e.name.toLowerCase().includes(q)) ||
            (e.description && e.description.toLowerCase().includes(q)) ||
            (e.location && e.location.toLowerCase().includes(q)) ||
            (e.artists && e.artists.some(a => a.name && a.name.toLowerCase().includes(q)));

        const matchesLocation = !selectedLocation.value || e.location === selectedLocation.value;

        const cat = (selectedCategory.value || '').toLowerCase().trim();
        const matchesCategory = !cat ||
            (e.name && e.name.toLowerCase().includes(cat)) ||
            (e.description && e.description.toLowerCase().includes(cat));

        return matchesSearch && matchesLocation && matchesCategory;
    });
});

function getLowestPrice(event) {
    if (!event.ticket_categories || event.ticket_categories.length === 0) return null;
    const prices = event.ticket_categories.map(c => Number(c.price));
    return Math.min(...prices);
}

function getStatusLabel(event) {
    if (event.status !== 'published') return 'Belum Buka';
    if (!event.ticket_categories || event.ticket_categories.length === 0) return 'Segera Hadir';
    const remaining = event.ticket_categories.reduce((acc, c) => acc + Math.max(0, c.quota - c.sold), 0);
    if (remaining <= 0) return 'Habis Terjual';
    return 'Tersedia';
}

function getStatusBadgeClass(event) {
    const label = getStatusLabel(event);
    if (label === 'Habis Terjual') return 'bg-rose-100 text-rose-700 border-rose-200';
    if (label === 'Belum Buka' || label === 'Segera Hadir') return 'bg-amber-100 text-amber-700 border-amber-200';
    return 'bg-emerald-100 text-emerald-700 border-emerald-200';
}

// Category labels derived from event names/description
const categoryChips = [
    { id: '', name: 'Semua Kategori' },
    { id: 'konser', name: 'Konser Musik' },
    { id: 'festival', name: 'Festival' },
    { id: 'jazz', name: 'Jazz & Acoustic' },
    { id: 'indie', name: 'Indie & Pop' },
    { id: 'k-pop', name: 'K-Pop Showcase' },
    { id: 'stand up', name: 'Stand-up & Seni' },
];

onMounted(() => {
    fetchEvents().then(() => {
        startAutoplay();
    });
});

onUnmounted(() => {
    stopAutoplay();
});
</script>

<template>
    <CustomerLayout>
        <Head title="Jelajah Tiket Konser Musik Resmi - Tiketin" />

        <!-- Hero Section -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 pb-0">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Left: Main Hero Banner -->
                <div class="lg:col-span-2">
                    <!-- Hero Copy -->
                    <div class="mb-4">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sky-50 text-sky-700 border border-sky-100 mb-2">
                            Platform Tiket Konser Resmi
                        </span>
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-tight mb-2">
                            Amankan Tiket Konser<br class="hidden sm:block"/> Musisi Favoritmu<br class="hidden sm:block"/> Lebih Awal
                        </h1>
                        <p class="text-sm text-slate-500 mb-4 max-w-lg">
                            Nikmati transaksi instan tanpa antre panjang. Garansi e-voucher barcode 100% tervalidasi di gerbang venue konser.
                        </p>
                        <div class="flex items-center gap-3">
                            <Link href="/#katalog-event" class="inline-flex items-center gap-2 px-4 py-2.5 bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold rounded-xl transition shadow-xs">
                                Beli Tiket Sekarang
                            </Link>
                            <Link href="/#katalog-event" class="inline-flex items-center gap-2 px-4 py-2.5 text-slate-700 text-sm font-semibold rounded-xl border border-slate-200 hover:bg-slate-50 transition">
                                Cek Kalender Event
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Right: Featured Event Card -->
                <div class="hidden lg:block">
                    <div v-if="loading" class="h-36 bg-slate-100 rounded-2xl animate-pulse"></div>
                    <div v-else-if="carouselEvents.length > 0" class="p-4 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between h-full">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold text-sky-700">Event Rekomendasi</span>
                            <span class="text-[11px] font-medium text-slate-400">Resmi</span>
                        </div>
                        <div class="flex items-center gap-3 mb-3">
                            <img
                                v-if="carouselEvents[0]?.poster"
                                :src="carouselEvents[0].poster"
                                :alt="carouselEvents[0].name"
                                class="w-14 h-14 rounded-xl object-cover shrink-0 border border-slate-100"
                                @error="$event.target.style.display='none'"
                            />
                            <div v-else class="w-14 h-14 rounded-xl bg-sky-50 border border-sky-100 flex items-center justify-center shrink-0">
                                <svg class="w-7 h-7 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[11px] text-slate-400 truncate">{{ carouselEvents[0]?.location }}</p>
                                <h3 class="text-xs font-bold text-slate-900 leading-snug line-clamp-2">{{ carouselEvents[0]?.name }}</h3>
                            </div>
                        </div>
                        <div class="flex items-center justify-between pt-2.5 border-t border-slate-100">
                            <div>
                                <p class="text-[10px] text-slate-400">Mulai dari</p>
                                <p class="text-xs font-bold text-slate-900">{{ formatRupiah(getLowestPrice(carouselEvents[0]) || 0) }}</p>
                            </div>
                            <Link :href="`/events/${carouselEvents[0]?.id}`" class="px-3 py-1.5 bg-sky-50 hover:bg-sky-100 text-sky-700 text-xs font-bold rounded-lg border border-sky-200/80 transition">
                                Pesan Cepat
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Category Filter Chips -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-5">
            <div class="p-1.5 bg-white/80 backdrop-blur-md border border-slate-200/70 rounded-2xl flex items-center gap-1.5 overflow-x-auto no-scrollbar shadow-xs">
                <button
                    v-for="chip in categoryChips"
                    :key="chip.id"
                    type="button"
                    @click="selectedCategory = chip.id"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition shrink-0"
                    :class="selectedCategory === chip.id
                        ? 'bg-sky-600 text-white shadow-xs'
                        : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70'"
                >
                    {{ chip.name }}
                </button>

                <!-- Location select -->
                <div class="ml-auto shrink-0 hidden sm:block pr-1">
                    <select
                        v-model="selectedLocation"
                        class="px-3 py-1.5 bg-slate-50 border border-slate-200/80 rounded-xl text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-500 transition cursor-pointer"
                    >
                        <option value="">Semua Lokasi</option>
                        <option v-for="loc in locations" :key="loc" :value="loc">{{ loc }}</option>
                    </select>
                </div>
            </div>
        </section>

        <!-- Artists Populer -->
        <section v-if="!loading && popularArtists.length > 0" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <h2 class="text-base font-bold text-slate-900">Musisi & Artis Populer</h2>
                </div>
                <button type="button" class="text-xs font-semibold text-sky-600 hover:text-sky-700 transition">Lihat Semua Artis</button>
            </div>
            <div class="flex items-center gap-4 overflow-x-auto pb-2 no-scrollbar">
                <div
                    v-for="artist in popularArtists"
                    :key="artist.id"
                    class="flex flex-col items-center gap-2 shrink-0 cursor-pointer group"
                    @click="searchQuery = artist.name; handleSearchSubmit()"
                >
                    <div class="w-16 h-16 rounded-full overflow-hidden border-2 border-slate-200 group-hover:border-sky-400 transition">
                        <img
                            v-if="artist.photo"
                            :src="artist.photo"
                            :alt="artist.name"
                            class="w-full h-full object-cover"
                            @error="$event.target.style.display='none'"
                        />
                        <div v-else class="w-full h-full bg-gradient-to-br from-sky-100 to-sky-200 flex items-center justify-center text-sky-600 font-bold text-xl">
                            {{ artist.name?.charAt(0)?.toUpperCase() }}
                        </div>
                    </div>
                    <div class="text-center">
                        <p class="text-xs font-semibold text-slate-900 group-hover:text-sky-600 transition truncate max-w-[70px]">{{ artist.name }}</p>
                        <p class="text-[10px] text-slate-400">{{ artist.eventCount }} konser</p>
                        <p class="text-[10px] text-sky-500 font-medium">lihat &rsaquo;</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Events Section -->
        <section id="katalog-event" class="pt-8 pb-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Section Header -->
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-sky-50 border border-sky-100 flex items-center justify-center text-sky-600">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M21.707 5.293a1 1 0 0 0-1.079-.217L13 7.844V6a3 3 0 0 0-6 0v2.511l-3.379.845A2 2 0 0 0 2 11.298v3.404a2 2 0 0 0 1.621 1.942L6 17.241V19a3 3 0 0 0 5.816 1.035l8.812 2.754a1 1 0 0 0 1.303-.956V6a1 1 0 0 0-.224-.707zM9 6a1 1 0 0 1 2 0v1.511L9 8.011V6zm0 13a1 1 0 0 1-1.895-.378l1.895-.474V19zm11 1.332-13-4.062V9.73l13-3.25v13.852z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-extrabold text-slate-900 leading-tight">Event Terbaru & Paling Dinanti</h2>
                        <p class="text-xs text-slate-500">{{ filteredEvents.length }} Konser Tersedia</p>
                    </div>
                </div>
                <button type="button" class="text-xs font-semibold text-sky-600 hover:text-sky-700 flex items-center gap-1 transition">
                    Lihat Semua Event
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>

            <!-- Active Filter Alert -->
            <div
                v-if="searchQuery"
                class="mb-5 p-3 bg-sky-50 border border-sky-200 rounded-lg flex items-center justify-between text-xs text-sky-800"
            >
                <span>Hasil untuk: <strong>"{{ searchQuery }}"</strong></span>
                <button type="button" @click="searchQuery = ''" class="text-sky-600 hover:text-sky-800 font-bold">Hapus</button>
            </div>

            <!-- Loading Skeleton -->
            <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
                <div v-for="n in 8" :key="n" class="bg-white border border-slate-200 rounded-xl overflow-hidden animate-pulse">
                    <div class="aspect-[16/9] bg-slate-100"></div>
                    <div class="p-4 space-y-2.5">
                        <div class="h-3 bg-slate-100 rounded w-1/3"></div>
                        <div class="h-4 bg-slate-200 rounded w-4/5"></div>
                        <div class="h-3 bg-slate-100 rounded w-1/2"></div>
                        <div class="pt-3 border-t border-slate-100 flex justify-between">
                            <div class="h-4 bg-slate-200 rounded w-1/3"></div>
                            <div class="h-4 bg-slate-100 rounded w-1/4"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="max-w-md mx-auto my-8 p-6 bg-rose-50 border border-rose-200 rounded-xl text-center">
                <h3 class="text-sm font-bold text-rose-900 mb-1">Gagal Memuat Event</h3>
                <p class="text-xs text-rose-600 mb-4">{{ error }}</p>
                <button @click="fetchEvents" class="px-4 py-2 bg-rose-600 text-white rounded-lg text-xs font-semibold hover:bg-rose-700 transition">
                    Muat Ulang
                </button>
            </div>

            <!-- Empty State -->
            <EmptyState
                v-else-if="filteredEvents.length === 0"
                title="Tidak Ada Event Ditemukan"
                description="Belum ada event yang sesuai dengan pencarian atau filter Anda."
                actionText="Reset Pencarian"
                @action="resetSearch"
            />

            <!-- Event Cards Grid -->
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
                <Link
                    v-for="event in filteredEvents"
                    :key="event.id"
                    :href="`/events/${event.id}`"
                    class="group flex flex-col bg-white border border-slate-200/80 rounded-2xl overflow-hidden hover:border-sky-300 hover:shadow-md transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
                >
                    <!-- Poster -->
                    <div class="relative aspect-[16/9] bg-slate-100 overflow-hidden">
                        <img
                            v-if="event.poster"
                            :src="event.poster"
                            :alt="event.name"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300 ease-out"
                            loading="lazy"
                            @error="$event.target.style.display='none'"
                        />
                        <div
                            v-else
                            class="w-full h-full bg-gradient-to-br from-slate-800 to-sky-900 flex flex-col items-center justify-center p-3 text-white"
                        >
                            <svg class="w-8 h-8 text-sky-300/70 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
                            <span class="text-xs font-semibold text-white/90 text-center line-clamp-1">{{ event.name }}</span>
                        </div>

                        <!-- Status Badge (top-right only) -->
                        <div class="absolute top-2.5 right-2.5">
                            <span
                                class="text-[10px] font-semibold px-2 py-0.5 rounded-full border shadow-xs"
                                :class="getStatusBadgeClass(event)"
                            >
                                {{ getStatusLabel(event) }}
                            </span>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-4 flex flex-col grow">
                        <div class="flex items-center gap-1.5 text-[11px] font-medium text-slate-400 mb-1">
                            <span class="truncate">{{ event.location || 'Indonesia' }}</span>
                            <span v-if="event.ticket_categories?.length" class="text-slate-300">•</span>
                            <span v-if="event.ticket_categories?.length" class="text-sky-600 font-medium truncate">
                                {{ event.ticket_categories.length > 1 ? `${event.ticket_categories.length} Kategori` : event.ticket_categories[0]?.name }}
                            </span>
                        </div>
                        <h3 class="text-sm font-bold text-slate-900 group-hover:text-sky-600 transition-colors leading-snug line-clamp-2 mb-2">
                            {{ event.name }}
                        </h3>
                        <div class="flex items-center gap-1.5 text-xs text-slate-500 mb-auto">
                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            {{ formatDate(event.event_date) }}
                        </div>

                        <!-- Footer -->
                        <div class="mt-3 pt-3 border-t border-slate-100 flex items-end justify-between">
                            <div>
                                <span class="text-[10px] text-slate-400 block leading-tight">Mulai dari</span>
                                <span v-if="getLowestPrice(event) !== null" class="text-sm font-extrabold text-slate-900">
                                    {{ formatRupiah(getLowestPrice(event)) }}
                                </span>
                                <span v-else class="text-xs text-slate-400 italic">Belum Buka</span>
                            </div>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-sky-50 text-sky-600 border border-sky-200 rounded-lg text-xs font-bold group-hover:bg-sky-600 group-hover:text-white transition">
                                Pilih Tiket
                            </span>
                        </div>
                    </div>
                </Link>
            </div>
        </section>        <!-- Why Tiketin Section (Trust Strip) -->
        <section v-if="!loading && events.length > 0" class="border-t border-slate-200/70 bg-slate-50/50 py-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white rounded-2xl border border-slate-200/80 p-6 sm:p-8 shadow-xs">
                    <div class="max-w-xl mb-6">
                        <h2 class="text-base sm:text-lg font-bold text-slate-900 mb-1">Standar Keamanan Tiketin</h2>
                        <p class="text-xs text-slate-500">Platform tiket terverifikasi langsung dari promotor resmi dengan jaminan keaslian QR code.</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-5 border-t border-slate-100">
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-xl bg-sky-50 border border-sky-100 flex items-center justify-center shrink-0 text-sky-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-slate-900 mb-0.5">QR Gate Resmi</h3>
                                <p class="text-[11px] text-slate-500 leading-relaxed">Kode QR tervalidasi langsung di gate pemindai promotor konser.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-xl bg-sky-50 border border-sky-100 flex items-center justify-center shrink-0 text-sky-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-slate-900 mb-0.5">E-Tiket Instan</h3>
                                <p class="text-[11px] text-slate-500 leading-relaxed">Tiket otomatis terbit dan dapat diakses di menu Tiket Saya.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-xl bg-sky-50 border border-sky-100 flex items-center justify-center shrink-0 text-sky-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-slate-900 mb-0.5">Proteksi Transaksi</h3>
                                <p class="text-[11px] text-slate-500 leading-relaxed">Metode pembayaran aman dan terverifikasi otomatis.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- WhatsApp Alert Banner -->
        <section class="border-t border-slate-200/70 py-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-6 bg-slate-900 rounded-2xl border border-slate-800">
                    <div>
                        <h2 class="text-base font-bold text-white mb-1">Jangan Sampai Kehabisan War Tiket!</h2>
                        <p class="text-xs text-slate-400">Dapatkan notifikasi WhatsApp otomatis 15 menit sebelum penjualan tiket dimulai.</p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <input
                            type="text"
                            placeholder="Ketik nomor WhatsApp..."
                            class="px-3 py-2 bg-white/10 border border-white/20 text-white placeholder-slate-400 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-sky-400 w-full sm:w-52"
                        />
                        <button type="button" class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white text-xs font-semibold rounded-xl transition shrink-0">
                            Aktifkan Notifikasi
                        </button>
                    </div>
                </div>
            </div>
        </section>
    </CustomerLayout>
</template>
