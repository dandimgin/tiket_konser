// API Client for Tiketin REST API
const BASE_URL = '/api';

export class ApiError extends Error {
    constructor(message, status = 500, errors = {}) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.errors = errors;
    }
}

function getToken() {
    if (typeof window !== 'undefined') {
        const token = localStorage.getItem('tiket_token');
        if (token && token !== 'undefined' && token !== 'null') {
            return token;
        }
    }
    return null;
}

export async function request(endpoint, options = {}) {
    const url = endpoint.startsWith('http') ? endpoint : `${BASE_URL}${endpoint.startsWith('/') ? '' : '/'}${endpoint}`;
    
    const headers = {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        ...(options.headers || {}),
    };

    const token = getToken();
    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }

    const config = {
        ...options,
        headers,
    };

    if (config.body && typeof config.body === 'object' && !(config.body instanceof FormData)) {
        config.body = JSON.stringify(config.body);
    }

    if (config.body instanceof FormData) {
        delete headers['Content-Type'];
    }

    try {
        const response = await fetch(url, config);
        
        let data = null;
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            data = await response.json();
        } else {
            const text = await response.text();
            data = { message: text };
        }

        if (!response.ok) {
            const errorMessage = data?.message || data?.error || `Terjadi kesalahan (HTTP ${response.status})`;
            const validationErrors = data?.errors || {};
            throw new ApiError(errorMessage, response.status, validationErrors);
        }

        return data;
    } catch (err) {
        if (err instanceof ApiError) {
            throw err;
        }
        throw new ApiError(err.message || 'Gagal terhubung ke server', 500);
    }
}

export const api = {
    // Auth
    login: (credentials) => request('/login', { method: 'POST', body: credentials }),
    register: (payload) => request('/register', { method: 'POST', body: payload }),
    logout: () => request('/logout', { method: 'POST' }),

    // Artists
    getArtists: () => request('/artists'),
    getArtist: (id) => request(`/artists/${id}`),
    createArtist: (data) => request('/artists', { method: 'POST', body: data }),
    updateArtist: (id, data) => request(`/artists/${id}`, { method: 'PUT', body: data }),
    deleteArtist: (id) => request(`/artists/${id}`, { method: 'DELETE' }),

    // Events
    getEvents: () => request('/events'),
    getEvent: (id) => request(`/events/${id}`),
    createEvent: (data) => request('/events', { method: 'POST', body: data }),
    updateEvent: (id, data) => request(`/events/${id}`, { method: 'PUT', body: data }),
    deleteEvent: (id) => request(`/events/${id}`, { method: 'DELETE' }),

    // Event Artists
    getEventArtists: (eventId) => request(`/events/${eventId}/artists`),
    attachEventArtist: (eventId, artistId) => request(`/events/${eventId}/artists`, { method: 'POST', body: { artist_id: artistId } }),
    detachEventArtist: (eventId, artistId) => request(`/events/${eventId}/artists/${artistId}`, { method: 'DELETE' }),

    // Ticket Categories
    getTicketCategories: (eventId) => request(`/events/${eventId}/ticket-categories`),
    createTicketCategory: (eventId, data) => request(`/events/${eventId}/ticket-categories`, { method: 'POST', body: data }),
    updateTicketCategory: (eventId, id, data) => request(`/events/${eventId}/ticket-categories/${id}`, { method: 'PUT', body: data }),
    deleteTicketCategory: (eventId, id) => request(`/events/${eventId}/ticket-categories/${id}`, { method: 'DELETE' }),

    // Orders
    getOrders: () => request('/orders'),
    getOrder: (id) => request(`/orders/${id}`),
    createOrder: (data) => request('/orders', { method: 'POST', body: data }),

    // Payments
    getOrderPayment: (orderId) => request(`/orders/${orderId}/payment`),
    submitPaymentProof: (orderId, data) => request(`/orders/${orderId}/payment/proof`, { method: 'POST', body: data }),
    getAdminPayments: () => request('/admin/payments'),
    verifyPayment: (orderId) => request(`/admin/orders/${orderId}/payment/verify`, { method: 'POST' }),
    rejectPayment: (orderId) => request(`/admin/orders/${orderId}/payment/reject`, { method: 'POST' }),

    // Tickets
    getTickets: () => request('/tickets'),
    getTicket: (id) => request(`/tickets/${id}`),

    // Dashboard
    getDashboard: () => request('/admin/dashboard'),
};
