<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import { api } from '@/services/api';
import { formatRupiah, formatDate } from '@/stores/auth';
import { useToast } from '@/composables/useToast';

const props = defineProps({
    orderId: {
        type: Number,
        required: true,
    },
});

const toast = useToast();
const order = ref(null);
const payment = ref(null);
const loading = ref(true);
const submitting = ref(false);
const error = ref(null);
const activeTab = ref('qris'); 

const paymentMethods = [
    { id: 'qris', label: 'QRIS / E-Wallet', icon: 'M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z' },
    { id: 'va', label: 'Virtual Account', icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z' },
    { id: 'cc', label: 'Kartu Kredit', icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z' }
];

async function fetchOrderAndPayment() {
    loading.value = true;
    error.value = null;
    try {
        const [orderRes, paymentRes] = await Promise.all([
            api.getOrder(props.orderId),
            api.getOrderPayment(props.orderId),
        ]);
        order.value = orderRes.data;
        payment.value = paymentRes.data;
    } catch (err) {
        error.value = err.message || 'Gagal memuat rincian pembayaran.';
    } finally {
        loading.value = false;
    }
}

async function simulatePayment() {
    submitting.value = true;
    error.value = null;

    let methodString = 'QRIS';
    if (activeTab.value === 'va') methodString = 'Virtual Account Bank';
    if (activeTab.value === 'cc') methodString = 'Credit Card';

    try {
        // Simulate a tiny delay for "processing" UX
        await new Promise(resolve => setTimeout(resolve, 1500));
        
        const res = await api.submitPaymentProof(props.orderId, {
            payment_method: methodString,
            proof: 'MOCK_GATEWAY_SUCCESS', // Our backend auto-verifies this
        });
        
        payment.value = res.data;
        // Re-fetch order to get updated status and tickets
        const orderRes = await api.getOrder(props.orderId);
        order.value = orderRes.data;
        
        toast.success('Pembayaran berhasil dikonfirmasi secara instan!');
    } catch (err) {
        error.value = err.message || 'Gagal memproses pembayaran.';
        toast.error(error.value);
    } finally {
        submitting.value = false;
    }
}

onMounted(() => {
    fetchOrderAndPayment();
});
</script>

<template>
    <CustomerLayout>
        <Head title="Secure Payment Gateway - Tiketin" />

        <div class="min-h-[80vh] bg-slate-50 py-10">
            <!-- Loading -->
            <div v-if="loading" class="max-w-3xl mx-auto px-4">
                <div class="animate-pulse space-y-6">
                    <div class="h-64 bg-slate-200 rounded-3xl"></div>
                </div>
            </div>

            <!-- Error (Order Not Found) -->
            <div v-else-if="error && !order" class="max-w-md mx-auto p-8 bg-white border border-slate-200 rounded-3xl text-center shadow-xs">
                <p class="text-rose-600 mb-5 font-medium">{{ error }}</p>
                <Link href="/orders" class="px-5 py-2.5 bg-slate-900 text-white rounded-xl text-sm font-bold">Kembali ke Pesanan</Link>
            </div>

            <!-- Main Gateway UI -->
            <div v-else-if="order" class="max-w-2xl mx-auto px-4">
                
                <!-- Success State -->
                <div v-if="payment && payment.status === 'verified'" class="bg-white rounded-3xl shadow-sm border border-emerald-100 overflow-hidden text-center p-10 animate-fade-in">
                    <div class="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-6 text-emerald-600">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <h2 class="text-2xl font-extrabold text-slate-900 mb-2">Pembayaran Berhasil!</h2>
                    <p class="text-slate-500 mb-8">Terima kasih, pembayaran sebesar <strong>{{ formatRupiah(order.total_amount) }}</strong> telah diterima. E-Tiket Anda sudah diterbitkan.</p>
                    
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                        <Link :href="`/tickets?order_id=${orderId}`" class="w-full sm:w-auto px-8 py-3.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl transition shadow-sm">
                            Lihat E-Tiket Saya
                        </Link>
                        <Link href="/orders" class="w-full sm:w-auto px-8 py-3.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition">
                            Riwayat Pesanan
                        </Link>
                    </div>
                </div>

                <!-- Gateway Checkout State -->
                <div v-else class="bg-white rounded-3xl shadow-sm border border-slate-200/80 overflow-hidden">
                    <!-- Gateway Header -->
                    <div class="bg-slate-900 text-white p-6 sm:p-8 relative overflow-hidden">
                        <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
                        <div class="relative z-10 flex items-center justify-between">
                            <div>
                                <p class="text-sky-300 text-xs font-bold uppercase tracking-widest mb-1">Tiketin Pay</p>
                                <h1 class="text-xl sm:text-2xl font-extrabold">Secure Checkout</h1>
                            </div>
                            <div class="text-right">
                                <p class="text-slate-400 text-xs mb-1">Total Tagihan</p>
                                <p class="text-2xl sm:text-3xl font-extrabold text-white">{{ formatRupiah(order.total_amount) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Order Summary Mini -->
                    <div class="px-6 sm:px-8 py-5 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center text-sm">
                        <span class="text-slate-500">Order ID: <strong class="text-slate-900">{{ order.order_code }}</strong></span>
                        <Link href="/orders" class="text-rose-600 font-semibold hover:underline text-xs">Batalkan</Link>
                    </div>

                    <div class="p-6 sm:p-8">
                        <h3 class="text-sm font-bold text-slate-900 mb-4">Pilih Metode Pembayaran</h3>
                        
                        <!-- Tabs -->
                        <div class="grid grid-cols-3 gap-3 mb-6">
                            <button 
                                v-for="method in paymentMethods" 
                                :key="method.id"
                                @click="activeTab = method.id"
                                class="flex flex-col items-center justify-center gap-2 p-4 rounded-2xl border transition text-center"
                                :class="activeTab === method.id ? 'border-sky-500 bg-sky-50/30 text-sky-700 ring-1 ring-sky-500' : 'border-slate-200 bg-white text-slate-500 hover:bg-slate-50'"
                            >
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="method.icon" />
                                </svg>
                                <span class="text-[11px] font-bold leading-tight">{{ method.label }}</span>
                            </button>
                        </div>

                        <!-- Mock Instruction Area -->
                        <div class="bg-slate-50 border border-slate-100 p-5 rounded-2xl mb-8">
                            <div v-if="activeTab === 'qris'" class="text-center">
                                <div class="w-32 h-32 bg-white border-2 border-slate-200 rounded-xl mx-auto flex items-center justify-center mb-3">
                                    <svg class="w-16 h-16 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                                </div>
                                <p class="text-xs text-slate-500">Scan QRIS menggunakan GoPay, OVO, Dana, atau Mobile Banking.</p>
                            </div>
                            <div v-if="activeTab === 'va'" class="space-y-4">
                                <div>
                                    <p class="text-[11px] text-slate-500 font-bold uppercase tracking-wider mb-1">Nomor Virtual Account</p>
                                    <div class="flex items-center justify-between bg-white px-4 py-3 rounded-xl border border-slate-200">
                                        <span class="font-mono font-extrabold text-slate-900 tracking-wide text-lg">8899 0011 2233 4455</span>
                                        <button class="text-sky-600 text-xs font-bold hover:underline">Salin</button>
                                    </div>
                                </div>
                                <p class="text-xs text-slate-500">Transfer tepat sesuai nominal tagihan sebelum batas waktu habis.</p>
                            </div>
                            <div v-if="activeTab === 'cc'" class="space-y-3">
                                <input type="text" placeholder="Nomor Kartu" class="w-full bg-white px-4 py-3 border border-slate-200 rounded-xl text-sm" />
                                <div class="grid grid-cols-2 gap-3">
                                    <input type="text" placeholder="MM/YY" class="w-full bg-white px-4 py-3 border border-slate-200 rounded-xl text-sm" />
                                    <input type="text" placeholder="CVC" class="w-full bg-white px-4 py-3 border border-slate-200 rounded-xl text-sm" />
                                </div>
                            </div>
                        </div>

                        <!-- Error Message -->
                        <p v-if="error" class="text-xs font-semibold text-rose-600 mb-4 p-3 rounded-xl bg-rose-50 border border-rose-200">
                            {{ error }}
                        </p>

                        <!-- Pay Button -->
                        <button 
                            @click="simulatePayment"
                            :disabled="submitting"
                            class="w-full py-4 px-4 bg-sky-600 hover:bg-sky-700 text-white font-bold rounded-2xl transition flex items-center justify-center gap-2 shadow-sm disabled:opacity-70 disabled:cursor-not-allowed"
                        >
                            <svg v-if="submitting" class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-lg">Bayar {{ formatRupiah(order.total_amount) }}</span>
                        </button>
                        
                        <div class="mt-4 flex items-center justify-center gap-1.5 text-[10px] font-medium text-slate-400">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            Pembayaran dijamin aman dan terenkripsi.
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </CustomerLayout>
</template>
