<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Modal from '@/Components/Modal.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { api } from '@/services/api';
import { formatRupiah } from '@/stores/auth';
import { useToast } from '@/composables/useToast';

const toast = useToast();

const events = ref([]);
const selectedEventId = ref('');
const categories = ref([]);
const loading = ref(true);

// Modals
const showFormModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const submitting = ref(false);

const showDeleteModal = ref(false);
const itemToDelete = ref(null);

const form = reactive({
    event_id: '',
    name: '',
    price: 0,
    quota: 100,
});

const formErrors = ref({});

async function loadInitial() {
    loading.value = true;
    try {
        const eventsRes = await api.getEvents();
        events.value = eventsRes.data || [];

        if (events.value.length > 0) {
            selectedEventId.value = events.value[0].id;
            await loadCategoriesForEvent(selectedEventId.value);
        }
    } catch (err) {
        toast.error(err.message || 'Gagal memuat event');
    } finally {
        loading.value = false;
    }
}

async function loadCategoriesForEvent(eventId) {
    if (!eventId) {
        categories.value = [];
        return;
    }
    loading.value = true;
    try {
        const res = await api.getTicketCategories(eventId);
        categories.value = res.data || [];
    } catch (err) {
        toast.error(err.message || 'Gagal memuat kategori tiket');
    } finally {
        loading.value = false;
    }
}

function handleEventChange() {
    loadCategoriesForEvent(selectedEventId.value);
}

function openCreateModal() {
    if (!selectedEventId.value) {
        toast.error('Pilih event terlebih dahulu.');
        return;
    }
    isEditing.value = false;
    editingId.value = null;
    formErrors.value = {};
    form.event_id = selectedEventId.value;
    form.name = '';
    form.price = 150000;
    form.quota = 100;
    showFormModal.value = true;
}

function openEditModal(category) {
    isEditing.value = true;
    editingId.value = category.id;
    formErrors.value = {};
    form.event_id = category.event_id;
    form.name = category.name;
    form.price = Number(category.price);
    form.quota = Number(category.quota);
    showFormModal.value = true;
}

async function handleSaveCategory() {
    submitting.value = true;
    formErrors.value = {};

    try {
        const payload = {
            name: form.name,
            price: Number(form.price),
            quota: Number(form.quota),
        };

        if (isEditing.value) {
            await api.updateTicketCategory(form.event_id, editingId.value, payload);
            toast.success('Kategori tiket berhasil diperbarui!');
        } else {
            await api.createTicketCategory(form.event_id, payload);
            toast.success('Kategori tiket baru berhasil dibuat!');
        }

        showFormModal.value = false;
        await loadCategoriesForEvent(selectedEventId.value);
    } catch (err) {
        if (err.status === 422) {
            formErrors.value = err.errors || {};
        }
        toast.error(err.message || 'Gagal menyimpan kategori tiket');
    } finally {
        submitting.value = false;
    }
}

function confirmDelete(category) {
    itemToDelete.value = category;
    showDeleteModal.value = true;
}

async function executeDelete() {
    if (!itemToDelete.value) return;
    try {
        await api.deleteTicketCategory(itemToDelete.value.event_id, itemToDelete.value.id);
        toast.success(`Kategori ${itemToDelete.value.name} berhasil dihapus`);
        showDeleteModal.value = false;
        await loadCategoriesForEvent(selectedEventId.value);
    } catch (err) {
        toast.error(err.message || 'Gagal menghapus kategori tiket');
    }
}

onMounted(() => {
    loadInitial();
});
</script>

<template>
    <AdminLayout>
        <Head title="Kelola Kategori Tiket - Admin Tiketin" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Kategori &amp; Kuota Tiket</h1>
                    <p class="text-xs text-slate-500 mt-1">Atur harga, kuota tempat duduk, dan stok tiket untuk setiap konser</p>
                </div>
                <button
                    @click="openCreateModal"
                    :disabled="!selectedEventId"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-900 hover:bg-slate-800 disabled:bg-slate-300 text-white rounded-xl text-xs font-bold transition shadow-xs self-start sm:self-auto"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Tambah Kategori Tiket
                </button>
            </div>

            <!-- Event Selector Filter -->
            <div class="bg-white p-4 rounded-xl border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <label class="text-xs font-bold text-slate-700 uppercase tracking-wider shrink-0">Pilih Event:</label>
                    <select
                        v-model="selectedEventId"
                        @change="handleEventChange"
                        class="px-3.5 py-2 bg-white border border-slate-300 rounded-lg text-xs font-semibold text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-900 min-w-[260px]"
                    >
                        <option value="" disabled>-- Pilih Event Konser --</option>
                        <option v-for="ev in events" :key="ev.id" :value="ev.id">
                            {{ ev.name }} ({{ ev.status }})
                        </option>
                    </select>
                </div>

                <span class="text-xs font-semibold text-slate-500 bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200 self-start sm:self-auto">
                    {{ categories.length }} Kategori Ditemukan
                </span>
            </div>

            <!-- Table -->
            <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-2xs">
                <div v-if="loading" class="p-8 text-center text-xs text-slate-400">
                    Memuat data kategori tiket...
                </div>

                <div v-else-if="categories.length === 0" class="p-8 text-center">
                    <EmptyState
                        title="Belum Ada Kategori Tiket"
                        description="Belum ada kategori tiket (misal: VIP, Regular) yang dibuat untuk event ini."
                        actionText="Buat Kategori Baru"
                        @action="openCreateModal"
                    />
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50/70 text-slate-500 font-semibold uppercase tracking-wider">
                                <th class="py-3 px-4">Nama Kategori</th>
                                <th class="py-3 px-4">Harga Satuan</th>
                                <th class="py-3 px-4">Total Kuota</th>
                                <th class="py-3 px-4">Terjual</th>
                                <th class="py-3 px-4">Sisa Kuota</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr v-for="cat in categories" :key="cat.id" class="hover:bg-slate-50/60 transition">
                                <td class="py-3.5 px-4 font-bold text-slate-900 text-sm">
                                    {{ cat.name }}
                                </td>
                                <td class="py-3.5 px-4 font-bold text-slate-900 whitespace-nowrap">
                                    {{ formatRupiah(cat.price) }}
                                </td>
                                <td class="py-3.5 px-4 font-medium text-slate-700 whitespace-nowrap">
                                    {{ cat.quota }} Tiket
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="inline-block px-2.5 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-700">
                                        {{ cat.sold }} Tiket
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span
                                        class="inline-block px-2.5 py-0.5 rounded text-[11px] font-semibold"
                                        :class="(cat.quota - cat.sold) > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'"
                                    >
                                        {{ cat.quota - cat.sold }} Tersedia
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button
                                            @click="openEditModal(cat)"
                                            class="p-1.5 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-md transition"
                                            title="Edit kategori"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="confirmDelete(cat)"
                                            class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-md transition"
                                            title="Hapus kategori"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Form Modal -->
        <Modal
            :show="showFormModal"
            :title="isEditing ? 'Perbarui Kategori Tiket' : 'Tambah Kategori Tiket Baru'"
            maxWidth="md"
            @close="showFormModal = false"
        >
            <form @submit.prevent="handleSaveCategory" class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Kategori *</label>
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        placeholder="Contoh: VIP, VVIP, Regular, Festival A"
                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-900"
                    />
                    <p v-if="formErrors.name" class="text-rose-600 mt-1">{{ formErrors.name[0] }}</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Harga (Rupiah) *</label>
                        <input
                            v-model.number="form.price"
                            type="number"
                            min="0"
                            step="1000"
                            required
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-900"
                        />
                        <p v-if="formErrors.price" class="text-rose-600 mt-1">{{ formErrors.price[0] }}</p>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Total Kuota *</label>
                        <input
                            v-model.number="form.quota"
                            type="number"
                            min="1"
                            required
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-900"
                        />
                        <p v-if="formErrors.quota" class="text-rose-600 mt-1">{{ formErrors.quota[0] }}</p>
                    </div>
                </div>
            </form>

            <template #footer>
                <button
                    type="button"
                    @click="showFormModal = false"
                    class="px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-md hover:bg-slate-50"
                >
                    Batal
                </button>
                <button
                    type="button"
                    @click="handleSaveCategory"
                    :disabled="submitting"
                    class="px-4 py-2 text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 rounded-md transition shadow-2xs"
                >
                    {{ submitting ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Buat Kategori') }}
                </button>
            </template>
        </Modal>

        <!-- Delete Modal -->
        <Modal
            :show="showDeleteModal"
            title="Konfirmasi Hapus Kategori"
            maxWidth="sm"
            @close="showDeleteModal = false"
        >
            <div class="text-xs text-slate-600 space-y-2">
                <p>Apakah Anda yakin ingin menghapus kategori tiket <strong class="text-slate-900">{{ itemToDelete?.name }}</strong>?</p>
                <p class="text-rose-600 font-semibold">Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <template #footer>
                <button
                    type="button"
                    @click="showDeleteModal = false"
                    class="px-3.5 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-md"
                >
                    Batal
                </button>
                <button
                    type="button"
                    @click="executeDelete"
                    class="px-3.5 py-1.5 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-md"
                >
                    Hapus
                </button>
            </template>
        </Modal>
    </AdminLayout>
</template>
