<script setup>
import { ref, watch, onUnmounted, nextTick } from 'vue';
import Modal from '@/Components/Modal.vue';
import Badge from '@/Components/Badge.vue';
import { api } from '@/services/api';
import { formatDate } from '@/stores/auth';
import { useToast } from '@/composables/useToast';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'ticket-updated']);
const toast = useToast();

const activeTab = ref('camera'); // 'camera' | 'manual'
const manualCode = ref('');
const scanning = ref(false);
const scannerError = ref(null);
const verificationResult = ref(null);
const verifying = ref(false);
const allTickets = ref([]);

let html5QrCode = null;

// Sound effects using Web Audio API
function playBeep(type = 'success') {
    if (typeof window === 'undefined' || !window.AudioContext) return;
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);

        if (type === 'success') {
            osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
            osc.frequency.setValueAtTime(880, ctx.currentTime + 0.1); // A5
            gain.gain.setValueAtTime(0.2, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);
            osc.start();
            osc.stop(ctx.currentTime + 0.3);
        } else {
            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(220, ctx.currentTime);
            gain.gain.setValueAtTime(0.3, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.4);
            osc.start();
            osc.stop(ctx.currentTime + 0.4);
        }
    } catch (e) {
        // AudioContext disabled or blocked
    }
}

async function loadTickets() {
    try {
        const res = await api.getTickets();
        allTickets.value = res.data || [];
    } catch (e) {
        console.error('Failed to load tickets:', e);
    }
}

async function startCameraScanner() {
    scannerError.value = null;
    await nextTick();
    const readerEl = document.getElementById('qr-reader-container');
    if (!readerEl) return;

    try {
        const { Html5Qrcode } = await import('html5-qrcode');
        if (html5QrCode) {
            try { await html5QrCode.stop(); } catch (e) {}
        }

        html5QrCode = new Html5Qrcode('qr-reader-container');
        scanning.value = true;

        await html5QrCode.start(
            { facingMode: 'environment' },
            {
                fps: 10,
                qrbox: { width: 220, height: 220 },
            },
            (decodedText) => {
                handleScannedCode(decodedText);
            },
            () => {
                // scanning frame error (ignore)
            }
        );
    } catch (err) {
        scanning.value = false;
        scannerError.value = 'Kamera tidak dapat diakses atau izin ditolak. Silakan gunakan tab Input Manual di bawah.';
    }
}

async function stopCameraScanner() {
    if (html5QrCode && scanning.value) {
        try {
            await html5QrCode.stop();
            html5QrCode.clear();
        } catch (e) {
            // ignore
        }
    }
    scanning.value = false;
}

function handleScannedCode(rawText) {
    if (verifying.value) return;
    let code = rawText.trim();

    // Check if JSON
    if (code.startsWith('{') && code.includes('code')) {
        try {
            const parsed = JSON.parse(code);
            code = parsed.code || code;
        } catch (e) {}
    }

    verifyTicketCode(code);
}

function verifyTicketCode(code) {
    if (!code) return;
    verifying.value = true;
    manualCode.value = code;

    // Search in tickets
    const clean = code.toUpperCase().trim();
    const found = allTickets.value.find(t => t.ticket_code?.toUpperCase() === clean);

    if (found) {
        verificationResult.value = {
            found: true,
            ticket: found,
            status: found.status,
            checkedIn: found.status === 'used',
            time: new Date(),
        };

        if (found.status === 'used') {
            playBeep('error');
            toast?.error?.('Peringatan: Tiket ini sudah pernah digunakan!');
        } else {
            playBeep('success');
            toast?.success?.('Tiket VALID! Siap untuk check-in gate.');
        }
    } else {
        playBeep('error');
        verificationResult.value = {
            found: false,
            code: clean,
            time: new Date(),
        };
        toast?.error?.('Tiket tidak terdaftar di sistem Tiketin!');
    }

    verifying.value = false;
}

function checkInTicket() {
    if (!verificationResult.value?.ticket) return;
    const t = verificationResult.value.ticket;
    t.status = 'used';
    verificationResult.value.checkedIn = true;
    playBeep('success');
    toast?.success?.(`Check-in Gate Berhasil! Tiket ${t.ticket_code} telah divalidasi.`);
    emit('ticket-updated', t);
}

function resetScanner() {
    verificationResult.value = null;
    manualCode.value = '';
    if (activeTab.value === 'camera') {
        startCameraScanner();
    }
}

watch(() => props.show, (isOpen) => {
    if (isOpen) {
        loadTickets();
        if (activeTab.value === 'camera') {
            setTimeout(() => {
                startCameraScanner();
            }, 300);
        }
    } else {
        stopCameraScanner();
        verificationResult.value = null;
    }
});

watch(activeTab, (tab) => {
    if (tab === 'camera' && props.show) {
        startCameraScanner();
    } else {
        stopCameraScanner();
    }
});

onUnmounted(() => {
    stopCameraScanner();
});
</script>

<template>
    <Modal
        :show="show"
        title="Scanner & Verifikasi Tiket QR"
        maxWidth="lg"
        @close="$emit('close')"
    >
        <div class="space-y-5">
            <!-- Header Mode Switcher -->
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-1 bg-slate-100 rounded-xl p-1">
                    <button
                        type="button"
                        @click="activeTab = 'camera'"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-1.5"
                        :class="activeTab === 'camera' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                    >
                        <svg class="w-3.5 h-3.5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Kamera Scanner
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'manual'"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-1.5"
                        :class="activeTab === 'manual' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                    >
                        <svg class="w-3.5 h-3.5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Input Kode Manual
                    </button>
                </div>

                <div class="text-[11px] text-slate-400 font-mono">
                    {{ allTickets.length }} Tiket Terdaftar
                </div>
            </div>

            <!-- Verification Active Result Display -->
            <div v-if="verificationResult" class="space-y-4 animate-fade-in">
                <!-- VALID TICKET -->
                <div
                    v-if="verificationResult.found && verificationResult.ticket.status !== 'used'"
                    class="p-5 bg-emerald-50/80 border-2 border-emerald-500 rounded-2xl shadow-sm"
                >
                    <div class="flex items-start justify-between gap-3 mb-4 pb-3 border-b border-emerald-200">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <div>
                                <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-700 block">Status Verifikasi</span>
                                <h3 class="text-base font-extrabold text-emerald-950">TIKET RESMI & VALID</h3>
                            </div>
                        </div>
                        <span class="px-3 py-1 bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-full text-xs font-bold">
                            Siap Masuk
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-xs mb-4">
                        <div class="p-3 bg-white/80 rounded-xl border border-emerald-100">
                            <span class="text-slate-400 block text-[10px] uppercase font-semibold">Nama Konser</span>
                            <span class="font-bold text-slate-900 block truncate">{{ verificationResult.ticket.ticket_category?.event?.name || 'Event Konser' }}</span>
                        </div>
                        <div class="p-3 bg-white/80 rounded-xl border border-emerald-100">
                            <span class="text-slate-400 block text-[10px] uppercase font-semibold">Kategori Area</span>
                            <span class="font-bold text-sky-700 block">{{ verificationResult.ticket.ticket_category?.name || 'Kategori' }}</span>
                        </div>
                        <div class="p-3 bg-white/80 rounded-xl border border-emerald-100">
                            <span class="text-slate-400 block text-[10px] uppercase font-semibold">Kode Tiket (Barcode)</span>
                            <span class="font-mono font-bold text-slate-900 block">{{ verificationResult.ticket.ticket_code }}</span>
                        </div>
                        <div class="p-3 bg-white/80 rounded-xl border border-emerald-100">
                            <span class="text-slate-400 block text-[10px] uppercase font-semibold">Pemilik / Pembeli</span>
                            <span class="font-bold text-slate-900 block truncate">{{ verificationResult.ticket.order?.user?.name || 'Customer' }}</span>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="checkInTicket"
                        class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-sm rounded-xl transition shadow-sm flex items-center justify-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Konfirmasi Masuk Turnstile Gate (Check-In)
                    </button>
                </div>

                <!-- ALREADY USED TICKET -->
                <div
                    v-else-if="verificationResult.found && verificationResult.ticket.status === 'used'"
                    class="p-5 bg-amber-50 border-2 border-amber-500 rounded-2xl shadow-sm"
                >
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-12 h-12 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <div>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-amber-800 block">Peringatan Duplikasi</span>
                            <h3 class="text-base font-extrabold text-amber-950">TIKET SUDAH PERNAH DIGUNAKAN</h3>
                        </div>
                    </div>
                    <p class="text-xs text-amber-800 mb-3">
                        Kode tiket <span class="font-mono font-bold">{{ verificationResult.ticket.ticket_code }}</span> telah tercatat check-in masuk gate sebelumnya. Tiket tidak dapat digunakan ulang.
                    </p>
                    <div class="p-3 bg-white/90 rounded-xl text-xs space-y-1 text-slate-600 border border-amber-200">
                        <p><span class="text-slate-400">Konser:</span> <strong class="text-slate-900">{{ verificationResult.ticket.ticket_category?.event?.name }}</strong></p>
                        <p><span class="text-slate-400">Area:</span> <strong class="text-slate-900">{{ verificationResult.ticket.ticket_category?.name }}</strong></p>
                    </div>
                </div>

                <!-- NOT FOUND TICKET -->
                <div
                    v-else
                    class="p-5 bg-rose-50 border-2 border-rose-500 rounded-2xl shadow-sm text-center"
                >
                    <div class="w-12 h-12 rounded-xl bg-rose-600 text-white flex items-center justify-center mx-auto mb-3 shadow-xs">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </div>
                    <h3 class="text-base font-extrabold text-rose-950 mb-1">TIKET TIDAK DITEMUKAN</h3>
                    <p class="text-xs text-rose-700 mb-2">
                        Kode barcode <span class="font-mono font-bold">{{ verificationResult.code }}</span> tidak terdaftar di sistem resmi Tiketin.
                    </p>
                    <p class="text-[11px] text-rose-500">Pastikan pengunjung memperlihatkan tiket dari aplikasi Tiketin resmi.</p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button
                        type="button"
                        @click="resetScanner"
                        class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition"
                    >
                        Scan Tiket Berikutnya
                    </button>
                </div>
            </div>

            <!-- Scanner & Manual Mode -->
            <div v-else>
                <!-- Camera Scanner Tab -->
                <div v-if="activeTab === 'camera'" class="space-y-4 text-center">
                    <div class="relative w-full max-w-sm mx-auto overflow-hidden rounded-2xl border-2 border-sky-400 bg-slate-950 shadow-inner">
                        <div id="qr-reader-container" class="w-full aspect-square"></div>
                        <div class="absolute inset-0 pointer-events-none flex flex-col items-center justify-between p-6">
                            <span class="text-[11px] font-semibold text-white/80 bg-slate-900/60 px-3 py-1 rounded-full backdrop-blur-xs">
                                Arahkan kamera ke QR Code Tiket
                            </span>
                            <div class="w-48 h-48 border-2 border-sky-400 rounded-2xl animate-pulse"></div>
                            <span class="text-[10px] text-white/60">Auto-detect QR code</span>
                        </div>
                    </div>

                    <div v-if="scannerError" class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800 text-left">
                        {{ scannerError }}
                    </div>

                    <p class="text-xs text-slate-500">
                        Pindai kode QR dinamis pada tiket pengunjung untuk langsung memvalidasi keaslian dan status turnstile gate.
                    </p>
                </div>

                <!-- Manual Input Tab -->
                <div v-if="activeTab === 'manual'" class="space-y-4">
                    <div>
                        <label for="manualTicketCode" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Nomor / Kode Barcode Tiket
                        </label>
                        <div class="flex gap-2">
                            <input
                                id="manualTicketCode"
                                v-model="manualCode"
                                type="text"
                                placeholder="Contoh: TIK-A1B2C3D4E5"
                                @keydown.enter="verifyTicketCode(manualCode)"
                                class="grow px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-mono font-bold text-slate-900 focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100 transition"
                            />
                            <button
                                type="button"
                                @click="verifyTicketCode(manualCode)"
                                class="px-5 py-2.5 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-bold transition shadow-sm shrink-0"
                            >
                                Periksa Tiket
                            </button>
                        </div>
                    </div>

                    <!-- Quick Sample List of Active Tickets -->
                    <div class="pt-2">
                        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide block mb-2">Tiket Tersedia (Klik Cepat Untuk Verifikasi):</span>
                        <div class="space-y-1.5 max-h-48 overflow-y-auto pr-1">
                            <div
                                v-for="t in allTickets.slice(0, 5)"
                                :key="t.id"
                                @click="verifyTicketCode(t.ticket_code)"
                                class="p-2.5 rounded-xl border border-slate-200 hover:border-sky-300 hover:bg-sky-50/50 cursor-pointer flex items-center justify-between transition text-xs"
                            >
                                <div class="min-w-0">
                                    <span class="font-mono font-bold text-slate-900 block">{{ t.ticket_code }}</span>
                                    <span class="text-slate-500 text-[11px] truncate block">{{ t.ticket_category?.name }}</span>
                                </div>
                                <Badge :status="t.status" size="sm" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <button
                type="button"
                @click="$emit('close')"
                class="px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition"
            >
                Tutup Scanner
            </button>
        </template>
    </Modal>
</template>
