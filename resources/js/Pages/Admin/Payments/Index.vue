<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Badge from '@/Components/Badge.vue';
import Modal from '@/Components/Modal.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { api } from '@/services/api';
import { formatRupiah, formatDate } from '@/stores/auth';
import { useToast } from '@/composables/useToast';

const toast = useToast();

const payments = ref([]);
const loading = ref(true);
const statusFilter = ref('all');
const search = ref('');

const showProofModal = ref(false);
const activeProof = ref('');
const activeOrderCode = ref('');
const activeItem = ref(null);

const processingId = ref(null);

async function fetchPayments() {
    loading.value = true;
    try {
        const res = await api.getAdminPayments();
        payments.value = res.data || [];
    } catch (err) {
        toast.error(err.message || 'Gagal memuat daftar pembayaran');
    } finally {
        loading.value = false;
    }
}

const filteredPayments = computed(() => {
    return payments.value.filter(p => {
        const status = p.payment?.status || 'pending';
        const matchStatus = statusFilter.value === 'all' || status === statusFilter.value;
        const q = search.value.toLowerCase();
        const matchSearch = !search.value ||
            p.order_code.toLowerCase().includes(q) ||
            (p.user && p.user.toLowerCase().includes(q)) ||
            (p.payment?.payment_method && p.payment.payment_method.toLowerCase().includes(q)) ||
            (p.payment?.proof && p.payment.proof.toLowerCase().includes(q));
        return matchStatus && matchSearch;
    });
});

const pendingCount = computed(() => payments.value.filter(p => (p.payment?.status || 'pending') === 'pending').length);
const verifiedCount = computed(() => payments.value.filter(p => p.payment?.status === 'verified').length);
const rejectedCount = computed(() => payments.value.filter(p => p.payment?.status === 'rejected').length);

function openProofModal(item) {
    activeProof.value = item.payment?.proof || '';
    activeOrderCode.value = item.order_code;
    activeItem.value = item;
    showProofModal.value = true;
}

async function handleVerify(orderId) {
    processingId.value = orderId;
    try {
        await api.verifyPayment(orderId);
        toast.success('Pembayaran berhasil diverifikasi & tiket diterbitkan!');
        showProofModal.value = false;
        await fetchPayments();
    } catch (err) {
        toast.error(err.message || 'Gagal memverifikasi pembayaran');
    } finally {
        processingId.value = null;
    }
}

async function handleReject(orderId) {
    processingId.value = orderId;
    try {
        await api.rejectPayment(orderId);
        toast.success('Pembayaran telah ditolak.');
        showProofModal.value = false;
        await fetchPayments();
    } catch (err) {
        toast.error(err.message || 'Gagal menolak pembayaran');
    } finally {
        processingId.value = null;
    }
}

onMounted(() => {
    fetchPayments();
});
</script>

<template>
    <AdminLayout>
        <Head title="Verifikasi Pembayaran - Admin Tiketin" />

        <div class="space-y-5">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-xs text-slate-400 mb-1">
                        <svg class="w-4 h-4 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        Manajemen Keuangan
                    </div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Verifikasi Pembayaran Tiket</h1>
                    <p class="text-sm text-slate-500 mt-0.5">Periksa bukti transfer dan verifikasi transaksi untuk menerbitkan e-tiket konser</p>
                </div>
                <button
                    @click="fetchPayments"
                    class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition shadow-sm self-start sm:self-auto"
                >
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Segarkan
                </button>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div
                    class="p-4 rounded-xl border cursor-pointer transition"
                    :class="statusFilter === 'pending' ? 'bg-amber-50 border-amber-300' : 'bg-white border-slate-200 hover:border-amber-200'"
                    @click="statusFilter = statusFilter === 'pending' ? 'all' : 'pending'"
                >
                    <p class="text-[11px] font-bold text-amber-600 uppercase tracking-wider mb-1">Menunggu Verifikasi</p>
                    <p class="text-3xl font-extrabold text-amber-900">{{ pendingCount }}</p>
                    <p class="text-xs text-amber-700 mt-1">Pembayaran perlu diperiksa</p>
                </div>
                <div
                    class="p-4 rounded-xl border cursor-pointer transition"
                    :class="statusFilter === 'verified' ? 'bg-emerald-50 border-emerald-300' : 'bg-white border-slate-200 hover:border-emerald-200'"
                    @click="statusFilter = statusFilter === 'verified' ? 'all' : 'verified'"
                >
                    <p class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider mb-1">Terverifikasi</p>
                    <p class="text-3xl font-extrabold text-emerald-900">{{ verifiedCount }}</p>
                    <p class="text-xs text-emerald-700 mt-1">Tiket sudah diterbitkan</p>
                </div>
                <div
                    class="p-4 rounded-xl border cursor-pointer transition"
                    :class="statusFilter === 'rejected' ? 'bg-rose-50 border-rose-300' : 'bg-white border-slate-200 hover:border-rose-200'"
                    @click="statusFilter = statusFilter === 'rejected' ? 'all' : 'rejected'"
                >
                    <p class="text-[11px] font-bold text-rose-600 uppercase tracking-wider mb-1">Ditolak</p>
                    <p class="text-3xl font-extrabold text-rose-900">{{ rejectedCount }}</p>
                    <p class="text-xs text-rose-700 mt-1">Bukti tidak valid</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-xl border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="relative max-w-sm w-full">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Cari order, nama customer, metode, bukti..."
                        class="w-full pl-9 pr-4 py-2.5 bg-white border border-slate-200 rounded-lg text-sm text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 transition"
                    />
                </div>

                <div class="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0 flex-shrink-0">
                    <button
                        v-for="st in [
                            { id: 'all', label: 'Semua' },
                            { id: 'pending', label: 'Menunggu' },
                            { id: 'verified', label: 'Terverifikasi' },
                            { id: 'rejected', label: 'Ditolak' }
                        ]"
                        :key="st.id"
                        @click="statusFilter = st.id"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition"
                        :class="statusFilter === st.id
                            ? 'bg-sky-600 text-white shadow-sm'
                            : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'"
                    >
                        {{ st.label }}
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                <div v-if="loading" class="p-10 text-center">
                    <div class="animate-spin w-6 h-6 border-2 border-sky-500 border-t-transparent rounded-full mx-auto mb-3"></div>
                    <p class="text-sm text-slate-400">Memuat data pembayaran...</p>
                </div>

                <div v-else-if="filteredPayments.length === 0" class="p-8 text-center">
                    <EmptyState
                        title="Tidak Ada Pembayaran"
                        description="Belum ada transaksi pembayaran yang cocok dengan filter ini."
                    />
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-xs">
                                <th class="py-3 px-4">Order & Customer</th>
                                <th class="py-3 px-4">Event</th>
                                <th class="py-3 px-4">Total Tagihan</th>
                                <th class="py-3 px-4">Metode Bayar</th>
                                <th class="py-3 px-4">Bukti Transfer</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">Waktu</th>
                                <th class="py-3 px-4 text-right">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr
                                v-for="item in filteredPayments"
                                :key="item.order_id"
                                class="hover:bg-slate-50/60 transition"
                            >
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="font-mono font-bold text-slate-900 text-xs block">{{ item.order_code }}</span>
                                    <span class="text-slate-400 text-xs">{{ item.user }}</span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="text-xs text-slate-600 block max-w-[120px] truncate">{{ item.event_name || '-' }}</span>
                                </td>
                                <td class="py-3.5 px-4 font-bold text-slate-900 whitespace-nowrap text-sm">
                                    {{ formatRupiah(item.total_amount) }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-600 whitespace-nowrap text-xs">
                                    {{ item.payment?.payment_method || '-' }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <button
                                        v-if="item.payment?.proof"
                                        @click="openProofModal(item)"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-100 hover:bg-sky-50 hover:text-sky-700 text-slate-700 rounded-lg font-medium text-xs transition border border-slate-200 hover:border-sky-200"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <span class="truncate max-w-[100px] text-xs">{{ item.payment.proof }}</span>
                                    </button>
                                    <span v-else class="text-slate-400 italic text-xs">Belum dikirim</span>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <Badge :status="item.payment?.status || 'pending'" size="sm" />
                                </td>
                                <td class="py-3.5 px-4 text-slate-400 whitespace-nowrap text-xs">
                                    {{ item.payment?.paid_at ? formatDate(item.payment.paid_at, true) : '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <template v-if="item.payment?.status === 'pending'">
                                            <button
                                                type="button"
                                                @click="handleVerify(item.order_id)"
                                                :disabled="processingId === item.order_id"
                                                class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 text-white font-bold text-xs rounded-lg transition shadow-sm"
                                            >
                                                {{ processingId === item.order_id ? 'Proses...' : '✓ Verifikasi' }}
                                            </button>
                                            <button
                                                type="button"
                                                @click="handleReject(item.order_id)"
                                                :disabled="processingId === item.order_id"
                                                class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 disabled:opacity-60 text-white font-bold text-xs rounded-lg transition shadow-sm"
                                            >
                                                Tolak
                                            </button>
                                        </template>
                                        <button
                                            v-else
                                            type="button"
                                            @click="openProofModal(item)"
                                            class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-medium text-xs rounded-lg transition"
                                        >
                                            Lihat Detail
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Proof Viewer Modal -->
        <Modal
            :show="showProofModal"
            title="Detail Bukti Pembayaran"
            maxWidth="md"
            @close="showProofModal = false"
        >
            <div v-if="activeItem" class="space-y-4">
                <!-- Order Info -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <p class="text-[10px] text-slate-400 font-semibold uppercase tracking-wide mb-0.5">Kode Order</p>
                        <p class="font-mono font-bold text-slate-900 text-sm">{{ activeOrderCode }}</p>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <p class="text-[10px] text-slate-400 font-semibold uppercase tracking-wide mb-0.5">Total Tagihan</p>
                        <p class="font-bold text-slate-900 text-sm">{{ formatRupiah(activeItem.total_amount) }}</p>
                    </div>
                </div>

                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                    <p class="text-[10px] text-slate-400 font-semibold uppercase tracking-wide mb-0.5">Customer</p>
                    <p class="font-semibold text-slate-900 text-sm">{{ activeItem.user }}</p>
                </div>

                <div>
                    <p class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Data Bukti Pembayaran:</p>
                    <div class="p-4 bg-slate-100 rounded-xl font-mono text-xs text-slate-800 break-all select-all border border-slate-200">
                        {{ activeProof }}
                    </div>
                </div>

                <!-- Image preview if URL -->
                <div v-if="activeProof && (activeProof.match(/\.(jpg|jpeg|png|webp)$/i) || activeProof.startsWith('http'))" class="mt-2">
                    <p class="text-xs text-slate-400 mb-1.5">Pratinjau Gambar:</p>
                    <img
                        :src="activeProof"
                        alt="Bukti Transfer"
                        class="w-full max-h-64 object-contain rounded-xl border border-slate-200 bg-white"
                        @error="$event.target.style.display='none'"
                    />
                </div>

                <!-- Verify/Reject Buttons (in modal too) -->
                <div v-if="activeItem.payment?.status === 'pending'" class="flex items-center gap-2 pt-2 border-t border-slate-100">
                    <button
                        type="button"
                        @click="handleVerify(activeItem.order_id)"
                        :disabled="processingId === activeItem.order_id"
                        class="grow py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 text-white font-bold text-sm rounded-lg transition shadow-sm"
                    >
                        {{ processingId === activeItem.order_id ? 'Memproses...' : '✓ Verifikasi & Terbitkan Tiket' }}
                    </button>
                    <button
                        type="button"
                        @click="handleReject(activeItem.order_id)"
                        :disabled="processingId === activeItem.order_id"
                        class="px-5 py-2.5 bg-rose-100 hover:bg-rose-200 text-rose-700 font-bold text-sm rounded-lg transition"
                    >
                        Tolak
                    </button>
                </div>
            </div>

            <template #footer>
                <button
                    type="button"
                    @click="showProofModal = false"
                    class="w-full px-4 py-2.5 text-sm font-semibold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition"
                >
                    Tutup
                </button>
            </template>
        </Modal>
    </AdminLayout>
</template>
