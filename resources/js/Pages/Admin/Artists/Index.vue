<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Modal from '@/Components/Modal.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { api } from '@/services/api';
import { useToast } from '@/composables/useToast';

const toast = useToast();

const artists = ref([]);
const loading = ref(true);
const error = ref(null);
const search = ref('');

// Form modal
const showFormModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const submitting = ref(false);

const showDeleteModal = ref(false);
const itemToDelete = ref(null);

const form = reactive({
    name: '',
    bio: '',
    photo: '',
});

const formErrors = ref({});

async function fetchArtists() {
    loading.value = true;
    error.value = null;
    try {
        const res = await api.getArtists();
        artists.value = res.data || [];
    } catch (err) {
        error.value = err.message || 'Gagal memuat data artis.';
    } finally {
        loading.value = false;
    }
}

const filteredArtists = computed(() => {
    if (!search.value) return artists.value;
    const q = search.value.toLowerCase();
    return artists.value.filter(a =>
        a.name.toLowerCase().includes(q) ||
        (a.bio && a.bio.toLowerCase().includes(q))
    );
});

function openCreateModal() {
    isEditing.value = false;
    editingId.value = null;
    formErrors.value = {};
    form.name = '';
    form.bio = '';
    form.photo = '';
    showFormModal.value = true;
}

function openEditModal(artist) {
    isEditing.value = true;
    editingId.value = artist.id;
    formErrors.value = {};
    form.name = artist.name;
    form.bio = artist.bio || '';
    form.photo = artist.photo || '';
    showFormModal.value = true;
}

async function handleSaveArtist() {
    submitting.value = true;
    formErrors.value = {};

    try {
        const payload = {
            name: form.name,
            bio: form.bio || null,
            photo: form.photo || null,
        };

        if (isEditing.value) {
            await api.updateArtist(editingId.value, payload);
            toast.success('Data artis berhasil diperbarui!');
        } else {
            await api.createArtist(payload);
            toast.success('Artis baru berhasil ditambahkan!');
        }

        showFormModal.value = false;
        await fetchArtists();
    } catch (err) {
        if (err.status === 422) {
            formErrors.value = err.errors || {};
        }
        toast.error(err.message || 'Gagal menyimpan data artis');
    } finally {
        submitting.value = false;
    }
}

function confirmDelete(artist) {
    itemToDelete.value = artist;
    showDeleteModal.value = true;
}

async function executeDelete() {
    if (!itemToDelete.value) return;
    try {
        await api.deleteArtist(itemToDelete.value.id);
        toast.success(`Artis ${itemToDelete.value.name} berhasil dihapus`);
        showDeleteModal.value = false;
        await fetchArtists();
    } catch (err) {
        toast.error(err.message || 'Gagal menghapus data artis');
    }
}

onMounted(() => {
    fetchArtists();
});
</script>

<template>
    <AdminLayout>
        <Head title="Kelola Artis - Admin Tiketin" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Kelola Lineup Artis</h1>
                    <p class="text-xs text-slate-500 mt-1">Daftar profil musisi, solois, band, dan penampil konser</p>
                </div>
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition shadow-xs self-start sm:self-auto"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Tambah Artis Baru
                </button>
            </div>

            <!-- Search Bar -->
            <div class="bg-white p-4 rounded-xl border border-slate-200">
                <div class="relative max-w-md">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Cari artis berdasarkan nama atau bio..."
                        class="w-full pl-9 pr-4 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-900"
                    />
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-2xs">
                <div v-if="loading" class="p-8 text-center text-xs text-slate-400">
                    Memuat data artis...
                </div>

                <div v-else-if="filteredArtists.length === 0" class="p-8 text-center">
                    <EmptyState
                        title="Tidak Ada Artis"
                        description="Belum ada data musisi/artis yang tersimpan."
                        actionText="Tambah Artis Baru"
                        @action="openCreateModal"
                    />
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50/70 text-slate-500 font-semibold uppercase tracking-wider">
                                <th class="py-3 px-4">Artis / Musisi</th>
                                <th class="py-3 px-4">Biografi Singkat</th>
                                <th class="py-3 px-4">Partisipasi Event</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr v-for="artist in filteredArtists" :key="artist.id" class="hover:bg-slate-50/60 transition">
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <img
                                            v-if="artist.photo"
                                            :src="artist.photo"
                                            :alt="artist.name"
                                            class="w-10 h-10 rounded-full object-cover border border-slate-200 shrink-0"
                                            @error="$event.target.style.display='none'"
                                        />
                                        <div v-else class="w-10 h-10 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center font-bold text-sm shrink-0">
                                            {{ artist.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <span class="font-bold text-slate-900 text-sm">{{ artist.name }}</span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <p class="text-slate-500 line-clamp-2 max-w-md">{{ artist.bio || '-' }}</p>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="inline-block px-2.5 py-1 rounded-md text-[11px] font-semibold bg-slate-100 text-slate-700">
                                        {{ artist.events?.length || 0 }} Event
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button
                                            @click="openEditModal(artist)"
                                            class="p-1.5 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-md transition"
                                            title="Edit data artis"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="confirmDelete(artist)"
                                            class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-md transition"
                                            title="Hapus data artis"
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
            :title="isEditing ? 'Perbarui Profil Artis' : 'Tambah Artis Baru'"
            maxWidth="md"
            @close="showFormModal = false"
        >
            <form @submit.prevent="handleSaveArtist" class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Artis / Band *</label>
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        maxlength="150"
                        placeholder="Contoh: Tulus"
                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-900"
                    />
                    <p v-if="formErrors.name" class="text-rose-600 mt-1">{{ formErrors.name[0] }}</p>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">URL Foto (Opsional)</label>
                    <input
                        v-model="form.photo"
                        type="text"
                        placeholder="https://... atau path/foto.jpg"
                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-900"
                    />
                </div>

                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Biografi / Deskripsi Artis</label>
                    <textarea
                        v-model="form.bio"
                        rows="3"
                        placeholder="Informasi profil singkat mengenai genre musik dan karir..."
                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-900"
                    ></textarea>
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
                    @click="handleSaveArtist"
                    :disabled="submitting"
                    class="px-4 py-2 text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 rounded-md transition shadow-2xs"
                >
                    {{ submitting ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Tambah Artis') }}
                </button>
            </template>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <Modal
            :show="showDeleteModal"
            title="Konfirmasi Hapus Artis"
            maxWidth="sm"
            @close="showDeleteModal = false"
        >
            <div class="text-xs text-slate-600 space-y-2">
                <p>Apakah Anda yakin ingin menghapus artis <strong class="text-slate-900">{{ itemToDelete?.name }}</strong>?</p>
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
