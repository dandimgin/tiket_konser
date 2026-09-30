import { reactive, computed } from 'vue';
import { api } from '@/services/api';
import { router } from '@inertiajs/vue3';

function getStoredToken() {
    if (typeof window === 'undefined') return null;
    const token = localStorage.getItem('tiket_token');
    if (!token || token === 'undefined' || token === 'null' || token.trim() === '') {
        return null;
    }
    return token;
}

function getStoredUser() {
    if (typeof window === 'undefined') return null;
    try {
        const stored = localStorage.getItem('tiket_user');
        if (!stored || stored === 'undefined' || stored === 'null') return null;
        return JSON.parse(stored);
    } catch {
        return null;
    }
}

export function getRoleName(user) {
    if (!user) return null;
    if (typeof user.role === 'string') return user.role;
    if (typeof user.role === 'object' && user.role?.name) return user.role.name;
    return null;
}

export const authState = reactive({
    user: getStoredUser(),
    token: getStoredToken(),
});

export const auth = {
    state: authState,
    user: computed(() => authState.user),
    token: computed(() => authState.token),
    role: computed(() => getRoleName(authState.user)),
    isAuthenticated: computed(() => !!authState.token && !!authState.user),
    isAdmin: computed(() => getRoleName(authState.user) === 'Admin'),
    isCustomer: computed(() => getRoleName(authState.user) === 'Customer'),

    setAuth(user, token) {
        if (!user || !token) return;
        authState.user = user;
        authState.token = token;
        if (typeof window !== 'undefined') {
            localStorage.setItem('tiket_token', token);
            localStorage.setItem('tiket_user', JSON.stringify(user));
        }
    },

    clearAuth() {
        authState.user = null;
        authState.token = null;
        if (typeof window !== 'undefined') {
            localStorage.removeItem('tiket_token');
            localStorage.removeItem('tiket_user');
        }
    },

    async login(credentials) {
        const res = await api.login(credentials);
        const token = res.token || res.access_token;
        this.setAuth(res.user, token);
        return res;
    },

    async register(data) {
        const res = await api.register(data);
        const token = res.token || res.access_token;
        this.setAuth(res.user, token);
        return res;
    },

    async logout() {
        try {
            await api.logout();
        } catch {
            // Ignore if already revoked
        } finally {
            this.clearAuth();
            router.visit('/login');
        }
    }
};

export function formatRupiah(amount) {
    if (amount === undefined || amount === null) return 'Rp0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
}

export function formatDate(dateString, withTime = false) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString;

    const options = {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    };

    if (withTime) {
        options.hour = '2-digit';
        options.minute = '2-digit';
    }

    return new Intl.DateTimeFormat('id-ID', options).format(date);
}
