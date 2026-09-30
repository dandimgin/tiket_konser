<script setup>
import Navbar from '@/Components/Navbar.vue';
import ToastContainer from '@/Components/ToastContainer.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex flex-col bg-white text-slate-900 font-sans">
        <Navbar />

        <main class="grow">
            <slot />
        </main>

        <!-- Footer sesuai Figma -->
        <footer class="border-t border-slate-200 bg-white pt-12 pb-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 mb-10">
                    <!-- Brand Column -->
                    <div class="lg:col-span-1">
                        <Link href="/" class="flex items-center gap-2 mb-3 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 rounded-lg w-fit">
                            <img
                                src="/images/tiketin-logo.png"
                                alt="Tiketin"
                                class="h-7 w-auto object-contain"
                            />
                        </Link>
                        <p class="text-xs text-slate-500 leading-relaxed mb-4">
                            Platform resmi penjualan tiket konser musik, festival, dan live entertainment terpercaya di seluruh Indonesia.
                        </p>
                        <div class="flex items-center gap-3">
                            <button type="button" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500 transition" aria-label="Bagikan">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92s2.92-1.31 2.92-2.92-1.31-2.92-2.92-2.92z"/></svg>
                            </button>
                            <button type="button" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500 transition" aria-label="Email">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </button>
                            <button type="button" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500 transition" aria-label="Kontak">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Fitur Utama -->
                    <div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-4">Fitur Utama</h3>
                        <ul class="space-y-2.5">
                            <li><Link href="/" class="text-xs text-slate-500 hover:text-sky-600 transition">Jelajah Konser</Link></li>
                            <li><Link href="/" class="text-xs text-slate-500 hover:text-sky-600 transition">Event Trending</Link></li>
                            <li><Link href="/orders" class="text-xs text-slate-500 hover:text-sky-600 transition">Pesanan & E-Tiket</Link></li>
                        </ul>
                    </div>

                    <!-- Bantuan & Panduan -->
                    <div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-4">Bantuan & Panduan</h3>
                        <ul class="space-y-2.5">
                            <li><span class="text-xs text-slate-500 cursor-default">Pusat Bantuan (FAQ)</span></li>
                            <li><span class="text-xs text-slate-500 cursor-default">Kebijakan Refund & Reschedule</span></li>
                            <li><span class="text-xs text-slate-500 cursor-default">Panduan Masuk Venue</span></li>
                            <li><span class="text-xs text-slate-500 cursor-default">Hubungi Tim Support</span></li>
                        </ul>
                    </div>

                    <!-- Metode Pembayaran -->
                    <div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-4">Metode Pembayaran Resmi</h3>
                        <p class="text-xs text-slate-500 mb-3">Dukungan instan transaksi aman perbankan Indonesia.</p>
                        <div class="flex flex-wrap gap-2">
                            <span class="px-2.5 py-1 bg-blue-50 text-blue-700 border border-blue-200 rounded text-[11px] font-bold">BCA</span>
                            <span class="px-2.5 py-1 bg-yellow-50 text-yellow-700 border border-yellow-200 rounded text-[11px] font-bold">Mandiri</span>
                            <span class="px-2.5 py-1 bg-orange-50 text-orange-700 border border-orange-200 rounded text-[11px] font-bold">BRI</span>
                            <span class="px-2.5 py-1 bg-sky-50 text-sky-700 border border-sky-200 rounded text-[11px] font-bold">QRIS</span>
                            <span class="px-2.5 py-1 bg-green-50 text-green-700 border border-green-200 rounded text-[11px] font-bold">GoPay</span>
                        </div>
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-6 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <p class="text-xs text-slate-400">© {{ new Date().getFullYear() }} Tiketin Indonesia. Seluruh hak cipta dilindungi.</p>
                    <div class="flex items-center gap-4 text-xs text-slate-400">
                        <span class="hover:text-slate-600 cursor-pointer transition">Syarat & Ketentuan</span>
                        <span class="hover:text-slate-600 cursor-pointer transition">Privasi Data</span>
                        <span class="hover:text-slate-600 cursor-pointer transition">Kemitraan Promotor</span>
                    </div>
                </div>
            </div>
        </footer>

        <ToastContainer />
    </div>
</template>
