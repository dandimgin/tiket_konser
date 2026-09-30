<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: {
        type: String,
        default: '',
    },
    label: {
        type: String,
        default: '',
    },
    variant: {
        type: String,
        default: '', // blue, purple, pink, yellow, emerald, rose
    },
    size: {
        type: String,
        default: 'md', // sm, md
    }
});

const config = computed(() => {
    if (props.variant === 'blue' || props.variant === 'sky') {
        return {
            bg: 'bg-sky-50 text-sky-800 border-sky-200',
            dot: 'bg-sky-500',
            text: props.label || props.status || 'Info',
        };
    }
    if (props.variant === 'purple') {
        return {
            bg: 'bg-purple-50 text-purple-800 border-purple-200',
            dot: 'bg-purple-500',
            text: props.label || props.status || 'Kategori',
        };
    }
    if (props.variant === 'pink') {
        return {
            bg: 'bg-pink-50 text-pink-800 border-pink-200',
            dot: 'bg-pink-500',
            text: props.label || props.status || 'Promo',
        };
    }
    if (props.variant === 'yellow' || props.variant === 'amber') {
        return {
            bg: 'bg-amber-50 text-amber-800 border-amber-200',
            dot: 'bg-amber-500',
            text: props.label || props.status || 'Perhatian',
        };
    }

    const s = props.status?.toLowerCase();
    switch (s) {
        case 'published':
        case 'verified':
        case 'paid':
        case 'active':
            return {
                bg: 'bg-emerald-50 text-emerald-800 border-emerald-200',
                dot: 'bg-emerald-500',
                text: props.label || (s === 'published' ? 'Tersedia' : s === 'verified' ? 'Terverifikasi' : s === 'paid' ? 'Lunas' : 'Aktif'),
            };
        case 'pending':
            return {
                bg: 'bg-amber-50 text-amber-800 border-amber-200',
                dot: 'bg-amber-500',
                text: props.label || 'Menunggu',
            };
        case 'draft':
            return {
                bg: 'bg-slate-100 text-slate-700 border-slate-200',
                dot: 'bg-slate-400',
                text: props.label || 'Draft',
            };
        case 'closed':
        case 'rejected':
        case 'cancelled':
            return {
                bg: 'bg-rose-50 text-rose-800 border-rose-200',
                dot: 'bg-rose-500',
                text: props.label || (s === 'closed' ? 'Ditutup' : s === 'rejected' ? 'Ditolak' : 'Dibatalkan'),
            };
        case 'finished':
        case 'used':
        case 'expired':
            return {
                bg: 'bg-slate-100 text-slate-600 border-slate-200',
                dot: 'bg-slate-400',
                text: props.label || (s === 'finished' ? 'Selesai' : s === 'used' ? 'Sudah Dipakai' : 'Kedaluwarsa'),
            };
        default:
            return {
                bg: 'bg-slate-50 text-slate-700 border-slate-200',
                dot: 'bg-slate-400',
                text: props.label || props.status || '-',
            };
    }
});
</script>

<template>
    <span
        :class="[
            'inline-flex items-center gap-1.5 font-semibold rounded-md border',
            size === 'sm' ? 'px-2 py-0.5 text-[11px]' : 'px-2.5 py-1 text-xs',
            config.bg
        ]"
    >
        <span class="w-1.5 h-1.5 rounded-full" :class="config.dot" aria-hidden="true" />
        {{ config.text }}
    </span>
</template>
