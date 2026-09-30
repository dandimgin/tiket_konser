<script setup>
import { ref, onMounted, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import Badge from '@/Components/Badge.vue';
import { api } from '@/services/api';
import { formatRupiah, formatDate } from '@/stores/auth';
import { useToast } from '@/composables/useToast';
import QRCode from 'qrcode';

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
const copiedCode = ref(false);
const qrisQrUrl = ref('');

const paymentMethods = [
    { id: 'Transfer Bank BCA', name: 'BCA Transfer / Virtual Account', account: '1234-5678-9012', desc: 'a/n PT Tiketin Indonesia' },
    { id: 'Transfer Bank Mandiri', name: 'Bank Mandiri', account: '9876-5432-1098', desc: 'a/n PT Tiketin Indonesia' },
    { id: 'Transfer Bank BNI', name: 'Bank BNI', account: '5555-4444-3333', desc: 'a/n PT Tiketin Indonesia' },
    { id: 'QRIS', name: 'QRIS (Gopay / OVO / Dana / LinkAja)', account: 'NMID: ID1020304050', desc: 'Scan melalui aplikasi e-wallet / mobile banking' },
];

const selectedMethod = ref(paymentMethods[0].id);
const proofString = ref('');

async function generateQrisQr() {
    if (!order.value) return;
    try {
        const payload = `00020101021226580016ID.TIKETIN.WWW0118936009990001020304050215${order.value.order_code}520458125303360540${order.value.total_amount}5802ID5914TIKETIN-OFFIC6007JAKARTA6304`;
        qrisQrUrl.value = await QRCode.toDataURL(payload, {
            width: 280,
            margin: 1,
            color: { dark: '#0f172a', light: '#ffffff' },
            errorCorrectionLevel: 'H',
        });
    } catch (e) {
        console.error('QRIS error:', e);
    }
}

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

        if (payment.value?.payment_method) {
            selectedMethod.value = payment.value.payment_method;
        }
        if (payment.value?.proof) {
            proofString.value = payment.value.proof;
        }

        await generateQrisQr();
    } catch (err) {
        error.value = err.message || 'Gagal memuat rincian pembayaran.';
    } finally {
        loading.value = false;
    }
}

async function handleSubmitProof() {
    if (!selectedMethod.value) {
        toast?.error?.('Pilih metode pembayaran terlebih dahulu.');
        return;
    }
    if (!proofString.value.trim()) {
        toast?.error?.('Ketikkan bukti transfer atau nomor referensi pembayaran.');
        return;
    }

    submitting.value = true;
    error.value = null;

    try {
        const res = await api.submitPaymentProof(props.orderId, {
            payment_method: selectedMethod.value,
            proof: proofString.value.trim(),
        });
        payment.value = res.data;
        toast?.success?.('Bukti pembayaran berhasil dikirim!');
    } catch (err) {
        error.value = err.message || 'Gagal mengirim bukti pembayaran.';
        toast?.error?.(error.value);
    } finally {
        submitting.value = false;
    }
}

function copyAccount(acc) {
    if (typeof navigator !== 'undefined' && navigator.clipboard) {
        navigator.clipboard.writeText(acc);
        toast?.success?.('Nomor rekening disalin!');
    }
}

function copyOrderCode(code) {
    if (typeof navigator !== 'undefined' && navigator.clipboard) {
        navigator.clipboard.writeText(code);
        copiedCode.value = true;
        toast?.success?.('Kode pesanan disalin!');
        setTimeout(() => {
            copiedCode.value = false;
        }, 2000);
    }
}

function downloadQrisImage() {
    if (!qrisQrUrl.value) return;
    const a = document.createElement('a');
    a.href = qrisQrUrl.value;
    a.download = `QRIS-Tiketin-${order.value?.order_code}.png`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    toast?.success?.('Kode QRIS berhasil diunduh.');
}

watch(selectedMethod, (val) => {
    if (val === 'QRIS' && !qrisQrUrl.value) {
        generateQrisQr();
    }
});

onMounted(() => {
    fetchOrderAndPayment();
});
</script>

<template>
    <CustomerLayout>
        <Head title="Pembayaran Tiket Konser - Tiketin" />

        <!-- Breadcrumbs Navigation -->
        <div class="border-b border-slate-200/80 bg-white">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
                <nav class="flex items-center gap-2 text-xs font-medium text-slate-400">
                    <Link href="/" class="hover:text-slate-900 transition">Beranda</Link>
                    <span>/</span>
                    <Link href="/orders" class="hover:text-slate-900 transition">Pesanan Saya</Link>
                    <span>/</span>
                    <span class="text-sky-600 font-semibold">Pembayaran</span>
                </nav>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="animate-pulse space-y-6">
                <div class="h-8 bg-slate-200 rounded-xl w-1/3"></div>
                <div class="h-48 bg-slate-100 rounded-2xl"></div>
                <div class="h-64 bg-slate-100 rounded-2xl"></div>
            </div>
        </div>

        <!-- Error State -->
        <div v-else-if="error && !order" class="max-w-md mx-auto my-16 p-8 bg-rose-50 border border-rose-200 rounded-2xl text-center shadow-sm">
            <div class="w-12 h-12 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h2 class="text-base font-bold text-rose-900 mb-1">Pesanan Tidak Ditemukan</h2>
            <p class="text-xs text-rose-600 mb-5">{{ error }}</p>
            <Link
                href="/orders"
                class="inline-block px-5 py-2.5 bg-sky-600 text-white rounded-xl text-xs font-bold hover:bg-sky-700 transition shadow-sm"
            >
                Kembali ke Pesanan Saya
            </Link>
        </div>

        <!-- Main Content -->
        <div v-else-if="order" class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10">
            <!-- Order Header Banner -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8 pb-6 border-b border-slate-200">
                <div>
                    <div class="flex items-center gap-2 text-xs text-slate-400 font-medium mb-1.5">
                        <svg class="w-4 h-4 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        Konfirmasi Pembayaran Pesanan &bull; Tiketin
                    </div>
                    <div class="flex items-center gap-2.5">
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight font-mono">
                            {{ order.order_code }}
                        </h1>
                        <button
                            type="button"
                            @click="copyOrderCode(order.order_code)"
                            title="Salin kode transaksi"
                            class="text-slate-400 hover:text-sky-600 transition p-1"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </button>
                    </div>
                    <p class="text-xs text-slate-400 mt-1">Dipesan pada {{ formatDate(order.ordered_at || order.created_at, true) }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <Badge :status="order.status" size="md" />
                    <Badge v-if="payment" :status="payment.status" size="md" />
                </div>
            </div>

            <!-- Verified Alert -->
            <div
                v-if="payment && payment.status === 'verified'"
                class="p-6 mb-8 rounded-2xl bg-emerald-50 border border-emerald-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm"
            >
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm sm:text-base font-bold text-emerald-950">Pembayaran Terverifikasi Resmi</h2>
                        <p class="text-xs text-emerald-800 mt-0.5">E-Tiket resmi konser Anda telah diterbitkan dan siap digunakan di pintu masuk venue.</p>
                    </div>
                </div>
                <Link
                    :href="`/tickets?order_id=${orderId}`"
                    class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shrink-0 text-center shadow-sm flex items-center justify-center gap-1.5"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                    </svg>
                    Buka E-Tiket Saya
                </Link>
            </div>

            <!-- Rejected Alert -->
            <div
                v-else-if="payment && payment.status === 'rejected'"
                class="p-5 mb-8 rounded-2xl bg-rose-50 border border-rose-200 flex items-start gap-3.5 shadow-sm"
            >
                <div class="w-10 h-10 rounded-xl bg-rose-600 text-white flex items-center justify-center shrink-0 mt-0.5 shadow-xs">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-rose-950">Bukti Pembayaran Belum Terverifikasi / Ditolak</h2>
                    <p class="text-xs text-rose-800 mt-0.5">
                        Mohon cek kembali nomor transaksi bank atau mutasi rekening Anda, lalu kirimkan ulang bukti yang valid di formulir bawah ini.
                    </p>
                </div>
            </div>

            <!-- Two Columns: Payment Action (Left) + Order Summary (Right) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                <!-- Left: Payment Method & Proof -->
                <div class="lg:col-span-7 space-y-6">
                    <!-- Methods Card -->
                    <div class="p-6 rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex items-center gap-2 mb-4">
                            <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                            <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                                Pilihan Rekening Pembayaran Resmi
                            </h2>
                        </div>
                        <div class="space-y-3">
                            <div
                                v-for="method in paymentMethods"
                                :key="method.id"
                                @click="selectedMethod = method.id"
                                class="p-4 rounded-xl border cursor-pointer transition"
                                :class="selectedMethod === method.id
                                    ? 'border-sky-500 bg-sky-50/50 ring-2 ring-sky-100 shadow-xs'
                                    : 'border-slate-200 hover:bg-slate-50'"
                            >
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-bold text-slate-900">{{ method.name }}</span>
                                    <button
                                        v-if="method.id !== 'QRIS'"
                                        type="button"
                                        @click.stop="copyAccount(method.account)"
                                        class="text-[11px] font-semibold text-sky-700 hover:text-sky-800 border border-sky-200 px-2.5 py-0.5 rounded-lg bg-white hover:bg-sky-50 transition shadow-2xs flex items-center gap-1"
                                    >
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        Salin
                                    </button>
                                    <span v-else class="text-[10px] font-bold text-sky-700 bg-sky-100 px-2 py-0.5 rounded">
                                        Instan QR
                                    </span>
                                </div>
                                <span class="font-mono text-sm font-bold text-slate-900 block my-0.5">{{ method.account }}</span>
                                <span class="text-[11px] text-slate-500 block">{{ method.desc }}</span>
                            </div>
                        </div>

                        <!-- DYNAMIC REAL QRIS QR CODE DISPLAY -->
                        <div v-if="selectedMethod === 'QRIS'" class="mt-5 p-5 bg-slate-50 border border-slate-200 rounded-2xl text-center space-y-3 animate-fade-in">
                            <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                                <span class="text-xs font-bold text-slate-800">QRIS Pembayaran Resmi</span>
                                <span class="text-[10px] text-slate-400 font-mono">NMID: ID1020304050</span>
                            </div>
                            <div class="inline-block p-3 bg-white rounded-2xl border-2 border-slate-300 shadow-sm">
                                <img
                                    v-if="qrisQrUrl"
                                    :src="qrisQrUrl"
                                    alt="QRIS Tiketin"
                                    class="w-48 h-48 mx-auto object-contain"
                                />
                                <div v-else class="w-48 h-48 flex items-center justify-center text-xs text-slate-400">
                                    Membuat QRIS...
                                </div>
                                <p class="text-[10px] font-bold text-slate-700 mt-1 uppercase tracking-wider">Tiketin Official Merchant</p>
                            </div>
                            <p class="text-xs text-slate-500">
                                Scan QRIS menggunakan GoPay, OVO, Dana, LinkAja, BCA Mobile, atau aplikasi bank apa pun.
                            </p>
                            <div class="flex items-center justify-center gap-2 pt-1">
                                <button
                                    type="button"
                                    @click="downloadQrisImage"
                                    class="px-3.5 py-1.5 text-xs font-semibold text-sky-700 bg-white border border-sky-200 rounded-lg hover:bg-sky-50 transition shadow-2xs flex items-center gap-1.5"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    Unduh Gambar QRIS
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Proof Submission -->
                    <div class="p-6 rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                            <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                                Konfirmasi & Kirim Bukti Transfer
                            </h2>
                        </div>
                        <p class="text-xs text-slate-500 mb-5">
                            Ketikkan nomor referensi transaksi bank Anda atau nama file bukti transfer.
                        </p>

                        <div class="space-y-4">
                            <div>
                                <label for="proofInput" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Bukti Pembayaran / Nomor Referensi Transaksi
                                </label>
                                <input
                                    id="proofInput"
                                    v-model="proofString"
                                    type="text"
                                    placeholder="Contoh: TRF-BCA-987654 atau bukti_transfer_bca.jpg"
                                    class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-900 focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100 transition shadow-xs"
                                    :disabled="payment && payment.status === 'verified'"
                                />
                            </div>

                            <button
                                type="button"
                                @click="handleSubmitProof"
                                :disabled="submitting || (payment && payment.status === 'verified')"
                                class="w-full py-3 px-4 bg-sky-600 hover:bg-sky-700 disabled:bg-slate-200 disabled:text-slate-400 disabled:cursor-not-allowed text-white font-bold text-sm rounded-xl transition flex items-center justify-center gap-2 shadow-sm"
                            >
                                <svg v-if="submitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>{{ submitting ? 'Mengirim...' : (payment?.proof ? 'Perbarui Bukti Pembayaran' : 'Kirim Bukti Pembayaran') }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right: Summary -->
                <div class="lg:col-span-5 space-y-6">
                    <div class="p-6 rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-4 pb-3 border-b border-slate-100 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                            Rincian Tagihan Pesanan
                        </h2>

                        <!-- Items List -->
                        <div class="space-y-3 mb-5">
                            <div
                                v-for="item in order.order_items"
                                :key="item.id"
                                class="flex items-center justify-between text-xs"
                            >
                                <div>
                                    <p class="font-bold text-slate-900">{{ item.ticket_category?.name || 'Tiket Masuk' }} &times; {{ item.quantity }}</p>
                                    <p class="text-slate-400">@ {{ formatRupiah(item.price) }}</p>
                                </div>
                                <span class="font-bold text-slate-900 text-sm">
                                    {{ formatRupiah(item.subtotal) }}
                                </span>
                            </div>
                        </div>

                        <!-- Total Tagihan Banner -->
                        <div class="pt-4 border-t border-slate-100 flex items-baseline justify-between mb-4">
                            <div>
                                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Total Tagihan</span>
                                <span class="text-[11px] text-slate-400">Termasuk pajak & biaya admin</span>
                            </div>
                            <span class="text-2xl font-extrabold text-slate-900">
                                {{ formatRupiah(order.total_amount) }}
                            </span>
                        </div>

                        <!-- Status Details Card -->
                        <div v-if="payment" class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500">Status Pembayaran:</span>
                                <span class="font-bold capitalize text-slate-800">{{ payment.status }}</span>
                            </div>
                            <div v-if="payment.payment_method" class="flex justify-between items-center">
                                <span class="text-slate-500">Metode:</span>
                                <span class="font-medium text-slate-800">{{ payment.payment_method }}</span>
                            </div>
                            <div v-if="payment.proof" class="flex justify-between items-center">
                                <span class="text-slate-500">Bukti:</span>
                                <span class="font-mono font-medium text-slate-800 truncate max-w-[170px]">{{ payment.proof }}</span>
                            </div>
                            <div v-if="payment.paid_at" class="flex justify-between items-center">
                                <span class="text-slate-500">Waktu Verifikasi:</span>
                                <span class="font-medium text-slate-800">{{ formatDate(payment.paid_at, true) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Helpful Security Badge -->
                    <div class="p-4 bg-sky-50/70 border border-sky-100 rounded-2xl text-xs text-sky-800 flex items-start gap-3 shadow-xs">
                        <svg class="w-5 h-5 text-sky-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <div>
                            <p class="font-bold text-sky-900 mb-0.5">Transaksi Aman & Terenkripsi</p>
                            <p class="text-sky-700 text-[11px] leading-relaxed">
                                Bukti pembayaran Anda diverifikasi langsung oleh sistem resmi Tiketin. Hindari mentransfer ke rekening selain yang tertera di atas.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>
