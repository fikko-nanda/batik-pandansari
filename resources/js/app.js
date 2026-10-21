/**
 * Batik Pandansari — Layer API Frontend
 * ------------------------------------------------------------------
 * Helper global untuk endpoint API team backend.
 * Spesifikasi: docs/BACKEND-API.md (sanctum token auth, envelope {success, message, data, errors?, meta?})
 *
 * Kontrak utama:
 *   POST /api/register  { nama_pengguna, email, kata_sandi, kata_sandi_confirmation, [nomor_telepon] } → 201
 *   POST /api/login     { email, kata_sandi } → data.token (plainTextToken, Bearer)
 *   GET  /api/me | POST /api/logout                                → auth:sanctum
 *   GET  /api/categories, /api/categories/{id}                     → publik
 *   GET  /api/products?search=&category=&page=&per_page=           → publik (data + meta)
 *   GET  /api/products/{id} | /variants | /images                  → publik
 *   CRUD /api/cart, /api/cart/items, /api/wishlist, /api/addresses → auth:sanctum
 *   POST /api/checkout { id_user_address, kode_voucher?, biaya_pengiriman?, catatan? }
 *   GET  /api/orders[?status=&page=] | GET /api/orders/{id}
 *   POST /api/orders/{id}/payment → Midtrans Snap {snap_token, redirect_url, client_key}
 *   POST /api/vouchers/validate, GET /api/vouchers/available
 *   PUT  /api/me { nama_pengguna?, email?, nomor_telepon? }
 *   PUT  /api/me/password { kata_sandi_saat_ini, kata_sandi, kata_sandi_confirmation }
 *   GET  /api/shipping/destinations?search= | POST /api/shipping/cost { destination, weight, courier }
 *   GET  /api/voucher-usages/me
 *   Seluruh endpoint admin → lihat sub-objek `PandansariAPI.admin` (di bawah).
 *
 * ATURAN PATH — SEMUA endpoint dipanggil TANPA prefix '/api', cukup:
 *   this.fetch('/me'), this.fetch('/cart/items', {...})
 *   → builder di fetch() otomatis menempelkan '/api' dari base dan
 *     mencegah '/api' ganda walau path tidak sengaja diawali 'api' atau '/api'.
 *
 * Token & profil user disimpan di localStorage (lihat getUser/setUser).
 */

/* ------------------------------------------------------------------
 * Base URL
 * ------------------------------------------------------------------
 * Sumber kebenaran: VITE_API_URL di .env (JANGAN hardcode URL di file ini).
 *   .env → VITE_API_URL=https://<subdomain>.ngrok-free.dev/api
 * Catatan:
 *   - Tail 'api' diakhir '/api' → semua panggilan jadi '.../api/me', dll.
 *   - Ubah .env → WAJIB restart Vite (composer dev) atau build ulang (npm run build).
 */
const API_URL = String(import.meta.env?.VITE_API_URL ?? '').trim().replace(/\/+$/, '');
if (!API_URL) {
    console.error(
        '[PandansariAPI] VITE_API_URL belum diset di .env — semua request API akan gagal. ' +
        'Isi seperti: VITE_API_URL=https://<subdomain>.ngrok-free.dev/api lalu restart Vite.'
    );
}

const TOKEN_KEY = 'pandansari_token';
const USER_KEY = 'pandansari_user';

/**
 * Penyusun URL aman:
 *   buildUrl('/me')                      → 'https://host/api/me'
 *   buildUrl('api/me')                   → 'https://host/api/me'   (tanpa /api ganda)
 *   buildUrl('/api/me')                  → 'https://host/api/me'   (tanpa /api ganda)
 *   buildUrl('https://lain.com/x')       → 'https://lain.com/x'    (URL absolut dipakai apa adanya)
 *   buildUrl('/orders?page=2')           → 'https://host/api/orders?page=2'
 */
function buildUrl(path = '') {
    const p = String(path ?? '').trim();

    // URL absolut → jangan disentuh
    if (/^https?:\/\//i.test(p)) return p;

    const base = API_URL; // sudah bersih: tanpa slash di akhir

    // Kumpulkan query string (jika ada) agar mudah dirapikan
    const [rawPath, rawQuery = ''] = p.split('?');
    const cleanPath = rawPath
        .replace(/^\/+/, '')          // buang slash awal   '/me' → 'me'
        .replace(/^api(?=\/|$)/, '')  // buang prefix 'api' ganda → 'api/me' | '/api/me' → 'me'
        .replace(/^\/+/, '');         // buang slash sisanya
    const query = rawQuery ? '?' + rawQuery : '';

    if (!cleanPath) return base + query;
    return `${base}/${cleanPath}${query}`;
}

window.PandansariAPI = {
    url: API_URL,
    buildUrl,
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
    /** Hapus token + profil user sekaligus (lencana di-nol-kan). */
    clearSession() {
        localStorage.removeItem(TOKEN_KEY);
        localStorage.removeItem(USER_KEY);
        this.setLencana(0, 0);
    },
    /** Alias aman agar pemanggilan PandansariAPI.clearToken() tidak error. */
    clearToken() { this.clearSession(); },

    /* ---------- Lencana global: badge Keranjang & Wishlist di navbar ---------- */
    _jadwalLencana: null,
    /** Tulis jumlah ke Alpine store global (dibaca navbar via $store.lencana). */
    setLencana(cart, wishlist) {
        const st = (window.Alpine && typeof Alpine.store === 'function') ? Alpine.store('lencana') : null;
        if (st) { st.cart = cart; st.wishlist = wishlist; }
    },
    /** Muat jumlah nyata dari API: GET /cart + GET /wishlist (dipanggil saat halaman dibuka bila login). */
    async muatLencana() {
        if (!this.getToken()) { this.setLencana(0, 0); return; }
        const [c, w] = await Promise.all([this.cart(), this.wishlist()]);
        const cItem = Array.isArray(c.data) ? c.data : (c.data && Array.isArray(c.data.items) ? c.data.items : []);
        const wItem = Array.isArray(w.data) ? w.data : (w.data && Array.isArray(w.data.items) ? w.data.items : []);
        const jumlahKeranjang = cItem.reduce((s, i) => s + Number(i.jumlah ?? 1), 0);
        this.setLencana(c.ok ? jumlahKeranjang : 0, w.ok ? wItem.length : 0);
    },
    /**
     * Refresh lencana segera-menant (isel diskala). Dipanggil otomatis oleh semua
     * helper yang mengubah keranjang/wishlist — badge navbar tetap sinkron tanpa
     * harus menyentuh tiap halaman.
     */
    jadwalLencana(tunda = 300) {
        clearTimeout(this._jadwalLencana);
        this._jadwalLencana = setTimeout(() => this.muatLencana(), tunda);
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
        // FormData (upload gambar) → biarkan browser set Content-Type + boundary
        const isForm = (typeof FormData !== 'undefined') && (body instanceof FormData);
        if (body !== null && !isForm) headers['Content-Type'] = 'application/json';
        const token = this.getToken();
        if (token) headers.Authorization = `Bearer ${token}`;

        // Batas waktu tunggu: 30 detik biasa, 90 detik untuk upload (FormData) —
        // supaya request yang menggantung ("pending" selamanya) berubah jadi pesan jelas.
        const timeoutMs = (isForm || raw) ? 90000 : 30000;
        const ctrl = new AbortController();
        const timer = setTimeout(() => ctrl.abort(), timeoutMs);
        try {
            const res = await fetch(buildUrl(path), {
                method,
                headers,
                body: body === null ? undefined : (raw || isForm ? body : JSON.stringify(body)),
                signal: ctrl.signal,
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
            const timedOut = !!e && (e.name === 'AbortError' || e.name === 'TimeoutError');
            return {
                ok: false, status: 0, data: null, meta: null, errors: {},
                message: timedOut
                    ? `Server tidak merespons selama ${Math.round(timeoutMs / 1000)} detik (timeout). Kemungkinan koneksi lambat atau tunnel ngrok terputus — periksa lalu coba lagi.`
                    : 'Tidak dapat menghubungi server. Pastikan tunnel backend (ngrok) aktif.',
            };
        } finally {
            clearTimeout(timer);
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
        const res = await this.fetch('/me');
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
    async addCartItem(id_variant, jumlah = 1) {
        const r = await this.fetch('/cart/items', { method: 'POST', body: { id_variant, jumlah } });
        this.jadwalLencana();
        return r;
    },
    async updateCartItem(id, jumlah) {
        const r = await this.fetch(`/cart/items/${id}`, { method: 'PUT', body: { jumlah } });
        this.jadwalLencana();
        return r;
    },
    async removeCartItem(id) {
        const r = await this.fetch(`/cart/items/${id}`, { method: 'DELETE' });
        this.jadwalLencana();
        return r;
    },
    async clearCart() {
        const r = await this.fetch('/cart', { method: 'DELETE' });
        this.jadwalLencana();
        return r;
    },

    /* ---------- Wishlist ---------- */
    wishlist() { return this.fetch('/wishlist'); },
    async addWishlist(id_product) {
        const r = await this.fetch('/wishlist', { method: 'POST', body: { id_product } });
        this.jadwalLencana();
        return r;
    },
    async removeWishlist(id) {
        const r = await this.fetch(`/wishlist/${id}`, { method: 'DELETE' });
        this.jadwalLencana();
        return r;
    },

    /* ---------- Alamat ---------- */
    addresses() { return this.fetch('/addresses'); },
    address(id) { return this.fetch(`/addresses/${id}`); },
    addAddress(payload) { return this.fetch('/addresses', { method: 'POST', body: payload }); },
    updateAddress(id, payload) { return this.fetch(`/addresses/${id}`, { method: 'PUT', body: payload }); },
    deleteAddress(id) { return this.fetch(`/addresses/${id}`, { method: 'DELETE' }); },

    /* ---------- Profil & Kata Sandi ---------- */
    /** PUT /me — field terima backend: nama_pengguna, email, nomor_telepon (parsial). */
    updateMe(payload) { return this.fetch('/me', { method: 'PUT', body: payload }); },
    /** PUT /me/password — wajib: kata_sandi_saat_ini, kata_sandi, kata_sandi_confirmation. */
    updatePassword(kata_sandi_saat_ini, kata_sandi, kata_sandi_confirmation) {
        return this.fetch('/me/password', {
            method: 'PUT',
            body: { kata_sandi_saat_ini, kata_sandi, kata_sandi_confirmation },
        });
    },

    /* ---------- Pengiriman (RajaOngkir) ---------- */
    /** GET /shipping/destinations?search= — daftar tujuan (butuh min. 3 huruf). */
    shippingDestinations(search) {
        return this.fetch(`/shipping/destinations?search=${encodeURIComponent(search ?? '')}`);
    },
    /**
     * POST /shipping/cost — { destination (int id hasil search), weight (gram), courier }.
     * STATUS (20-Oct-2026, probe CI): resolusi tujuan sudah BERFUNGSI ketika `destination`
     * = id subdistrict dari /shipping/destinations. Kurir ✅ jne, sicepat, lion;
     * ❌ tiki/pos/jnt/anteraja/ninja → 422 RAJAONGKIR_DESTINATION_NOT_FOUND,
     * jxpress → RAJAONGKIR_INVALID_REQUEST. Kadang 502 RAJAONGKIR_INVALID_RESPONSE
     * (upstream Komerce flaky) — checkout punya fallback tarif manual.
     */
    shippingCost(destination, weight, courier) {
        return this.fetch('/shipping/cost', {
            method: 'POST',
            body: { destination: Number(destination), weight: Number(weight), courier },
        });
    },

    /* ---------- Riwayat Voucher Pengguna ---------- */
    /** GET /voucher-usages/me — riwayat pakai voucher milik user login. */
    voucherUsagesMe() { return this.fetch('/voucher-usages/me'); },

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

    /**
     * Tambah produk ke keranjang langsung dari kartu (katalog/landing).
     * Karena daftar produk TIDAK menyertakan variants, kita ambil varian aktif
     * dulu, lalu pakai varian pertama yang masih ada stoknya.
     * Return: { ok, status?, login?, message? }
     */
    async keKeranjangProduk(id_product) {
        if (!this.getToken()) return { ok: false, status: 401, login: true };
        const res = await this.productVariants(id_product);
        const v = (res.data ?? []).filter(x => x.status_varian !== 'nonaktif');
        const pick = v.find(x => Number(x.stok) > 0) ?? v[0];
        if (!pick) return { ok: false, message: 'Produk ini belum memiliki varian aktif. Buka detail produk untuk memilih.' };
        return this.addCartItem(pick.id_variant, 1);
    },

    /**
     * Toast mini di layar bawah (tanpa Alpine) untuk umpan balik cepat
     * di halaman mana pun: PandansariAPI.toast('Ditambahkan!', true).
     */
    toast(message, ok = true) {
        const el = document.createElement('div');
        el.textContent = message;
        el.style.cssText =
            'position:fixed;left:50%;transform:translateX(-50%);bottom:24px;z-index:9999;' +
            'padding:10px 18px;border-radius:12px;font-size:12px;font-weight:700;' +
            "font-family:'Plus Jakarta Sans',sans-serif;box-shadow:0 8px 24px rgba(15,26,36,.18);" +
            (ok ? 'background:#059669;color:#fff;' : 'background:#e11d48;color:#fff;');
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 3000);
    },

    /* ---------- Admin (role: admin) ---------- */
    /**
     * Semua endpoint admin dari list-api.md §4. Dipanggil: PandansariAPI.admin.orders().
     * Catatan kontrak (hasil probe 20-Okt-2026):
     *   - /users TIDAK punya PUT/DELETE (hanya GET list/detail + POST buat).
     *   - /admin/categories TIDAK punya GET (daftar pakai /categories publik).
     *   - Paginasi /admin/*, /vouchers, /voucher-usages → { data:[], meta:{current_page,per_page,total,last_page} }
     *   - Khusus GET /users → paginator lengkap DI DALAM data (sudah dinormalkan di bawah).
     *   - Enum: status_pesanan, status_pengiriman, status_fulfillment ('dikemas'|'selesai'), peran ('pelanggan'|'admin').
     */
    admin: {
        _qs(params = {}) {
            const q = new URLSearchParams();
            for (const [k, v] of Object.entries(params)) if (v !== undefined && v !== null && v !== '') q.set(k, v);
            const qs = q.toString();
            return qs ? '?' + qs : '';
        },

        /* -- 4.1 Pengguna -- */
        users(params = {}) {
            // GET /users membalikkan paginator lengkap di data → normalkan ke { data:[], meta }
            return PandansariAPI.fetch('/users' + this._qs(params)).then(res => {
                if (res.ok && res.data && Array.isArray(res.data.data)) {
                    const p = res.data;
                    res.meta = { current_page: p.current_page, per_page: p.per_page, total: p.total, last_page: p.last_page };
                    res.data = p.data;
                }
                return res;
            });
        },
        user(id) { return PandansariAPI.fetch(`/users/${id}`); },
        /** POST /users — wajib: nama_pengguna, email, kata_sandi (+kata_sandi_confirmation), peran. */
        createUser(payload) { return PandansariAPI.fetch('/users', { method: 'POST', body: payload }); },
        usersSummary() { return PandansariAPI.fetch('/admin/users/summary'); },
        usersExport() { return PandansariAPI.downloadCsv('/users/export', 'pengguna.csv'); },

        /* -- 4.2 Produk, varian & gambar -- */
        /** GET /admin/products — query: search, status, category, page, per_page. */
        adminProducts(params = {}) { return PandansariAPI.fetch('/admin/products' + this._qs(params)); },
        adminProduct(id) { return PandansariAPI.fetch(`/admin/products/${id}`); },
        createProduct(payload) { return PandansariAPI.fetch('/products', { method: 'POST', body: payload }); },
        updateProduct(id, payload) { return PandansariAPI.fetch(`/products/${id}`, { method: 'PUT', body: payload }); },
        deleteProduct(id) { return PandansariAPI.fetch(`/products/${id}`, { method: 'DELETE' }); },
        productsExport() { return PandansariAPI.downloadCsv('/admin/products/export', 'produk.csv'); },
        createVariant(id_product, payload) { return PandansariAPI.fetch(`/products/${id_product}/variants`, { method: 'POST', body: payload }); },
        updateVariant(id, payload) { return PandansariAPI.fetch(`/variants/${id}`, { method: 'PUT', body: payload }); },
        deleteVariant(id) { return PandansariAPI.fetch(`/variants/${id}`, { method: 'DELETE' }); },
        /** Upload gambar: payload = FormData { gambar, alt_text?, is_primary?, urutan? }. */
        addProductImage(id_product, formData) { return PandansariAPI.fetch(`/products/${id_product}/images`, { method: 'POST', body: formData }); },
        deleteImage(id) { return PandansariAPI.fetch(`/images/${id}`, { method: 'DELETE' }); },

        /* -- 4.4 Kategori (daftar: PandansariAPI.categories() publik) -- */
        createCategory(payload) { return PandansariAPI.fetch('/admin/categories', { method: 'POST', body: payload }); },
        updateCategory(id, payload) { return PandansariAPI.fetch(`/admin/categories/${id}`, { method: 'PUT', body: payload }); },
        deleteCategory(id) { return PandansariAPI.fetch(`/admin/categories/${id}`, { method: 'DELETE' }); },

        /* -- 4.3 Voucher -- */
        vouchers(params = {}) { return PandansariAPI.fetch('/vouchers' + this._qs(params)); },
        voucher(id) { return PandansariAPI.fetch(`/vouchers/${id}`); },
        createVoucher(payload) { return PandansariAPI.fetch('/vouchers', { method: 'POST', body: payload }); },
        updateVoucher(id, payload) { return PandansariAPI.fetch(`/vouchers/${id}`, { method: 'PUT', body: payload }); },
        deleteVoucher(id) { return PandansariAPI.fetch(`/vouchers/${id}`, { method: 'DELETE' }); },
        publishVoucher(id) { return PandansariAPI.fetch(`/vouchers/${id}/publish`, { method: 'POST', body: {} }); },
        unpublishVoucher(id) { return PandansariAPI.fetch(`/vouchers/${id}/unpublish`, { method: 'POST', body: {} }); },
        vouchersSummary() { return PandansariAPI.fetch('/vouchers/summary'); },
        voucherUsages(params = {}) { return PandansariAPI.fetch('/voucher-usages' + this._qs(params)); },

        /* -- 4.5 Pesanan -- */
        /** GET /admin/orders — query: status, search, dari, sampai, page, per_page. */
        orders(params = {}) { return PandansariAPI.fetch('/admin/orders' + this._qs(params)); },
        order(id) { return PandansariAPI.fetch(`/admin/orders/${id}`); },
        ordersSummary() { return PandansariAPI.fetch('/admin/orders/summary'); },
        /** PUT /admin/orders/{id}/status — { status_pesanan, catatan? }. */
        updateOrderStatus(id, status_pesanan, catatan = null) {
            return PandansariAPI.fetch(`/admin/orders/${id}/status`, {
                method: 'PUT', body: { status_pesanan, ...(catatan ? { catatan } : {}) },
            });
        },
        /** POST /admin/orders/{id}/fulfillments — { status_fulfillment: 'dikemas'|'selesai', ... }. */
        addFulfillment(id, status_fulfillment, extra = {}) {
            return PandansariAPI.fetch(`/admin/orders/${id}/fulfillments`, {
                method: 'POST', body: { status_fulfillment, ...extra },
            });
        },
        orderFulfillments(params = {}) { return PandansariAPI.fetch('/admin/order-fulfillments' + this._qs(params)); },

        /* -- 4.6 Pembayaran -- */
        payments(params = {}) { return PandansariAPI.fetch('/admin/payments' + this._qs(params)); },
        paymentsSummary() { return PandansariAPI.fetch('/admin/payments/summary'); },

        /* -- 4.7 Pengiriman -- */
        shipments(params = {}) { return PandansariAPI.fetch('/admin/shipments' + this._qs(params)); },
        /** PUT /admin/shipments/{id}/status — { status_pengiriman, ... }. */
        updateShipmentStatus(id, status_pengiriman, extra = {}) {
            return PandansariAPI.fetch(`/admin/shipments/${id}/status`, {
                method: 'PUT', body: { status_pengiriman, ...extra },
            });
        },

        /* -- 4.8 Konfigurasi RajaOngkir -- */
        shippingConfig() { return PandansariAPI.fetch('/admin/shipping/config'); },
        shippingHealth() { return PandansariAPI.fetch('/admin/shipping/health'); },
        shippingCouriers() { return PandansariAPI.fetch('/admin/shipping/couriers'); },
        /** PUT /admin/shipping/couriers/{code} — { aktif: bool }. */
        setCourier(code, aktif) { return PandansariAPI.fetch(`/admin/shipping/couriers/${code}`, { method: 'PUT', body: { aktif: !!aktif } }); },
        /** POST /admin/shipping/simulate — { destination, weight, courier? }. Memakai kuota API! */
        simulateShipping(destination, weight, courier = null) {
            return PandansariAPI.fetch('/admin/shipping/simulate', {
                method: 'POST', body: { destination: Number(destination), weight: Number(weight), ...(courier ? { courier } : {}) },
            });
        },
    },

    /**
     * Unduh file CSV yang butuh header Authorization (export admin).
     * Dipanggil: PandansariAPI.downloadCsv('/users/export', 'pengguna.csv').
     */
    async downloadCsv(path, filename = 'export.csv') {
        const headers = { 'Accept': 'text/csv', 'ngrok-skip-browser-warning': 'true' };
        const token = this.getToken();
        if (token) headers.Authorization = `Bearer ${token}`;
        try {
            const res = await fetch(buildUrl(path), { headers });
            if (!res.ok) return { ok: false, status: res.status, message: `Gagal mengunduh (${res.status}).` };
            const url = URL.createObjectURL(await res.blob());
            const a = document.createElement('a');
            a.href = url; a.download = filename;
            document.body.appendChild(a); a.click(); a.remove();
            setTimeout(() => URL.revokeObjectURL(url), 2000);
            return { ok: true };
        } catch {
            return { ok: false, status: 0, message: 'Tidak dapat menghubungi server.' };
        }
    },

    /* ---------- Helper format ---------- */
    formatRp(value) {
        const n = Number(value ?? 0);
        return 'Rp ' + n.toLocaleString('id-ID', { maximumFractionDigits: 0 });
    },

    /**
     * URL lengkap gambar dari field backend `path_gambar` (relatif, mis. "products/x.jpg").
     * Backend menyimpan file di disk public → disajikan di {origin}/storage/{path}.
     * Jika sudah absolut (http...), dikembalikan apa adanya.
     */
    imgUrl(path) {
        const p = String(path ?? '').trim();
        if (!p) return '';
        if (/^https?:\/\//i.test(p)) return p;
        const origin = API_URL.replace(/\/api\/?$/i, ''); // buang suffix /api
        return origin + '/storage/' + p.replace(/^\/+/, '');
    },

    /**
     * Ambil URL gambar utama dari daftar `images` produk.
     * Utama = is_primary true, fallback elemen pertama. Kosong '' jika belum ada gambar.
     */
    coverUrl(images) {
        const list = Array.isArray(images) ? images : [];
        const img = list.find(x => x && x.is_primary) ?? list[0];
        return this.imgUrl(img?.path_gambar ?? img?.url ?? '');
    },
};

window.PandansariAPI._init();

// Store global untuk badge navbar (Keranjang & Wishlist) — dibaca via $store.lencana.
document.addEventListener('alpine:init', () => {
    if (window.Alpine && typeof window.Alpine.store === 'function') {
        window.Alpine.store('lencana', { cart: 0, wishlist: 0 });
    }
});
