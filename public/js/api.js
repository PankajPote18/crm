const API_BASE = '/api';

function getToken() {
    return localStorage.getItem('token');
}

function getUser() {
    const raw = localStorage.getItem('user');
    return raw ? JSON.parse(raw) : null;
}

function requireAuth() {
    if (!getToken()) {
        window.location.href = '/login';
    }
}

function logout() {
    apiFetch('/logout', { method: 'POST' }).finally(() => {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        window.location.href = '/login';
    });
}

async function apiFetch(path, options = {}) {
    const headers = Object.assign({
        Accept: 'application/json',
        'Content-Type': 'application/json',
    }, options.headers || {});

    const token = getToken();
    if (token) {
        headers.Authorization = `Bearer ${token}`;
    }

    const response = await fetch(API_BASE + path, { ...options, headers });
    const body = await response.json().catch(() => null);

    if (!response.ok) {
        const error = new Error(body?.message || 'Request failed');
        error.status = response.status;
        error.body = body;
        throw error;
    }

    return body;
}
