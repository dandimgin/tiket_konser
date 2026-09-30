import { ref } from 'vue';

const toasts = ref([]);
let nextId = 1;

export function useToast() {
    function show(message, type = 'info', duration = 4000) {
        const id = nextId++;
        toasts.value.push({ id, message, type });

        if (duration > 0) {
            setTimeout(() => {
                remove(id);
            }, duration);
        }
    }

    function success(message, duration = 4000) {
        show(message, 'success', duration);
    }

    function error(message, duration = 5000) {
        show(message, 'error', duration);
    }

    function remove(id) {
        toasts.value = toasts.value.filter(t => t.id !== id);
    }

    return {
        toasts,
        show,
        success,
        error,
        remove,
    };
}
