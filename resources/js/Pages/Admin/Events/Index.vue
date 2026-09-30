<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Badge from '@/Components/Badge.vue';
import Modal from '@/Components/Modal.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { api } from '@/services/api';
import { formatDate } from '@/stores/auth';
import { useToast } from '@/composables/useToast';

const toast = useToast();

const events = ref([]);
const allArtists = ref([]);
const loading = ref(true);
const error = ref(null);
const search = ref('');

// Modals
const showFormModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const submitting = ref(false);

const showArtistsModal = ref(false);
const selectedEvent = ref(null);
const eventArtists = ref([]);
const selectedArtistToAttach = ref('');

const showDeleteModal = ref(false);
const itemToDelete = ref(null);

const form = reactive({
    name: '',
    description: '',
    location: '',
    event_date: '',
    poster: '',
    status: 'draft',
});

const formErrors = ref({});

async function loadData() {
    loading.value = true;
    error.value = null;
    try {
        const [eventsRes, artistsRes] = await Promise.all([
            api.getEvents(),
            api.getArtists(),
        ]);
        events.value = eventsRes.data || [];
        allArtists.value = artistsRes.data || [];
    } catch (err) {
        error.value = err.message || 'Gagal memuat data event.';
    } finally {
        loading.value = false;
    }
}

const filteredEvents = computed(() => {
    if (!search.value) return events.value;
    const q = search.value.toLowerCase();
    return events.value.filter(e =>
        e.name.toLowerCase().includes(q) ||
        (e.location && e.location.toLowerCase().includes(q)) ||
        e.status.toLowerCase().includes(q)
    );
});

function openCreateModal() {
    isEditing.value = false;
    editingId.value = null;
    formErrors.value = {};
    form.name = '';
    form.description = '';
    form.location = '';
    form.event_date = '';
    form.poster = '';
    form.status = 'published';
    showFormModal.value = true;
}

function openEditModal(event) {
    isEditing.value = true;
    editingId.value = event.id;
    formErrors.value = {};
    form.name = event.name;
    form.description = event.description || '';
    form.location = event.location;
    // Format to datetime-local input YYYY-MM-DDTHH:mm
    if (event.event_date) {
        const d = new Date(event.event_date);
        const iso = d.toISOString();
        form.event_date = iso.substring(0, 16);
    } else {
        form.event_date = '';
    }
    form.poster = event.poster || '';
    form.status = event.status;
    showFormModal.value = true;
}

async function handleSaveEvent() {
    submitting.value = true;
    formErrors.value = {};

    try {
        const payload = {
            name: form.name,
            description: form.description || null,
            location: form.location,
            event_date: form.event_date ? form.event_date.replace('T', ' ') : null,
            poster: form.poster || null,
            status: form.status,
        };

        if (isEditing.value) {
            await api.updateEvent(editingId.value, payload);
            toast.success('Event berhasil diperbarui!');
        } else {
            await api.createEvent(payload);
            toast.success('Event baru berhasil ditambahkan!');
        }

        showFormModal.value = false;
        await loadData();
    } catch (err) {
        if (err.status === 422) {
            formErrors.value = err.errors || {};
        }
        toast.error(err.message || 'Gagal menyimpan event');
    } finally {
        submitting.value = false;
    }
}

// Manage Artists Modal
async function openArtistsModal(event) {
    selectedEvent.value = event;
    selectedArtistToAttach.value = '';
    showArtistsModal.value = true;
    try {
        const res = await api.getEventArtists(event.id);
        eventArtists.value = res.data || [];
    } catch (err) {
        toast.error('Gagal mengambil daftar artis untuk event ini');
    }
}

async function handleAttachArtist() {
    if (!selectedArtistToAttach.value) return;
    try {
        await api.attachEventArtist(selectedEvent.value.id, selectedArtistToAttach.value);
        toast.success('Artis berhasil ditambahkan ke lineup event!');
        selectedArtistToAttach.value = '';
        const res = await api.getEventArtists(selectedEvent.value.id);
        eventArtists.value = res.data || [];
        await loadData();
    } catch (err) {
        toast.error(err.message || 'Gagal menghubungkan artis');
    }
}

async function handleDetachArtist(artistId) {
    try {
        await api.detachEventArtist(selectedEvent.value.id, artistId);
        toast.success('Artis dilepas dari lineup event');
        const res = await api.getEventArtists(selectedEvent.value.id);
        eventArtists.value = res.data || [];
        await loadData();
    } catch (err) {
        toast.error(err.message || 'Gagal melepas artis');
    }
}

// Delete Event
function confirmDelete(event) {
    itemToDelete.value = event;
    showDeleteModal.value = true;
}

async function executeDelete() {
    if (!itemToDelete.value) return;
    try {
        await api.deleteEvent(itemToDelete.value.id);
        toast.success(`Event ${itemToDelete.value.name} berhasil dihapus`);
        showDeleteModal.value = false;
        await loadData();
    } catch (err) {
        toast.error(err.message || 'Gagal menghapus event');
    }
}

onMounted(() => {
    loadData();
});
</script>

<template>
    <AdminLayout>
        <Head title="Kelola Event - Admin Tiketin" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Kelola Event Konser</h1>
                    <p class="text-xs text-slate-500 mt-1">Daftar event konser, jadwal tayang, lokasi, dan relasi artis</p>
                </div>
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition shadow-xs self-start sm:self-auto"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Tambah Event Baru
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
                        placeholder="Cari event berdasarkan nama, lokasi, status..."
                        class="w-full pl-9 pr-4 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-900"
                    />
                </div>
            </div>

            <!-- Table of Events -->
            <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-2xs">
                <div v-if="loading" class="p-8 text-center text-xs text-slate-400">
                    Memuat data event...
                </div>

                <div v-else-if="filteredEvents.length === 0" class="p-8 text-center">
                    <EmptyState
                        title="Tidak Ada Event"
                        description="Belum ada data event yang tersimpan atau cocok dengan pencarian."
                        actionText="Buat Event Baru"
                        @action="openCreateModal"
                    />
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50/70 text-slate-500 font-semibold uppercase tracking-wider">
                                <th class="py-3 px-4">Event</th>
                                <th class="py-3 px-4">Jadwal &amp; Lokasi</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">Artis</th>
                                <th class="py-3 px-4">Kategori Tiket</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr v-for="event in filteredEvents" :key="event.id" class="hover:bg-slate-50/60 transition">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <img
                                            v-if="event.poster"
                                            :src="event.poster"
                                            :alt="event.name"
                                            class="w-10 h-10 rounded-lg object-cover border border-slate-200 shrink-0"
                                            @error="$event.target.style.display='none'"
                                        />
                                        <div v-else class="w-10 h-10 rounded-lg bg-slate-100 text-slate-400 flex items-center justify-center font-bold text-xs shrink-0">
                                            TK
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900 text-sm leading-snug">{{ event.name }}</p>
                                            <p class="text-[11px] text-slate-400 line-clamp-1 max-w-xs">{{ event.description || '-' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <p class="font-semibold text-slate-800">{{ formatDate(event.event_date) }}</p>
                                    <p class="text-slate-400 text-[11px]">{{ event.location }}</p>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <Badge :status="event.status" size="sm" />
                                </td>
                                <td class="py-3.5 px-4">
                                    <button
                                        @click="openArtistsModal(event)"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200 transition"
                                    >
                                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        <span>{{ event.artists?.length || 0 }} Artis</span>
                                    </button>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <Link
                                        href="/admin/ticket-categories"
                                        class="text-[11px] font-semibold text-slate-600 hover:text-slate-900 underline"
                                    >
                                        {{ event.ticket_categories?.length || 0 }} Kategori
                                    </Link>
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button
                                            @click="openEditModal(event)"
                                            class="p-1.5 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-md transition"
                                            title="Edit event"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="confirmDelete(event)"
                                            class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-md transition"
                                            title="Hapus event"
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

        <!-- Create / Edit Event Modal -->
        <Modal
            :show="showFormModal"
            :title="isEditing ? 'Perbarui Data Event' : 'Tambah Event Konser Baru'"
            maxWidth="xl"
            @close="showFormModal = false"
        >
            <form @submit.prevent="handleSaveEvent" class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Event *</label>
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        maxlength="200"
                        placeholder="Contoh: Konser Sheila on 7 Live Jakarta"
                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-900"
                    />
                    <p v-if="formErrors.name" class="text-rose-600 mt-1">{{ formErrors.name[0] }}</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Tanggal &amp; Waktu *</label>
                        <input
                            v-model="form.event_date"
                            type="datetime-local"
                            required
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-900"
                        />
                        <p v-if="formErrors.event_date" class="text-rose-600 mt-1">{{ formErrors.event_date[0] }}</p>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Status Event *</label>
                        <select
                            v-model="form.status"
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-900"
                        >
                            <option value="draft">Draft (Belum Ditampilkan)</option>
                            <option value="published">Published (Tayang &amp; Aktif)</option>
                            <option value="closed">Closed (Pemesanan Ditutup)</option>
                            <option value="finished">Finished (Acara Telah Selesai)</option>
                        </select>
                        <p v-if="formErrors.status" class="text-rose-600 mt-1">{{ formErrors.status[0] }}</p>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Lokasi / Venue *</label>
                    <input
                        v-model="form.location"
                        type="text"
                        required
                        placeholder="Contoh: Gelora Bung Karno, Senayan, Jakarta"
                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-900"
                    />
                    <p v-if="formErrors.location" class="text-rose-600 mt-1">{{ formErrors.location[0] }}</p>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">URL Poster (Opsional)</label>
                    <input
                        v-model="form.poster"
                        type="text"
                        placeholder="https://... atau path/poster.jpg"
                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-900"
                    />
                </div>

                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Deskripsi Event</label>
                    <textarea
                        v-model="form.description"
                        rows="3"
                        placeholder="Rincian informasi acara, ketentuan masuk, dll..."
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
                    @click="handleSaveEvent"
                    :disabled="submitting"
                    class="px-4 py-2 text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 rounded-md transition shadow-2xs"
                >
                    {{ submitting ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Buat Event') }}
                </button>
            </template>
        </Modal>

        <!-- Manage Lineup Artists Modal -->
        <Modal
            :show="showArtistsModal"
            title="Kelola Lineup Artis Event"
            maxWidth="md"
            @close="showArtistsModal = false"
        >
            <div v-if="selectedEvent" class="space-y-4 text-xs">
                <p class="font-bold text-slate-900 text-sm">{{ selectedEvent.name }}</p>

                <!-- Attach Form -->
                <div class="flex items-center gap-2 pt-2">
                    <select
                        v-model="selectedArtistToAttach"
                        class="grow px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-900"
                    >
                        <option value="">Pilih Artis untuk Ditambahkan...</option>
                        <option
                            v-for="a in allArtists"
                            :key="a.id"
                            :value="a.id"
                            :disabled="eventArtists.some(ea => ea.id === a.id)"
                        >
                            {{ a.name }} {{ eventArtists.some(ea => ea.id === a.id) ? '(Sudah Ada)' : '' }}
                        </option>
                    </select>
                    <button
                        type="button"
                        @click="handleAttachArtist"
                        :disabled="!selectedArtistToAttach"
                        class="px-3 py-2 bg-slate-900 disabled:bg-slate-300 text-white rounded-lg font-bold text-xs shrink-0"
                    >
                        Tambah
                    </button>
                </div>

                <!-- Current Artists List -->
                <div class="mt-4 border-t border-slate-100 pt-3">
                    <span class="font-semibold text-slate-500 uppercase tracking-wider block mb-2">Artis Terdaftar ({{ eventArtists.length }})</span>
                    <div v-if="eventArtists.length === 0" class="text-slate-400 italic py-2 text-center">
                        Belum ada artis yang dihubungkan ke event ini.
                    </div>
                    <div v-else class="space-y-2 max-h-56 overflow-y-auto pr-1">
                        <div
                            v-for="a in eventArtists"
                            :key="a.id"
                            class="flex items-center justify-between p-2 rounded-lg bg-slate-50 border border-slate-200"
                        >
                            <span class="font-bold text-slate-800">{{ a.name }}</span>
                            <button
                                type="button"
                                @click="handleDetachArtist(a.id)"
                                class="text-rose-600 hover:text-rose-800 text-[11px] font-semibold px-2 py-0.5 rounded hover:bg-rose-50"
                            >
                                Hapus
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <button
                    type="button"
                    @click="showArtistsModal = false"
                    class="w-full px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-md hover:bg-slate-50"
                >
                    Selesai
                </button>
            </template>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <Modal
            :show="showDeleteModal"
            title="Konfirmasi Hapus Event"
            maxWidth="sm"
            @close="showDeleteModal = false"
        >
            <div class="text-xs text-slate-600 space-y-2">
                <p>Apakah Anda yakin ingin menghapus event <strong class="text-slate-900">{{ itemToDelete?.name }}</strong>?</p>
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
