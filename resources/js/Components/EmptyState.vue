<script setup>
defineProps({
    title: {
        type: String,
        default: 'Tidak Ada Data',
    },
    description: {
        type: String,
        default: '',
    },
    actionText: {
        type: String,
        default: '',
    },
});

defineEmits(['action']);
</script>

<template>
    <div class="py-12 px-4 text-center max-w-md mx-auto">
        <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center mx-auto mb-3">
            <slot name="icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
            </slot>
        </div>
        <h3 class="text-base font-semibold text-slate-900 mb-1">{{ title }}</h3>
        <p v-if="description" class="text-sm text-slate-500 mb-4 leading-relaxed">{{ description }}</p>
        <button
            v-if="actionText"
            type="button"
            @click="$emit('action')"
            class="inline-flex items-center justify-center px-4 py-2 border border-sky-200 shadow-2xs text-xs sm:text-sm font-semibold rounded-xl text-sky-700 bg-sky-50 hover:bg-sky-100 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
        >
            {{ actionText }}
        </button>
    </div>
</template>
