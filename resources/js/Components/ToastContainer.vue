<script setup>
import { useToast } from '@/composables/useToast';

const { toasts, remove } = useToast();
</script>

<template>
    <div class="fixed bottom-5 right-5 z-50 flex flex-col gap-2 max-w-sm w-full pointer-events-none px-4 sm:px-0" aria-live="polite">
        <transition-group
            enter-active-class="transform ease-out duration-200 transition"
            enter-from-class="translate-y-2 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-for="toast in toasts"
                :key="toast.id"
                class="pointer-events-auto flex items-start justify-between gap-3 p-4 rounded-lg shadow-sm border text-sm font-medium"
                :class="{
                    'bg-slate-900 text-white border-slate-800': toast.type === 'info',
                    'bg-emerald-900 text-white border-emerald-800': toast.type === 'success',
                    'bg-rose-900 text-white border-rose-800': toast.type === 'error',
                }"
            >
                <div class="flex items-start gap-2.5">
                    <!-- Icon -->
                    <svg v-if="toast.type === 'success'" class="w-5 h-5 text-emerald-300 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <svg v-else-if="toast.type === 'error'" class="w-5 h-5 text-rose-300 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <svg v-else class="w-5 h-5 text-slate-300 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="leading-relaxed">{{ toast.message }}</p>
                </div>
                <button
                    @click="remove(toast.id)"
                    class="text-white/70 hover:text-white p-1 rounded transition focus-visible:ring-2 focus-visible:ring-white focus:outline-none"
                    aria-label="Tutup notifikasi"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </transition-group>
    </div>
</template>
