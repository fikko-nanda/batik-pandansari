/**
 * Batik Pandansari — Layer API Frontend
 * ------------------------------------------------------------------
 * Helper global untuk endpoint API team backend.
 * Spesifikasi: docs/API-BAKEND.md (sanctum token auth, envelope {success, message, data, errors?, meta?})
 *
 * Kontrak utama:
 *   POST /api/register  { nama_pengguna, email, kata_sandi, kata_sandi_confirmation, [nomor_telepon] } → 201
 *   POST /login     { email, kata_sandi } → data.token (plainTextToken, Bearer)
 *   GET  /me | POST /logout                                  → auth:sanctum
 *   GET  /categories, /categories/{id}                        → publik
 *   GET  /products?search=&category=&page=&per_page=          → publik (data + meta)
 *   GET  /products/{id} | /products/{id}/variants | /images   → publik
 *   CRUD /cart, /cart/items, /wishlist, /addresses           → auth:sanctum
 *   POST /checkout { id_user_address, kode_voucher?, biaya_pengiriman?, catatan? }
 *   GET  /orders[?status=&page=] | GET /orders/{id}
 *   POST /orders/{id}/payment → Midtrans Snap {snap_token, redirect_url, client_key}
 *   POST /vouchers/validate, GET /vouchers/available
 *
 * Token & profil user disimpan di localStorage (lihat getUser/setUser).
 */
const API_URL = import.meta.env?.VITE_API_URL || 'https://overshoot-rasping-afford.ngrok-free.dev';
const TOKEN_KEY = 'pandansari_token';
const USER_KEY = 'pandansari_user';

window.PandansariAPI = {
    url: API_URL,
    // Log kecil untuk verifikasi di console bahwa layer ini termuat
    // (jika tidak muncul → app.js gagal dimuat, biasanya Vite dev server tidak jalan)
    _init() { console.debug('[PandansariAPI] aktif →', API_URL); },

    /* ---------- Token & Profil ---------- */
    getToken() { return localStorage.getItem(TOKEN_KEY); },
    setToken(token) { localStorage.setItem(TOKEN_KEY, token); },
    setUser(user) { localStorage.setItem(USER_KEY, JSON.stringify(user ?? null)); },
    getUser() {
        try { return JSON.parse(localStorage.getItem(USER_KEY)); } catch { return null; }
    },
    clearSession() {
        localStorage.removeItem(TOKEN_KEY);
        localStorage.removeItem(USER_KEY);
    },
    clearToken() {
        this.clearSession();
    },

    /**
     * Fetch dasar: JSON in/out + Bearer token + header bypass ngrok.
     * Selalu resolve { ok, status, data, meta, message, errors } — bukan throw.
     */
    async fetch(path, { method = 'GET', body = null, raw = false } = {}) {
        const headers = {
            'Accept': 'application/json',
            'ngrok-skip-browser-warning': 'true',
        };
        if (body !== null) headers['Content-Type'] = 'application/json';
        const token = this.getToken();
        if (token) headers.Authorization = `Bearer ${token}`;

        try {
            const res = await fetch(`${API_URL}${path}`, {
                method,
                headers,
                body: raw ? body : (body ? JSON.stringify(body) : undefined),
            });
            let payload = null;
            try { payload = await res.json(); } catch { /* non-JSON */ }
            return {
                ok: res.ok,
                status: res.status,
                data: payload?.data ?? null,
                meta: payload?.meta ?? null,
                message: payload?.message ?? ((res.ok) ? 'Berhasil.' : `Terjadi kesalahan server (${res.status}).`),
                errors: payload?.errors ?? {},
            };
        } catch (e) {
            return {
                ok: false, status: 0, data: null, meta: null, errors: {},
                message: 'Tidak dapat menghubungi server. Pastikan tunnel backend (ngrok) aktif.',
            };
        }
    },

    /* ---------- Auth ---------- */
    register(payload) { return this.fetch('/register', { method: 'POST', body: payload }); },
    async login(email, kata_sandi) {
        const res = await this.fetch('/login', { method: 'POST', body: { email, kata_sandi } });
        if (res.ok && res.data?.token) {
            this.setToken(res.data.token);
            this.setUser(res.data.user ?? null);
        }
        return res;
    },
    logout() { return this.fetch('/logout', { method: 'POST' }); },
    async me() {
        const res = await this.fetch('api/me');
        if (res.ok && res.data) this.setUser(res.data);
        return res;
    },

    /* ---------- Kategori & Produk (publik) ---------- */
    categories() { return this.fetch('/categories'); },
    category(id) { return this.fetch(`/categories/${id}`); },
    products(params = {}) {
        const q = new URLSearchParams();
        for (const [k, v] of Object.entries(params)) if (v !== undefined && v !== null && v !== '') q.set(k, v);
        const qs = q.toString();
        return this.fetch(`/products${qs ? '?' + qs : ''}`);
    },
    product(id) { return this.fetch(`/products/${id}`); },
    productVariants(productId) { return this.fetch(`/products/${productId}/variants`); },
    productImages(productId) { return this.fetch(`/products/${productId}/images`); },

    /* ---------- Keranjang ---------- */
    cart() { return this.fetch('/cart'); },
    addCartItem(id_variant, jumlah = 1) {
        return this.fetch('/cart/items', { method: 'POST', body: { id_variant, jumlah } });
    },
    updateCartItem(id, jumlah) {
        return this.fetch(`/cart/items/${id}`, { method: 'PUT', body: { jumlah } });
    },
    removeCartItem(id) { return this.fetch(`/cart/items/${id}`, { method: 'DELETE' }); },
    clearCart() { return this.fetch('/cart', { method: 'DELETE' }); },

    /* ---------- Wishlist ---------- */
    wishlist() { return this.fetch('/wishlist'); },
    addWishlist(id_product) {
        return this.fetch('/wishlist', { method: 'POST', body: { id_product } });
    },
    removeWishlist(id) { return this.fetch(`/wishlist/${id}`, { method: 'DELETE' }); },

    /* ---------- Alamat ---------- */
    addresses() { return this.fetch('/addresses'); },
    addAddress(payload) { return this.fetch('/addresses', { method: 'POST', body: payload }); },
    updateAddress(id, payload) { return this.fetch(`/addresses/${id}`, { method: 'PUT', body: payload }); },
    deleteAddress(id) { return this.fetch(`/addresses/${id}`, { method: 'DELETE' }); },

    /* ---------- Voucher ---------- */
    availableVouchers(subtotal) {
        return this.fetch(`/vouchers/available${subtotal != null ? `?subtotal=${subtotal}` : ''}`);
    },
    validateVoucher(kode_voucher, subtotal) {
        return this.fetch('/vouchers/validate', { method: 'POST', body: { kode_voucher, subtotal } });
    },

    /* ---------- Checkout, Pesanan & Pembayaran ---------- */
    checkout(payload) { return this.fetch('/checkout', { method: 'POST', body: payload }); },
    orders(params = {}) {
        const q = new URLSearchParams();
        for (const [k, v] of Object.entries(params)) if (v !== undefined && v !== null && v !== '') q.set(k, v);
        const qs = q.toString();
        return this.fetch(`/orders${qs ? '?' + qs : ''}`);
    },
    order(id) { return this.fetch(`/orders/${id}`); },
    payOrder(id, payload = {}) {
        return this.fetch(`/orders/${id}/payment`, { method: 'POST', body: payload });
    },
    lastPayment(id) { return this.fetch(`/orders/${id}/payment`); },

    /* ---------- Helper format ---------- */
    formatRp(value) {
        const n = Number(value ?? 0);
        return 'Rp ' + n.toLocaleString('id-ID', { maximumFractionDigits: 0 });
    },
};

window.PandansariAPI._init();
