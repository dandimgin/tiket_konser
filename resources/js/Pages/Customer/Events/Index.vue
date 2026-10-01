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

const locations = [
  'Ambon', 'Balikpapan', 'Banda Aceh', 'Bandar Lampung', 'Bandung', 'Banjar', 'Banjarbaru', 'Banjarmasin', 'Batam', 'Batu', 'Baubau', 'Bekasi', 'Bengkulu', 'Bima', 'Binjai', 'Bitung', 'Blitar', 'Bogor', 'Bontang', 'Bukittinggi', 'Cilegon', 'Cimahi', 'Cirebon', 'Denpasar', 'Depok', 'Dumai', 'Gorontalo', 'Gunungsitoli', 'Jakarta Barat', 'Jakarta Pusat', 'Jakarta Selatan', 'Jakarta Timur', 'Jakarta Utara', 'Jambi', 'Jayapura', 'Kediri', 'Kendari', 'Kotamobagu', 'Kupang', 'Langsa', 'Lhokseumawe', 'Lubuklinggau', 'Madiun', 'Magelang', 'Makassar', 'Malang', 'Manado', 'Mataram', 'Medan', 'Metro', 'Mojokerto', 'Padang', 'Padang Panjang', 'Padang Sidempuan', 'Pagar Alam', 'Palangka Raya', 'Palembang', 'Palopo', 'Palu', 'Pangkalpinang', 'Parepare', 'Pariaman', 'Pasuruan', 'Payakumbuh', 'Pekalongan', 'Pekanbaru', 'Pematangsiantar', 'Pontianak', 'Prabumulih', 'Probolinggo', 'Sabang', 'Salatiga', 'Samarinda', 'Sawahlunto', 'Semarang', 'Serang', 'Sibolga', 'Singkawang', 'Sorong', 'Subulussalam', 'Sukabumi', 'Sungai Penuh', 'Surabaya', 'Surakarta', 'Tangerang', 'Tangerang Selatan', 'Tanjungbalai', 'Tanjungpinang', 'Tarakan', 'Tasikmalaya', 'Tebing Tinggi', 'Tegal', 'Ternate', 'Tidore Kepulauan', 'Tomohon', 'Tual', 'Yogyakarta'
];

const showLocationDropdown = ref(false);
const locationSearch = ref('');
const locationDropdownRef = ref(null);

const filteredLocations = computed(() => {
    const q = locationSearch.value.toLowerCase().trim();
    if (!q) return locations;
    return locations.filter(loc => loc.toLowerCase().includes(q));
});

function closeLocationDropdown(e) {
    if (locationDropdownRef.value && !locationDropdownRef.value.contains(e.target)) {
        showLocationDropdown.value = false;
    }
}

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

        const matchesLocation = !selectedLocation.value || (e.location && e.location.toLowerCase().includes(selectedLocation.value.toLowerCase()));

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
    { id: '', name: 'Semua' },
    { id: 'international', name: 'International' },
    { id: 'indonesia', name: 'Indonesia' },
    { id: 'festival', name: 'Festival' },
];

onMounted(() => {
    fetchEvents().then(() => {
        startAutoplay();
    });
    document.addEventListener('click', closeLocationDropdown);
});

onUnmounted(() => {
    stopAutoplay();
    document.removeEventListener('click', closeLocationDropdown);
});
</script>

<template>
    <CustomerLayout>
        <Head title="Jelajah Tiket Konser Musik Resmi - Tiketin" />

        <!-- Hero Section -->
        <section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pt-16 pb-12 text-center">
            <span class="text-sm font-semibold text-blue-500 mb-6 block">
                Platform Tiket Konser Terpercaya
            </span>
            <h1 class="text-5xl sm:text-6xl md:text-7xl font-extrabold text-slate-900 tracking-tight leading-[1.1] mb-6">
                Temukan Konser<br/>Favoritmu
            </h1>
            <p class="text-base sm:text-lg text-slate-500 mb-10 max-w-2xl mx-auto">
                Beli tiket konser, festival, dan pertunjukan musik di Indonesia dengan mudah dan aman.
            </p>

            <!-- Search Bar -->
            <div class="relative max-w-3xl mx-auto mb-16">
                <div class="flex flex-col sm:flex-row items-center bg-white rounded-full p-2 shadow-[0_8px_30px_rgb(0,0,0,0.06)] border border-slate-100">
                    <div class="flex items-center flex-1 w-full sm:w-auto px-4 py-2 sm:py-0">
                        <svg class="w-5 h-5 text-slate-400 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input
                            v-model="searchQuery"
                            @keyup.enter="handleSearchSubmit"
                            type="text"
                            class="w-full bg-transparent border-0 focus:ring-0 text-slate-900 placeholder-slate-400 text-sm sm:text-base outline-none p-0"
                            placeholder="Cari konser, artis, atau festival..."
                        />
                    </div>
                    
                    <div class="hidden sm:block h-6 w-px bg-slate-200 mx-2"></div>
                    
                    <div class="flex items-center justify-between w-full sm:w-auto pl-4 pr-2 sm:px-2 py-2 sm:py-0 gap-4 sm:gap-2">
                        <div class="relative flex items-center" ref="locationDropdownRef">
                            <!-- Trigger Button -->
                            <button 
                                type="button" 
                                @click="showLocationDropdown = !showLocationDropdown"
                                class="flex items-center gap-2 pl-2 pr-2 py-2 bg-transparent text-slate-500 hover:text-slate-800 text-sm font-medium transition whitespace-nowrap border-0 focus:ring-0 outline-none truncate max-w-[130px] sm:max-w-[150px]"
                            >
                                <span class="truncate">{{ selectedLocation || 'Semua Kota' }}</span>
                                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>

                            <!-- Dropdown Menu -->
                            <div 
                                v-show="showLocationDropdown"
                                class="absolute top-full right-0 sm:left-0 mt-3 w-56 bg-white rounded-xl shadow-xl border border-slate-100 overflow-hidden z-50 transform origin-top"
                            >
                                <div class="p-2 border-b border-slate-100 bg-slate-50/50">
                                    <input 
                                        type="text" 
                                        v-model="locationSearch" 
                                        placeholder="Cari kota..." 
                                        class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-400 outline-none text-slate-900 placeholder-slate-400 transition"
                                        @click.stop
                                    />
                                </div>
                                <div class="max-h-64 overflow-y-auto p-1 text-left">
                                    <button 
                                        type="button" 
                                        @click="selectedLocation = ''; showLocationDropdown = false; locationSearch = ''"
                                        class="w-full text-left px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 rounded-lg transition"
                                        :class="{ 'font-bold text-blue-600 bg-blue-50/50': !selectedLocation }"
                                    >
                                        Semua Kota
                                    </button>
                                    <button 
                                        v-for="loc in filteredLocations" 
                                        :key="loc"
                                        type="button" 
                                        @click="selectedLocation = loc; showLocationDropdown = false; locationSearch = ''"
                                        class="w-full text-left px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 rounded-lg transition"
                                        :class="{ 'font-bold text-blue-600 bg-blue-50/50': selectedLocation === loc }"
                                    >
                                        {{ loc }}
                                    </button>
                                    <div v-if="filteredLocations.length === 0" class="px-3 py-4 text-center text-xs text-slate-400">
                                        Kota tidak ditemukan
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button
                            @click="handleSearchSubmit"
                            type="button"
                            class="bg-blue-500 hover:bg-blue-600 text-white px-8 py-3 rounded-full text-sm font-bold transition shadow-sm whitespace-nowrap ml-2"
                        >
                            Cari
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Category Filter Chips -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-10">
            <div class="flex items-center gap-3 overflow-x-auto no-scrollbar">
                <button
                    v-for="chip in categoryChips"
                    :key="chip.id"
                    type="button"
                    @click="selectedCategory = chip.id"
                    class="px-6 py-2 rounded-full text-sm font-medium transition shrink-0 border"
                    :class="selectedCategory === chip.id
                        ? 'bg-blue-500 text-white border-blue-500'
                        : 'bg-white text-slate-500 border-slate-200 hover:border-slate-300 hover:bg-slate-50'"
                >
                    {{ chip.name }}
                </button>
            </div>
        </section>

        <!-- Events Section -->
        <section id="katalog-event" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-16">
            <!-- Section Header -->
            <div class="flex items-end justify-between mb-6">
                <h2 class="text-2xl font-bold text-slate-900 leading-tight">Event Populer</h2>
                <p class="text-sm font-medium text-slate-400">{{ filteredEvents.length }} event</p>
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
                    class="group flex flex-col bg-white border border-slate-200/80 rounded-[1.25rem] overflow-hidden hover:border-blue-400 hover:shadow-xl transition-all duration-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
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
                            class="w-full h-full bg-gradient-to-br from-slate-800 to-blue-900 flex flex-col items-center justify-center p-3 text-white"
                        >
                            <svg class="w-8 h-8 text-blue-300/70 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
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
                            <span v-if="event.ticket_categories?.length" class="text-blue-600 font-medium truncate">
                                {{ event.ticket_categories.length > 1 ? `${event.ticket_categories.length} Kategori` : event.ticket_categories[0]?.name }}
                            </span>
                        </div>
                        <h3 class="text-[15px] font-bold text-slate-900 group-hover:text-blue-600 transition-colors leading-snug line-clamp-2 mb-2">
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
                            <span class="inline-flex items-center gap-1 px-3 py-1.5 bg-white text-blue-600 border border-slate-200 rounded-full text-xs font-bold group-hover:bg-blue-600 group-hover:text-white group-hover:border-blue-600 transition duration-300 shadow-sm">
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
                            <div class="w-9 h-9 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center shrink-0 text-blue-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-slate-900 mb-0.5">QR Gate Resmi</h3>
                                <p class="text-[11px] text-slate-500 leading-relaxed">Kode QR tervalidasi langsung di gate pemindai promotor konser.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center shrink-0 text-blue-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-slate-900 mb-0.5">E-Tiket Instan</h3>
                                <p class="text-[11px] text-slate-500 leading-relaxed">Tiket otomatis terbit dan dapat diakses di menu Tiket Saya.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center shrink-0 text-blue-600">
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
                            class="px-3 py-2 bg-white/10 border border-white/20 text-white placeholder-slate-400 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-blue-400 w-full sm:w-52"
                        />
                        <button type="button" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold rounded-xl transition shrink-0">
                            Aktifkan Notifikasi
                        </button>
                    </div>
                </div>
            </div>
        </section>
    </CustomerLayout>
</template>
