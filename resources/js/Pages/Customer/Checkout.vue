<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import { api } from '@/services/api';
import { auth, formatRupiah, formatDate } from '@/stores/auth';
import { useToast } from '@/composables/useToast';

const props = defineProps({
    eventId: {
        type: Number,
        required: true,
    },
});

const toast = useToast();
const event = ref(null);
const ticketCategories = ref([]);
const loading = ref(true);
const submitting = ref(false);
const error = ref(null);

const selections = reactive({});
const agreedToTerms = ref(false);

async function loadData() {
    loading.value = true;
    error.value = null;
    try {
        const eventRes = await api.getEvent(props.eventId);
        event.value = eventRes.data;
        ticketCategories.value = event.value.ticket_categories || [];

        if (ticketCategories.value.length === 0) {
            const catRes = await api.getTicketCategories(props.eventId);
            ticketCategories.value = catRes.data || [];
        }

        ticketCategories.value.forEach(cat => {
            selections[cat.id] = 0;
        });

        const firstAvail = ticketCategories.value.find(c => (c.quota - c.sold) > 0);
        if (firstAvail) {
            selections[firstAvail.id] = 1;
        }
    } catch (err) {
        error.value = err.message || 'Gagal memuat data checkout.';
    } finally {
        loading.value = false;
    }
}

function updateQty(categoryId, delta) {
    const cat = ticketCategories.value.find(c => c.id === categoryId);
    if (!cat) return;
    const available = cat.quota - cat.sold;
    const current = selections[categoryId] || 0;
    const next = current + delta;

    if (next < 0) return;
    if (next > available) {
        toast.error(`Maksimal ${available} tiket untuk kategori ${cat.name}`);
        return;
    }

    selections[categoryId] = next;
}

const selectedItems = computed(() => {
    return ticketCategories.value
        .filter(cat => (selections[cat.id] || 0) > 0)
        .map(cat => ({
            ticket_category_id: cat.id,
            name: cat.name,
            price: Number(cat.price),
            quantity: selections[cat.id],
            subtotal: Number(cat.price) * selections[cat.id],
        }));
});

const totalAmount = computed(() => selectedItems.value.reduce((sum, item) => sum + item.subtotal, 0));
const serviceFee = computed(() => 15000);
const taxAmount = computed(() => Math.round(totalAmount.value * 0.10));

const grandTotal = computed(() => totalAmount.value + serviceFee.value + taxAmount.value);
const totalQuantity = computed(() => selectedItems.value.reduce((sum, item) => sum + item.quantity, 0));

async function handleCheckout() {
    if (!auth.isAuthenticated.value) {
        toast.error('Silakan login terlebih dahulu untuk membuat pesanan.');
        router.visit('/login');
        return;
    }

    if (selectedItems.value.length === 0) {
        toast.error('Pilih minimal 1 tiket sebelum membuat pesanan.');
        return;
    }

    if (!agreedToTerms.value) {
        toast.error('Harap setujui Syarat & Ketentuan terlebih dahulu.');
        return;
    }

    submitting.value = true;
    error.value = null;

    try {
        const payload = {
            items: selectedItems.value.map(i => ({
                ticket_category_id: i.ticket_category_id,
                quantity: i.quantity,
            })),
        };

        const res = await api.createOrder(payload);
        toast.success('Pesanan berhasil dibuat!');
        router.visit(`/payment/${res.data.id}`);
    } catch (err) {
        error.value = err.message || 'Gagal membuat pesanan.';
        toast.error(error.value);
    } finally {
        submitting.value = false;
    }
}

onMounted(() => {
    loadData();
});
</script>

<template>
    <CustomerLayout>
        <Head title="Checkout Pesanan Tiket Konser" />

        <!-- Step Progress Bar -->
        <div class="border-b border-slate-200/80 bg-white/70">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
                <div class="flex items-center justify-center gap-2 text-xs">
                    <span class="text-slate-400">1. Pilih Tiket</span>
                    <span class="text-slate-300">&rsaquo;</span>
                    <span class="font-semibold text-sky-700 bg-sky-50 px-2.5 py-1 rounded-lg border border-sky-200/60">2. Data Pemesan</span>
                    <span class="text-slate-300">&rsaquo;</span>
                    <span class="text-slate-400">3. Pembayaran</span>
                </div>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="max-w-5xl mx-auto px-4 py-16">
            <div class="animate-pulse space-y-6">
                <div class="h-8 bg-slate-200 rounded w-1/3"></div>
                <div class="h-40 bg-slate-100 rounded-2xl"></div>
                <div class="h-40 bg-slate-100 rounded-2xl"></div>
            </div>
        </div>

        <div v-else-if="event" class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <!-- Left: Buyer Info + Payment Method -->
                <div class="lg:col-span-7 space-y-5">
                    <!-- Informasi Pemesan -->
                    <div class="p-5 rounded-2xl border border-slate-200/80 bg-white">
                        <div class="flex items-center gap-2 mb-4">
                            <h2 class="text-sm font-bold text-slate-900">Informasi Pemesan</h2>
                            <span class="text-[10px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-lg">Data Terverifikasi</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Nama Lengkap</label>
                                <div class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200/70 rounded-xl text-xs sm:text-sm font-semibold text-slate-900">
                                    {{ auth.user.value?.name || 'Nama Pengguna' }}
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Alamat Email</label>
                                <div class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200/70 rounded-xl text-xs sm:text-sm text-slate-900 truncate">
                                    {{ auth.user.value?.email || 'email@example.com' }}
                                </div>
                            </div>
                        </div>

                        <p class="mt-3 text-xs text-slate-500 bg-sky-50 border border-sky-100 rounded-xl px-3 py-2.5 flex items-start gap-2">
                            <svg class="w-4 h-4 text-sky-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            E-Ticket dan konfirmasi reservasi akan dikirimkan langsung ke email resmi pemesan.
                        </p>
                    </div>

                    <!-- Pilih Kategori Tiket -->
                    <div class="p-5 rounded-xl border border-slate-200 bg-white">
                        <h3 class="text-sm font-bold text-slate-900 mb-4">Pilih Kategori Tiket</h3>
                        <div class="space-y-3">
                            <div
                                v-for="cat in ticketCategories"
                                :key="cat.id"
                                class="p-4 rounded-xl border flex flex-col sm:flex-row sm:items-center justify-between gap-3 transition"
                                :class="(cat.quota - cat.sold) > 0
                                    ? 'border-slate-200 bg-white hover:border-sky-200'
                                    : 'border-slate-100 bg-slate-50/50 opacity-60'"
                            >
                                <div class="grow">
                                    <div class="flex items-center gap-2 mb-1">
                                        <h4 class="text-sm font-bold text-slate-900">{{ cat.name }}</h4>
                                        <span
                                            v-if="(cat.quota - cat.sold) > 0"
                                            class="text-[10px] font-semibold px-2 py-0.5 rounded border bg-emerald-50 text-emerald-700 border-emerald-200"
                                        >
                                            Tersedia
                                        </span>
                                        <span
                                            v-else
                                            class="text-[10px] font-semibold px-2 py-0.5 rounded border bg-rose-50 text-rose-700 border-rose-200"
                                        >
                                            Habis
                                        </span>
                                    </div>
                                    <p class="text-xl font-extrabold text-slate-900">{{ formatRupiah(cat.price) }}</p>
                                    <p class="text-[11px] text-slate-400">Rp{{ Number(cat.price).toLocaleString('id-ID') }} / tiket</p>
                                </div>

                                <!-- Counter -->
                                <div v-if="(cat.quota - cat.sold) > 0" class="flex items-center gap-2.5 shrink-0">
                                    <button
                                        type="button"
                                        @click="updateQty(cat.id, -1)"
                                        :disabled="!selections[cat.id] || selections[cat.id] <= 0"
                                        class="w-9 h-9 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-100 disabled:opacity-30 disabled:cursor-not-allowed flex items-center justify-center font-bold text-xl transition"
                                        aria-label="Kurangi tiket"
                                    >
                                        <span class="leading-none">-</span>
                                    </button>
                                    <span class="w-8 text-center font-bold text-sm text-slate-900">
                                        {{ selections[cat.id] || 0 }}
                                    </span>
                                    <button
                                        type="button"
                                        @click="updateQty(cat.id, 1)"
                                        :disabled="(selections[cat.id] || 0) >= (cat.quota - cat.sold)"
                                        class="w-9 h-9 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-100 disabled:opacity-30 disabled:cursor-not-allowed flex items-center justify-center font-bold text-xl transition"
                                        aria-label="Tambah tiket"
                                    >
                                        <span class="leading-none">+</span>
                                    </button>
                                </div>
                                <span v-else class="text-xs font-semibold text-rose-500 italic shrink-0">Tiket telah habis</span>
                            </div>
                        </div>
                    </div>

                    <!-- Terms Agreement -->
                    <div class="flex items-start gap-3 p-4 rounded-xl border border-slate-200 bg-white">
                        <input
                            id="agreeTerms"
                            v-model="agreedToTerms"
                            type="checkbox"
                            class="mt-0.5 w-4 h-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500 cursor-pointer"
                        />
                        <label for="agreeTerms" class="text-xs text-slate-600 cursor-pointer">
                            Saya menyetujui <span class="font-semibold text-sky-600 underline cursor-pointer">Syarat & Ketentuan Pembelian Tiket</span>, kebijakan tiket non-refundable, serta protokol keselamatan resmi promotor.
                        </label>
                    </div>
                </div>

                <!-- Right: Order Summary -->
                <div class="lg:col-span-5 lg:sticky lg:top-24">
                    <div class="p-5 rounded-2xl border border-slate-200/80 bg-white/90 backdrop-blur-md shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-bold text-slate-900">Ringkasan Pesanan</h3>
                            <span class="text-[11px] font-semibold text-sky-700 bg-sky-50 px-2 py-0.5 rounded-lg border border-sky-200/60">Tiket Resmi</span>
                        </div>

                        <!-- Event Info -->
                        <div class="flex gap-3 mb-4 pb-4 border-b border-slate-100">
                            <img
                                v-if="event.poster"
                                :src="event.poster"
                                :alt="event.name"
                                class="w-16 h-16 rounded-xl object-cover shrink-0 border border-slate-200"
                                @error="$event.target.style.display='none'"
                            />
                            <div v-else class="w-16 h-16 rounded-xl bg-gradient-to-br from-sky-100 to-sky-200 shrink-0 flex items-center justify-center text-sky-600">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
                            </div>
                            <div class="min-w-0">
                                <h4 class="text-sm font-bold text-slate-900 leading-snug line-clamp-2">{{ event.name }}</h4>
                                <p class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                                    <svg class="w-3 h-3 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    {{ formatDate(event.event_date) }} &bull; {{ event.location }}
                                </p>
                            </div>
                        </div>

                        <!-- Selected Items -->
                        <div v-if="selectedItems.length > 0" class="space-y-2.5 mb-4">
                            <div
                                v-for="item in selectedItems"
                                :key="item.ticket_category_id"
                                class="flex items-center justify-between text-xs"
                            >
                                <span class="text-slate-600">{{ item.quantity }}x {{ item.name }}</span>
                                <span class="font-semibold text-slate-900">{{ formatRupiah(item.subtotal) }}</span>
                            </div>
                        </div>
                        <div v-else class="py-4 text-center text-xs text-slate-400 italic mb-4">
                            Belum ada tiket yang dipilih.
                        </div>

                        <!-- Fee Breakdown -->
                        <div class="space-y-2 text-xs text-slate-500 border-t border-slate-100 pt-3 mb-3">
                            <div class="flex justify-between">
                                <span>Biaya Layanan Platform</span>
                                <span>{{ formatRupiah(serviceFee) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Pajak Hiburan Daerah (10%)</span>
                                <span>{{ formatRupiah(taxAmount) }}</span>
                            </div>
                        </div>

                        <!-- Total -->
                        <div class="flex items-baseline justify-between mb-4 pt-3 border-t border-slate-200">
                            <div>
                                <p class="text-xs font-bold text-slate-700">TOTAL TAGIHAN</p>
                                <p class="text-[10px] text-slate-400">Termasuk pajak & biaya admin</p>
                            </div>
                            <p class="text-2xl font-extrabold text-sky-600">{{ formatRupiah(grandTotal) }}</p>
                        </div>

                        <!-- Error -->
                        <p v-if="error" class="text-xs font-semibold text-rose-600 mb-3 p-2.5 rounded-lg bg-rose-50 border border-rose-200">
                            {{ error }}
                        </p>

                        <!-- Submit CTA -->
                        <button
                            type="button"
                            @click="handleCheckout"
                            :disabled="submitting || totalQuantity === 0 || !agreedToTerms"
                            class="w-full py-3.5 px-4 bg-sky-600 hover:bg-sky-700 disabled:bg-slate-200 disabled:text-slate-400 disabled:cursor-not-allowed text-white font-bold text-sm rounded-xl text-center transition flex items-center justify-center gap-2 shadow-sm"
                        >
                            <svg v-if="submitting" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            <span>{{ submitting ? 'Memproses...' : 'Bayar Sekarang & Konfirmasi' }}</span>
                        </button>

                        <!-- Security info -->
                        <div class="mt-3 flex items-center justify-center gap-4 text-[10px] text-slate-400">
                            <span>256-Bit SSL Enkripsi</span>
                            <span>&bull;</span>
                            <span>Jaminan Tiket 100% Sah</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>
