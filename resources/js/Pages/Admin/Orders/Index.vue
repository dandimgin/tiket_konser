<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Badge from '@/Components/Badge.vue';
import Modal from '@/Components/Modal.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { api } from '@/services/api';
import { formatRupiah, formatDate } from '@/stores/auth';

const orders = ref([]);
const loading = ref(true);
const search = ref('');
const statusFilter = ref('all');

// Detail modal
const showDetailModal = ref(false);
const selectedOrder = ref(null);

async function fetchOrders() {
    loading.value = true;
    try {
        const res = await api.getOrders();
        orders.value = res.data || [];
    } catch (err) {
        // error
    } finally {
        loading.value = false;
    }
}

const filteredOrders = computed(() => {
    return orders.value.filter(o => {
        const matchStatus = statusFilter.value === 'all' || o.status === statusFilter.value;
        const q = search.value.toLowerCase();
        const matchSearch = !search.value ||
            o.order_code.toLowerCase().includes(q) ||
            (o.user?.name && o.user.name.toLowerCase().includes(q)) ||
            (o.user?.email && o.user.email.toLowerCase().includes(q));
        return matchStatus && matchSearch;
    });
});

function openDetail(order) {
    selectedOrder.value = order;
    showDetailModal.value = true;
}

onMounted(() => {
    fetchOrders();
});
</script>

<template>
    <AdminLayout>
        <Head title="Kelola Pesanan - Admin Tiketin" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Daftar Seluruh Pesanan</h1>
                    <p class="text-xs text-slate-500 mt-1">Data transaksi pembelian tiket konser dari seluruh customer</p>
                </div>
                <button
                    @click="fetchOrders"
                    class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition shadow-2xs self-start sm:self-auto"
                >
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Segarkan
                </button>
            </div>

            <!-- Filters & Search -->
            <div class="bg-white p-4 rounded-xl border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="relative max-w-sm w-full">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Cari kode order, nama pembeli..."
                        class="w-full pl-9 pr-4 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-900"
                    />
                </div>

                <div class="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0">
                    <button
                        v-for="st in [
                            { id: 'all', label: 'Semua' },
                            { id: 'pending', label: 'Pending' },
                            { id: 'paid', label: 'Lunas' },
                            { id: 'cancelled', label: 'Dibatalkan' }
                        ]"
                        :key="st.id"
                        @click="statusFilter = st.id"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition"
                        :class="statusFilter === st.id
                            ? 'bg-slate-900 text-white'
                            : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'"
                    >
                        {{ st.label }}
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-2xs">
                <div v-if="loading" class="p-8 text-center text-xs text-slate-400">
                    Memuat data pesanan...
                </div>

                <div v-else-if="filteredOrders.length === 0" class="p-8 text-center">
                    <EmptyState
                        title="Tidak Ada Pesanan"
                        description="Belum ada transaksi pesanan yang sesuai kriteria pencarian."
                    />
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50/70 text-slate-500 font-semibold uppercase tracking-wider">
                                <th class="py-3 px-4">Kode Order</th>
                                <th class="py-3 px-4">Nama Customer</th>
                                <th class="py-3 px-4">Item Tiket</th>
                                <th class="py-3 px-4">Total Tagihan</th>
                                <th class="py-3 px-4">Status Order</th>
                                <th class="py-3 px-4">Tanggal Pesan</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr v-for="order in filteredOrders" :key="order.id" class="hover:bg-slate-50/60 transition">
                                <td class="py-3.5 px-4 font-mono font-bold text-slate-900 whitespace-nowrap">
                                    {{ order.order_code }}
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <p class="font-bold text-slate-800">{{ order.user?.name || '-' }}</p>
                                    <p class="text-slate-400 text-[11px]">{{ order.user?.email || '-' }}</p>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="text-slate-600 font-medium">
                                        {{ order.order_items?.length || 0 }} Jenis Tiket
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-bold text-slate-900 whitespace-nowrap">
                                    {{ formatRupiah(order.total_amount) }}
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <Badge :status="order.status" size="sm" />
                                </td>
                                <td class="py-3.5 px-4 text-slate-500 whitespace-nowrap">
                                    {{ formatDate(order.ordered_at || order.created_at, true) }}
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <button
                                        @click="openDetail(order)"
                                        class="px-2.5 py-1 bg-white border border-slate-300 text-slate-700 rounded-md font-semibold text-[11px] hover:bg-slate-50 transition"
                                    >
                                        Rincian
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Detail Modal -->
        <Modal
            :show="showDetailModal"
            title="Rincian Transaksi Pesanan"
            maxWidth="lg"
            @close="showDetailModal = false"
        >
            <div v-if="selectedOrder" class="space-y-5 text-xs">
                <!-- Header Info -->
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <span class="text-slate-400 block mb-0.5">Kode Pesanan</span>
                        <span class="font-mono font-bold text-slate-900 text-sm">{{ selectedOrder.order_code }}</span>
                    </div>
                    <Badge :status="selectedOrder.status" />
                </div>

                <!-- Customer Details -->
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                    <span class="font-bold text-slate-700 uppercase tracking-wider block mb-1">Informasi Pemesan</span>
                    <p class="font-bold text-slate-900">{{ selectedOrder.user?.name }}</p>
                    <p class="text-slate-500">{{ selectedOrder.user?.email }}</p>
                </div>

                <!-- Items -->
                <div>
                    <span class="font-bold text-slate-700 uppercase tracking-wider block mb-2">Item Tiket Dipesan</span>
                    <div class="space-y-2 bg-slate-50 p-3 rounded-xl border border-slate-200">
                        <div
                            v-for="item in selectedOrder.order_items"
                            :key="item.id"
                            class="flex justify-between items-center"
                        >
                            <div>
                                <p class="font-bold text-slate-800">{{ item.ticket_category?.name || 'Tiket' }} &times; {{ item.quantity }}</p>
                                <p class="text-slate-400">@ {{ formatRupiah(item.price) }}</p>
                            </div>
                            <span class="font-bold text-slate-900">{{ formatRupiah(item.subtotal) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Total -->
                <div class="flex justify-between items-baseline pt-2 border-t border-slate-100">
                    <span class="font-semibold text-slate-600">Total Transaksi</span>
                    <span class="text-base font-extrabold text-slate-900">{{ formatRupiah(selectedOrder.total_amount) }}</span>
                </div>
            </div>

            <template #footer>
                <button
                    type="button"
                    @click="showDetailModal = false"
                    class="px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-md hover:bg-slate-50"
                >
                    Tutup
                </button>
            </template>
        </Modal>
    </AdminLayout>
</template>
