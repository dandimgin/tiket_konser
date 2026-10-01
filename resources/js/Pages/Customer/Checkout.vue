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
const buyerForm = reactive({
    name: auth.user.value?.name || '',
    email: auth.user.value?.email || '',
    phone: '',
    referral: '',
    sendToEmail: false
});

const countdown = ref('14 menit : 54 detik');

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

        const params = new URLSearchParams(window.location.search);
        let hasSelection = false;
        
        ticketCategories.value.forEach(cat => {
            const q = params.get(`qty[${cat.id}]`);
            if (q) {
                selections[cat.id] = parseInt(q, 10);
                hasSelection = true;
            } else {
                selections[cat.id] = 0;
            }
        });

        if (!hasSelection) {
            toast.error('Silakan pilih tiket terlebih dahulu');
            router.visit(`/events/${props.eventId}`);
        }
    } catch (err) {
        error.value = err.message || 'Gagal memuat data checkout.';
    } finally {
        loading.value = false;
    }
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

        <!-- Timer Banner -->
        <div class="bg-indigo-600 text-white text-[13px] font-bold py-2.5 px-4 text-center flex items-center justify-center gap-2">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
            <span>Tiket sudah disimpan, selesaikan pesanan dalam</span>
            <span class="tracking-wide">{{ countdown }}</span>
        </div>

        <div class="bg-white border-b border-slate-200/80 mb-6">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-5">
                <Link :href="`/events/${eventId}`" class="inline-flex items-center gap-1.5 text-xs font-bold text-rose-600 hover:text-rose-700 transition mb-3">
                    <span class="text-lg leading-none">&lsaquo;</span> Kembali
                </Link>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">{{ event?.name || 'Checkout Tiket' }}</h1>
                <p class="text-sm text-slate-500 mt-1">Untuk melakukan pemesanan, silakan lengkapi formulir berikut:</p>
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
                    <!-- Detail Peserta Form -->
                    <div class="p-6 rounded-2xl bg-white shadow-sm border border-slate-200/80">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 rounded-xl bg-sky-50 flex items-center justify-center text-sky-600 font-bold text-xl shadow-sm border border-sky-100">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                            </div>
                            <h2 class="text-lg font-bold text-slate-900">Detail Peserta</h2>
                        </div>

                        <div class="space-y-5">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Nama *</label>
                                <p class="text-[11px] text-slate-500 mb-2">Gunakan nama lengkap yang tertera di KTP/Paspor.</p>
                                <input v-model="buyerForm.name" type="text" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 rounded-xl text-sm transition text-slate-900" placeholder="Masukkan nama" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Email *</label>
                                <p class="text-[11px] text-slate-500 mb-2">Masukkan email yang masih aktif.</p>
                                <input v-model="buyerForm.email" type="email" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 rounded-xl text-sm transition text-slate-900" placeholder="Masukkan email" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Nomor HP *</label>
                                <p class="text-[11px] text-slate-500 mb-2">Pastikan nomor HP yang kamu masukkan masih aktif.</p>
                                <input v-model="buyerForm.phone" type="tel" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 rounded-xl text-sm transition text-slate-900" placeholder="Masukkan nomor HP" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Kode Referral</label>
                                <p class="text-[11px] text-slate-500 mb-2">*Jika ada</p>
                                <input v-model="buyerForm.referral" type="text" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 rounded-xl text-sm transition text-slate-900" placeholder="Masukkan Kode Referral" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Order Summary -->
                <div class="lg:col-span-5 lg:sticky lg:top-24">
                    <!-- Detail Pemesanan Card -->
                    <div class="p-6 rounded-2xl bg-white shadow-sm border border-slate-200/80 mb-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-bold text-slate-900">Ringkasan Pesanan</h3>
                            <span class="text-[11px] font-semibold text-sky-700 bg-sky-50 px-2 py-0.5 rounded-lg border border-sky-200/60">Tiket Resmi</span>
                        </div>

                        <!-- Selected Items -->
                        <div v-if="selectedItems.length > 0" class="space-y-3 mb-4 pb-4 border-b border-slate-100">
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
                        <div class="space-y-2 text-xs text-slate-500 mb-4">
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
                        <div class="flex items-baseline justify-between mb-5 pt-4 border-t border-slate-200">
                            <div>
                                <p class="text-xs font-bold text-slate-700">TOTAL TAGIHAN</p>
                                <p class="text-[10px] text-slate-400">Termasuk pajak & biaya admin</p>
                            </div>
                            <p class="text-xl font-extrabold text-sky-600">{{ formatRupiah(grandTotal) }}</p>
                        </div>

                        <!-- Error Message -->
                        <p v-if="error" class="text-xs font-semibold text-rose-600 mb-4 p-3 rounded-xl bg-rose-50 border border-rose-200">
                            {{ error }}
                        </p>

                        <!-- Submit CTA -->
                        <button
                            type="button"
                            @click="handleCheckout"
                            :disabled="submitting || totalQuantity === 0"
                            class="w-full py-3.5 px-4 bg-sky-600 hover:bg-sky-700 disabled:bg-slate-200 disabled:text-slate-400 disabled:cursor-not-allowed text-white font-bold text-sm rounded-xl text-center transition flex items-center justify-center gap-2 shadow-sm"
                        >
                            <svg v-if="submitting" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ submitting ? 'Memproses...' : 'Lanjutkan ke Pembayaran' }}</span>
                        </button>
                    </div>

                    <!-- Email Toggle Option -->
                    <div class="px-6 py-4 rounded-xl bg-white shadow-sm border border-slate-100 flex items-center justify-between">
                        <span class="text-xs text-slate-600">Kirimkan tiket ke email peserta.</span>
                        <button 
                            type="button" 
                            @click="buyerForm.sendToEmail = !buyerForm.sendToEmail"
                            class="w-9 h-5 rounded-full relative transition-colors focus:outline-none"
                            :class="buyerForm.sendToEmail ? 'bg-indigo-500' : 'bg-slate-200'"
                        >
                            <span 
                                class="absolute top-0.5 left-0.5 bg-white w-4 h-4 rounded-full transition-transform shadow-sm"
                                :class="buyerForm.sendToEmail ? 'transform translate-x-4' : ''"
                            ></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>
