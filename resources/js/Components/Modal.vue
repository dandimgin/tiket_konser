<script setup>
import { onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: '',
    },
    maxWidth: {
        type: String,
        default: 'md', // sm, md, lg, xl
    },
});

const emit = defineEmits(['close']);

function close() {
    emit('close');
}

function handleKeydown(e) {
    if (e.key === 'Escape' && props.show) {
        close();
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
});

watch(() => props.show, (val) => {
    if (typeof document !== 'undefined') {
        if (val) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }
});
</script>

<template>
    <teleport to="body">
        <transition
            enter-active-class="ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center"
                role="dialog"
                aria-modal="true"
            >
                <!-- Backdrop -->
                <div
                    class="fixed inset-0 bg-slate-950/40 backdrop-blur-sm transition-opacity"
                    @click="close"
                    aria-hidden="true"
                />

                <!-- Modal Dialog (Liquid Glass Surface) -->
                <div
                    class="relative bg-white/95 backdrop-blur-md rounded-2xl shadow-[0_20px_60px_-15px_rgba(0,0,0,0.15)] border border-slate-200/80 overflow-hidden transform transition-all w-full max-h-[90vh] flex flex-col my-auto"
                    :class="{
                        'max-w-sm': maxWidth === 'sm',
                        'max-w-md': maxWidth === 'md',
                        'max-w-lg': maxWidth === 'lg',
                        'max-w-2xl': maxWidth === 'xl',
                        'max-w-4xl': maxWidth === '2xl',
                    }"
                >
                    <!-- Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100/80 shrink-0">
                        <h3 class="text-base font-bold text-slate-900">
                            <slot name="title">{{ title }}</slot>
                        </h3>
                        <button
                            type="button"
                            @click="close"
                            class="w-8 h-8 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100/80 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
                            aria-label="Tutup"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Content -->
                    <div class="px-6 py-5 overflow-y-auto grow">
                        <slot />
                    </div>

                    <!-- Footer -->
                    <div v-if="$slots.footer" class="px-6 py-3.5 bg-slate-50/70 border-t border-slate-100/80 flex items-center justify-end gap-3 shrink-0">
                        <slot name="footer" />
                    </div>
                </div>
            </div>
        </transition>
    </teleport>
</template>
