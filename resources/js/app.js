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
 *   POST /api/auth/google { token | access_token | id_token } → login/daftar via Google (sesi Sanctum)
 *   POST /api/me/google   { token | access_token | id_token } → tautkan akun Google (auth:sanctum)
 *   GET  /api/voucher-usages/me
 *   Seluruh endpoint admin → lihat sub-objek `PandansariAPI.admin` (di bawah).
 *   Seluruh endpoint teknisi → lihat sub-objek `PandansariAPI.teknisi` (dashboard,
 *     health, audit-logs, webhook-logs, notification-logs, failed-jobs + detail).
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
    _init() {
        this._initImageCache();
        console.debug('[PandansariAPI] aktif →', API_URL);
    },

    /* ---------- Token & Profil ---------- */
    getToken() { return localStorage.getItem(TOKEN_KEY); },
    setToken(token) { localStorage.setItem(TOKEN_KEY, token); },
    setUser(user) { localStorage.setItem(USER_KEY, JSON.stringify(user ?? null)); },
    getUser() {
        try { return JSON.parse(localStorage.getItem(USER_KEY)); } catch { return null; }
    },
    /**
     * Hapus token + profil user sekaligus (lencana di-nol-kan).
     * Cookie panel juga dibersihkan best-effort lewat sendBeacon agar tetap
     * terkirim saat halaman langsung dialihkan ke /login.
     */
    clearSession(beaconPanel = true) {
        if (beaconPanel) this.hapusPanel(true);
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
                // 429 rate-limit kirim ulang OTP: detik sisa tunggu dari backend
                retry_after: payload?.retry_after ?? null,
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
    /**
     * Registrasi 2 langkah (kontrak rev. diterima).
     * POST /register — kirim data diri; backend mengirim OTP ke email.
     * POST /otp/verify-registration — { email, otp } → token (Sanctum).
     * POST /otp/resend-registration — kirim ulang OTP registrasi { email }.
     */
    register(payload) { return this.fetch('/register', { method: 'POST', body: payload }); },
    registerSendOtp(payload) { return this.fetch('/register', { method: 'POST', body: payload }); },
    registerVerifyOtp(email, otp) {
        return this.fetch('/otp/verify-registration', { method: 'POST', body: { email, otp } });
    },
    registerResendOtp(email) { return this.fetch('/otp/resend-registration', { method: 'POST', body: { email } }); },
    /** Alias sesuai spesifikasi: POST /otp/verify-registration → { email, otp } → token. */
    verifyRegisterOtp(email, otp) { return this.registerVerifyOtp(email, otp); },

    /* ---------- Lupa Password: 3 langkah (OTP email, kontrak rev. diterima) ----------
     * 1. POST /forgot-password             { email }                                        → OTP ke email
     * 2. POST /forgot-password/verify-otp  { email, otp }                                   → OTP valid
     * 3. POST /forgot-password/reset       { email, otp, kata_sandi, kata_sandi_confirmation }
     * 4. POST /forgot-password/resend-otp  { email }                                        → kirim ulang OTP
     */
    forgotSendOtp(email) { return this.fetch('/forgot-password', { method: 'POST', body: { email } }); },
    forgotVerifyOtp(email, otp) { return this.fetch('/forgot-password/verify-otp', { method: 'POST', body: { email, otp } }); },
    resetPassword(email, otp, kata_sandi, kata_sandi_confirmation, token = null) {
        return this.fetch('/forgot-password/reset', { method: 'POST', body: { email, otp, kata_sandi, kata_sandi_confirmation, ...(token ? { token } : {}) } });
    },
    forgotResendOtp(email) { return this.fetch('/forgot-password/resend-otp', { method: 'POST', body: { email } }); },
    /** Alias sesuai spesifikasi: POST /forgot-password/reset → { email, otp, kata_sandi, kata_sandi_confirmation, token? }. */
    forgotResetPassword(payload) {
        return this.fetch('/forgot-password/reset', { method: 'POST', body: payload });
    },
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
    /** Alias sesuai spesifikasi: GET /api/me. */
    getProfile() { return this.me(); },

    /* ---------- Peran pengguna & redirect panel ---------- */
    /**
     * Kumpulan nilai peran mentah dari objek user → array string huruf kecil.
     * Mendukung `peran` (backend), `role`, `roles[]`, serta objek peran ({slug|nama|name}).
     */
    _peranMentah(user = null) {
        const u = user ?? this.getUser() ?? {};
        return [u.peran, u.role, u.roles, u.peran_akses, u.hak_akses]
            .flatMap((v) => Array.isArray(v) ? v : (v == null ? [] : [v]))
            .map((v) => String(
                v && typeof v === 'object'
                    ? (v.slug ?? v.nama ?? v.name ?? '')
                    : v
            ).trim().toLowerCase());
    },
    /** Peran efektif: 'admin' | 'teknisi' | 'pelanggan'. */
    peranUser(user = null) {
        const peran = this._peranMentah(user);
        if (peran.some((p) => ['admin', 'administrator', 'super_admin', 'superadmin', 'owner'].includes(p))) return 'admin';
        if (peran.some((p) => ['teknisi', 'technician', 'engineer', 'engineering'].includes(p))) return 'teknisi';
        return 'pelanggan';
    },
    /** Halaman awal sesuai peran — dipakai setelah login berhasil. */
    berandaPeran(user = null) {
        const peran = this.peranUser(user);
        if (peran === 'admin') return '/admin/dashboard';
        if (peran === 'teknisi') return '/teknisi';
        return '/';
    },
    /** Arahkan pengguna ke panel sesuai peran (admin/teknisi) atau beranda customer. */
    arahkanKePanel(user = null) {
        window.location.href = this.berandaPeran(user);
    },

    /* ---------- Sesi panel (cookie HttpOnly, diverifikasi backend) ---------- */
    /** Token CSRF dari <meta name="csrf-token"> di layout. */
    csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    },

    /**
     * Hapus cookie panel di server.
     * `beacon` dipakai saat logout/navigasi agar request tetap terkirim.
     */
    hapusPanel(beacon = false) {
        const csrf = this.csrfToken();
        if (!csrf) return Promise.resolve();

        if (beacon && typeof navigator.sendBeacon === 'function') {
            const data = new FormData();
            data.append('_token', csrf);
            navigator.sendBeacon('/session/logout', data);
            return Promise.resolve();
        }

        return fetch('/session/logout', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                'X-CSRF-TOKEN': csrf,
                'ngrok-skip-browser-warning': 'true',
            },
            body: new URLSearchParams({ _token: csrf }),
            credentials: 'same-origin',
        }).catch(() => {});
    },

    /**
     * Verifikasi token login ke backend lewat POST /session/panel.
     * Backend mengembalikan peran; server lalu memasang cookie panel HttpOnly.
     */
    async sinkronPanel() {
        const token = this.getToken();
        if (!token) {
            await this.hapusPanel();
            return { ok: false, status: 0, role: 'pelanggan', message: '' };
        }

        const csrf = this.csrfToken();
        if (!csrf) {
            return {
                ok: false,
                status: 0,
                role: 'pelanggan',
                message: 'Token CSRF tidak tersedia. Muat ulang halaman lalu coba lagi.',
            };
        }

        try {
            const res = await fetch('/session/panel', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'ngrok-skip-browser-warning': 'true',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ token }),
            });
            const payload = await res.json().catch(() => ({}));

            return {
                ok: res.ok && payload.success === true,
                status: res.status,
                role: payload.role ?? 'pelanggan',
                message: payload.message ?? '',
            };
        } catch {
            return {
                ok: false,
                status: 0,
                role: 'pelanggan',
                message: 'Tidak dapat menghubungi server. Periksa koneksi lalu coba lagi.',
            };
        }
    },

    /** Logout panel: hapus cookie HttpOnly + sesi lokal, lalu kembali ke login. */
    async keluarPanel() {
        await this.hapusPanel();
        this.clearSession(false);
        window.location.href = '/login';
    },

    /* ---------- Google OAuth (Google Identity Services) ---------- */
    /** Client ID Google dari .env → VITE_GOOGLE_CLIENT_ID (kosong = fitur belum dikonfigurasi). */
    googleClientId() {
        return String(import.meta.env?.VITE_GOOGLE_CLIENT_ID ?? '').trim();
    },
    /** Pra-muat SDK Google (GSI) lebih awal — dipanggil saat halaman auth dibuka. */
    siapkanGoogleScript() { return this._pastikanGsiScript(); },
    /** Muat script GSI (https://accounts.google.com/gsi/client) sekali → resolve window.google. */
    _pastikanGsiScript() {
        if (window.google?.accounts) return Promise.resolve(window.google);
        return new Promise((resolve, reject) => {
            const s = document.createElement('script');
            s.src = 'https://accounts.google.com/gsi/client';
            s.async = true;
            s.defer = true;
            s.onload = () => (window.google?.accounts ? resolve(window.google) : reject(new Error('Google Identity Services tidak termuat')));
            s.onerror = () => reject(new Error('Script Google Identity Services gagal dimuat'));
            document.head.appendChild(s);
        });
    },
    /**
     * Minta token akses Google via popup OAuth (Token Client — google.accounts.oauth2).
     * Return: Promise<string accessToken>. Reject dengan kode error yang jelas:
     *   GOOGLE_CLIENT_ID_BELUM_DISET  — VITE_GOOGLE_CLIENT_ID kosong
     *   GOOGLE_SDK_TIDAK_SIAP         — script/objek window.google tidak tersedia
     *   GOOGLE_POPUP_DITUTUP          — pengguna menutup popup tanpa memilih akun
     *   GOOGLE_POPUP_TIDAK_TAMPIL     — popup gagal dibuka (diblokir browser)
     *   GOOGLE_TOKEN_KOSONG           — callback dipanggil tanpa access_token
     */
    async mintaGoogleToken() {
        const clientId = this.googleClientId();
        if (!clientId) throw new Error('GOOGLE_CLIENT_ID_BELUM_DISET');
        const gsi = await this._pastikanGsiScript();
        // Cek eksplisit ketersediaan SDK Google di window sebelum memakai API-nya
        if (!gsi || !window.google?.accounts?.oauth2) throw new Error('GOOGLE_SDK_TIDAK_SIAP');
        return new Promise((resolve, reject) => {
            const client = gsi.accounts.oauth2.initTokenClient({
                client_id: clientId,
                scope: 'openid email profile',
                // Sukses: popup selesai, token akses diterima
                callback: (resp) => {
                    if (resp?.access_token) resolve(resp.access_token);
                    else reject(new Error('GOOGLE_TOKEN_KOSONG'));
                },
                // Gagal: pengguna menutup popup / popup gagal dibuka
                error_callback: (err) => {
                    if (err?.type === 'popup_failed_to_start') reject(new Error('GOOGLE_POPUP_TIDAK_TAMPIL'));
                    else reject(new Error('GOOGLE_POPUP_DITUTUP'));
                },
            });
            client.requestAccessToken();          // memicu popup pilihan akun Google
        });
    },
    /** Alias kompatibilitas — kini mengembalikan access token dari Token Client. */
    mintaGoogleCredential() { return this.mintaGoogleToken(); },
    /**
     * POST /auth/google — login/daftar via Google.
     * Kontrak payload: ID Token (JWT) dari `google.accounts.id` — `response.credential`.
     * Backend memverifikasi ID Token (bukan access token OAuth).
     * Sukses → { data: { token, user? } } → sesi disimpan otomatis (setToken + setUser + me()).
     */
    async loginGoogle(idToken) {
        const res = await this.fetch('/auth/google', {
            method: 'POST',
            // `id_token` = kunci utama; `credential` & `token` dikirim sebagai alias kompatibilitas.
            body: { id_token: idToken, credential: idToken, token: idToken },
        });
        if (res.ok) {
            const sesi = res.data?.token ?? res.data?.data?.token ?? res.data?.access_token;
            if (sesi) {
                this.setToken(sesi);
                this.setUser(res.data?.user ?? null);
                // Refresh profil TIDAK memblokir tombol: bila /me lambat (tunnel ngrok),
                // tombol tetap lepas dari 'MENGHUBUNGKAN…' maks. 8 detik setelah sesi tersimpan.
                try {
                    await Promise.race([this.me(), new Promise((r) => setTimeout(r, 8000))]);
                } catch { /* profil tetap tersedia dari response /auth/google */ }
            }
        }
        return res;
    },
    /** Alias kompatibilitas — nama baru: loginGoogle(token). */
    authGoogle(token) { return this.loginGoogle(token); },
    /**
     * POST /me/google — tautkan akun Google ke user yang sedang login (butuh Bearer token).
     * body: token akses popup Google (alias kunci sama seperti /auth/google).
     * Sukses → data profil user terbaru (kolom google_id terisi).
     */
    linkGoogle(token) {
        return this.fetch('/me/google', {
            method: 'POST',
            body: { token, access_token: token, id_token: token },
        });
    },

    /* ---------- Kategori & Produk (publik) ---------- */
    /** GET /cms/homepage — konten homepage (hero, promo, banner). */
    getHomepageCms() { return this.fetch('/cms/homepage'); },
    /** GET /cms/hero — slide banner hero aktif (publik), urut urutan_tampilan. */
    cmsHeroes() { return this.fetch('/cms/hero'); },
    /** GET /cms/kategori — kartu kategori beranda CMS aktif (publik), urut urutan_tampilan. */
    cmsKategori() { return this.fetch('/cms/kategori'); },

    /** GET /cms/galeri — info galeri + daftar foto (publik); data: null bila galeri tidak ada / nonaktif. */
    cmsGaleriPublik() { return this.fetch('/cms/galeri'); },

    /** GET /cms/profil-usaha — identitas, alamat, jam_operasional, Google Maps (publik); data: null bila belum diisi. */
    cmsProfilUsahaPublik() { return this.fetch('/cms/profil-usaha'); },

    /** GET /cms/fasilitas — daftar fasilitas aktif (publik). */
    cmsFasilitasPublik() { return this.fetch('/cms/fasilitas'); },

    /** GET /cms/promo — banner promo beranda (publik): voucher unggulan / konfigurasi cms_promo; data: null bila kosong. */
    cmsPromoPublik() { return this.fetch('/cms/promo'); },
    cmsPromosPublik() { return this.fetch('/cms/promos'); },
    categories() { return this.fetch('/categories'); },
    category(id) { return this.fetch(`/categories/${id}`); },
    async products(params = {}) {
        const q = new URLSearchParams();
        for (const [k, v] of Object.entries(params)) if (v !== undefined && v !== null && v !== '') q.set(k, v);
        const qs = q.toString();
        const res = await this.fetch(`/products${qs ? '?' + qs : ''}`);
        if (res.ok && Array.isArray(res.data)) {
            this.cacheProductImages(res.data);
        }
        return res;
    },
    async product(id) {
        const res = await this.fetch(`/products/${id}`);
        if (res.ok && res.data) {
            this.cacheProductImages([res.data]);
        }
        return res;
    },
    async productVariants(productId) {
        const res = await this.fetch(`/products/${productId}/variants`);
        if (res.ok && Array.isArray(res.data)) {
            this.cacheVariantsForProduct(productId, res.data);
        }
        return res;
    },
    async productImages(productId) {
        const res = await this.fetch(`/products/${productId}/images`);
        if (res.ok && Array.isArray(res.data)) {
            const cover = this.coverUrl(res.data);
            if (cover) this.cacheImageForProduct(productId, cover);
        }
        return res;
    },

    /* ---------- Keranjang ---------- */
    cart() { return this.fetch('/cart'); },
    /** Alias sesuai spesifikasi: GET /api/cart. */
    getCart() { return this.cart(); },
    async addCartItem(id_variant, jumlah = 1) {
        const r = await this.fetch('/cart/items', { method: 'POST', body: { id_variant, jumlah } });
        this.jadwalLencana();
        return r;
    },
    /** Alias sesuai spesifikasi: POST /api/cart/items → { id_variant, jumlah }. */
    addToCart(id_variant, jumlah = 1) { return this.addCartItem(id_variant, jumlah); },
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
    /** Alias sesuai spesifikasi: GET /api/wishlist. */
    getWishlist() { return this.wishlist(); },
    async addWishlist(id_product) {
        const r = await this.fetch('/wishlist', { method: 'POST', body: { id_product } });
        this.jadwalLencana();
        return r;
    },
    /** Alias sesuai spesifikasi: POST /api/wishlist → { id_product }. */
    addToWishlist(id_product) { return this.addWishlist(id_product); },
    async removeWishlist(id) {
        const r = await this.fetch(`/wishlist/${id}`, { method: 'DELETE' });
        this.jadwalLencana();
        return r;
    },

    /* ---------- Alamat ---------- */
    addresses() { return this.fetch('/addresses'); },
    /** Alias sesuai spesifikasi: GET /api/addresses. */
    getAddresses() { return this.addresses(); },
    address(id) { return this.fetch(`/addresses/${id}`); },
    addAddress(payload) { return this.fetch('/addresses', { method: 'POST', body: payload }); },
    updateAddress(id, payload) { return this.fetch(`/addresses/${id}`, { method: 'PUT', body: payload }); },
    deleteAddress(id) { return this.fetch(`/addresses/${id}`, { method: 'DELETE' }); },

    /* ---------- Profil & Kata Sandi ---------- */
    /** PUT /me — field terima backend: nama_pengguna, email, nomor_telepon (parsial). */
    updateMe(payload) { return this.fetch('/me', { method: 'PUT', body: payload }); },
    /** Alias sesuai spesifikasi: PUT /me → { nama_pengguna?, email?, nomor_telepon? }. */
    updateProfile(payload) { return this.updateMe(payload); },
    /** PUT /me/password — wajib: kata_sandi_saat_ini, kata_sandi, kata_sandi_confirmation. */
    updatePassword(kata_sandi_saat_ini, kata_sandi, kata_sandi_confirmation) {
        return this.fetch('/me/password', {
            method: 'PUT',
            body: { kata_sandi_saat_ini, kata_sandi, kata_sandi_confirmation },
        });
    },
    /** Alias sesuai spesifikasi: PUT /me/password → menerima payload object { kata_sandi_saat_ini, kata_sandi, kata_sandi_confirmation }. */
    updatePasswordPayload(payload) {
        return this.updatePassword(payload.kata_sandi_saat_ini, payload.kata_sandi, payload.kata_sandi_confirmation);
    },
    /** PUT /me/password — ganti password sesuai spesifikasi (payload object). */
    changePassword(payload) { return this.updatePasswordPayload(payload); },

    /* ---------- Pengiriman (RajaOngkir) ---------- */
    /** GET /shipping/destinations?search= — daftar tujuan (butuh min. 3 huruf). */
    shippingDestinations(search) {
        return this.fetch(`/shipping/destinations?search=${encodeURIComponent(search ?? '')}`);
    },
    /** Alias sesuai spesifikasi: GET /api/shipping/destinations?search=. */
    getDestinations(search) { return this.shippingDestinations(search); },
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
    /**
     * POST /rajaongkir/check-all — { destination, weight } → array layanan ongkir semua kurir.
     * Digunakan checkout untuk pengecekan ongkir sekali panggil (tidak per-kurir).
     */
    rajaongkirCheckAll(destination, weight) {
        return this.fetch('/rajaongkir/check-all', {
            method: 'POST',
            body: { destination: Number(destination), weight: Number(weight) },
        });
    },
    /** Alias sesuai spesifikasi: POST /api/rajaongkir/check-all. */
    checkAllOngkir(destination, weight) { return this.rajaongkirCheckAll(destination, weight); },
    /** Alias sesuai spesifikasi: POST /api/shipping/cost. */
    checkSingleOngkir(destination, weight, courier) { return this.shippingCost(destination, weight, courier); },
    /**
     * Hitung ongkir: coba POST /rajaongkir/check-all dulu,
     * kalau gagal (404/405) fallback ke POST /shipping/cost (kurir default: jne).
     */
    async checkOngkir(destination, weight) {
        try {
            const res = await this.rajaongkirCheckAll(Number(destination), Number(weight));
            if (res.ok) return res;
        } catch (_) {
            // ignore errors here, will fallback
        }
        // Fallback ke shipping/cost dengan kurir jne
        return this.shippingCost(Number(destination), Number(weight), 'jne');
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
    /** Alias sesuai spesifikasi: POST /vouchers/validate → hanya kode voucher (subtotal diabaikan bila tidak dikirim). */
    validateVoucherCode(code) {
        return this.validateVoucher(code, undefined);
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
    /** GET /orders/{id}/invoice — detail invoice (PDF/meta) untuk pesanan. */
    getInvoice(id) { return this.fetch(`/orders/${id}/invoice`); },
    payOrder(id, payload = {}) {
        return this.fetch(`/orders/${id}/payment`, { method: 'POST', body: payload });
    },
    lastPayment(id) { return this.fetch(`/orders/${id}/payment`); },

    /* ---------- Midtrans Snap (popup) ---------- */
    /** URL snap.js — boleh dioverride via VITE_SNAP_URL di .env (default: sandbox). */
    _snapUrl() {
        return String(import.meta.env?.VITE_SNAP_URL ?? 'https://app.sandbox.midtrans.com/snap/snap.js').trim();
    },
    /**
     * Muat script snap.js sekali (dengan data-client-key dari response backend).
     * Resolve window.snap bila siap; throw bila gagal → pemanggil memakai fallback redirect_url.
     */
    _pastikanSnapScript(clientKey) {
        if (!clientKey) throw new Error('client_key tidak ada');
        const idScript = 'midtrans-snap-script';
        const existing = document.getElementById(idScript);
        if (existing && existing.dataset.clientKey === clientKey) {
            if (window.snap?.pay) return window.snap;
            throw new Error('Snap belum siap');
        }
        if (existing) existing.remove(); // client_key berubah → ganti script
        return new Promise((resolve, reject) => {
            const s = document.createElement('script');
            s.id = idScript;
            s.src = this._snapUrl();
            s.dataset.clientKey = clientKey;
            s.onload = () => { if (window.snap?.pay) resolve(window.snap); else reject(new Error('Snap tidak termuat')); };
            s.onerror = () => reject(new Error('Script Midtrans Snap gagal dimuat'));
            document.head.appendChild(s);
        });
    },
    /**
     * Buka popup Midtrans Snap utk satu transaksi pembayaran.
     * Callback (opsional): onSuccess, onPending, onError, onClose.
     * Jika snap_token/client_key tidak ada ATAU script gagal termuat →
     * fallback ke redirect_url (alur lama) agar tombol bayar tetap bekerja.
     * Return: 'snap' (popup dibuka) | 'redirect' (dialihkan).
     */
    async bayarSnap(payment, { onSuccess, onPending, onError, onClose } = {}) {
        const url = payment?.redirect_url ?? payment?.payment_url;
        const token = payment?.snap_token ?? payment?.token;
        const key = payment?.client_key;
        try {
            const snap = await this._pastikanSnapScript(key);
            snap.pay(token, {
                // 1) Pembayaran sukses → default redirect ke /orders (Pesanan Saya)
                onSuccess: (r) => { onSuccess ? onSuccess(r) : (window.location.href = '/orders'); },
                onPending: (r) => { onPending ? onPending(r) : (window.location.href = '/orders'); },
                onError: (r) => { onError ? onError(r) : this.toast(r?.message ?? 'Pembayaran gagal, silakan coba lagi.', false); },
                // 2) Popup ditutup user (tanpa selesai bayar) → pemanggil menentukan re-fetch status
                onClose: () => { onClose ? onClose() : null; },
            });
            return 'snap';
        } catch {
            if (url) { window.location.href = url; return 'redirect'; }
            throw new Error('Midtrans Snap tidak tersedia dan redirect_url kosong.');
        }
    },

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

    /* ---------- Teknisi / Engineering Console ---------- */
    /**
     * Endpoint modul teknisi (dipanggil: PandansariAPI.teknisi.auditLogs()).
     * Semua request tetap lewat PandansariAPI.fetch → otomatis membawa
     * header `ngrok-skip-browser-warning` + `Authorization: Bearer <token>`.
     *
     *   GET /teknisi/dashboard
     *   GET /teknisi/health
     *   GET /teknisi/audit-logs[?page=&per_page=&search=]
     *   GET /teknisi/audit-logs/{id}
     *   GET /teknisi/webhook-logs[?page=&per_page=&search=]
     *   GET /teknisi/webhook-logs/{id}
     *   GET /teknisi/notification-logs[?page=&per_page=&search=]
     *   GET /teknisi/notification-logs/{id}
     *   GET /teknisi/failed-jobs[?page=&per_page=&search=]
     *   GET /teknisi/failed-jobs/{id}
     */
    teknisi: {
        _TYPES: {
            audit: 'audit-logs',
            webhook: 'webhook-logs',
            notification: 'notification-logs',
            failed: 'failed-jobs',
        },
        _qs(params = {}) {
            const q = new URLSearchParams();
            for (const [k, v] of Object.entries(params)) if (v !== undefined && v !== null && v !== '') q.set(k, v);
            const qs = q.toString();
            return qs ? '?' + qs : '';
        },
        /** GET /teknisi/{dashboard|health}. */
        dashboard() { return PandansariAPI.fetch('/teknisi/dashboard'); },
        health() { return PandansariAPI.fetch('/teknisi/health'); },
        /** Daftar log generik: kind = audit | webhook | notification | failed. */
        list(kind, params = {}) {
            const path = this._TYPES[kind];
            if (!path) {
                return Promise.resolve({
                    ok: false, status: 0, data: null, meta: null, errors: {},
                    message: `Jenis log teknisi tidak dikenal: ${kind}.`,
                });
            }
            return PandansariAPI.fetch(`/teknisi/${path}${this._qs(params)}`);
        },
        /** Detail satu baris log/job (untuk JSON viewer di modal). */
        detail(kind, id) {
            const path = this._TYPES[kind];
            if (!path) {
                return Promise.resolve({
                    ok: false, status: 0, data: null, meta: null, errors: {},
                    message: `Jenis log teknisi tidak dikenal: ${kind}.`,
                });
            }
            return PandansariAPI.fetch(`/teknisi/${path}/${encodeURIComponent(id)}`);
        },
        auditLogs(params = {}) { return this.list('audit', params); },
        auditLog(id) { return this.detail('audit', id); },
        webhookLogs(params = {}) { return this.list('webhook', params); },
        webhookLog(id) { return this.detail('webhook', id); },
        notificationLogs(params = {}) { return this.list('notification', params); },
        notificationLog(id) { return this.detail('notification', id); },
        failedJobs(params = {}) { return this.list('failed', params); },
        failedJob(id) { return this.detail('failed', id); },
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
        /** Upload gambar produk/draft. Payload FormData: { gambar, type?, id_variant?, alt_text?, is_primary?, urutan? }. */
        addProductImage(id_product, formData) { return PandansariAPI.fetch(`/admin/products/${id_product}/images`, { method: 'POST', body: formData }); },
        /** GET /admin/products/{id}/images?type=produk|size_chart (produk draft/nonaktif ikut terlihat). */
        adminProductImages(id_product, type = null) {
            return PandansariAPI.fetch(`/admin/products/${id_product}/images${type ? `?type=${encodeURIComponent(type)}` : ''}`);
        },
        uploadAdminProductImage(id_product, formData) { return PandansariAPI.fetch(`/admin/products/${id_product}/images`, { method: 'POST', body: formData }); },
        deleteAdminProductImage(id_product, imageId) { return PandansariAPI.fetch(`/admin/products/${id_product}/images/${imageId}`, { method: 'DELETE' }); },
        deleteImage(id) { return PandansariAPI.fetch(`/images/${id}`, { method: 'DELETE' }); },
        /** Simpan banyak varian sekaligus. Body: { items: [...], hapus_yang_tidak_disediakan?: bool }. */
        bulkVariants(id_product, items, hapusYangTidakDisediakan = false) {
            return PandansariAPI.fetch(`/admin/products/${id_product}/variants/bulk`, {
                method: 'POST',
                body: { items, hapus_yang_tidak_disediakan: !!hapusYangTidakDisediakan },
            });
        },

        /* -- 4.4 Kategori (daftar: PandansariAPI.categories() publik) -- */
        createCategory(payload) { return PandansariAPI.fetch('/admin/categories', { method: 'POST', body: payload }); },
        updateCategory(id, payload) { return PandansariAPI.fetch(`/admin/categories/${id}`, { method: 'PUT', body: payload }); },
        deleteCategory(id) { return PandansariAPI.fetch(`/admin/categories/${id}`, { method: 'DELETE' }); },

        /* -- 19.8 Banner Hero CMS -- */
        /** GET /admin/cms/hero — semua slide (termasuk nonaktif). */
        heroes() { return PandansariAPI.fetch('/admin/cms/hero'); },
        /** POST /admin/cms/hero — multipart: judul, teks_tombol, tautan_tombol, gambar, urutan_tampilan?, status_aktif?. */
        createHero(formData) { return PandansariAPI.fetch('/admin/cms/hero', { method: 'POST', body: formData }); },
        /**
         * PUT /admin/cms/hero/{id}.
         * FormData (ganti gambar) dikirim via POST + `_method=PUT` karena PHP tidak
         * mem-parse body multipart pada method PUT. JSON dipakai untuk update teks saja.
         */
        updateHero(id, payload) {
            if (typeof FormData !== 'undefined' && payload instanceof FormData) {
                payload.set('_method', 'PUT');
                return PandansariAPI.fetch(`/admin/cms/hero/${id}`, { method: 'POST', body: payload });
            }
            return PandansariAPI.fetch(`/admin/cms/hero/${id}`, { method: 'PUT', body: payload });
        },
        /** DELETE /admin/cms/hero/{id} — hapus baris + berkas gambar. */
        deleteHero(id) { return PandansariAPI.fetch(`/admin/cms/hero/${id}`, { method: 'DELETE' }); },

        /* -- 19.9 Kategori CMS (kartu kategori beranda) -- */
        /** GET /admin/cms/kategori — semua kartu kategori CMS. */
        cmsKategori() { return PandansariAPI.fetch('/admin/cms/kategori'); },
        /** POST /admin/cms/kategori — multipart: nama_kategori (unik), judul, gambar; tautan_tujuan dihitung FE dari slug kategori. */
        createCmsKategori(formData) { return PandansariAPI.fetch('/admin/cms/kategori', { method: 'POST', body: formData }); },
        /** PUT /admin/cms/kategori/{id} — multipart via POST + `_method=PUT` (alasan sama dengan updateHero). */
        updateCmsKategori(id, payload) {
            if (typeof FormData !== 'undefined' && payload instanceof FormData) {
                payload.set('_method', 'PUT');
                return PandansariAPI.fetch(`/admin/cms/kategori/${id}`, { method: 'POST', body: payload });
            }
            return PandansariAPI.fetch(`/admin/cms/kategori/${id}`, { method: 'PUT', body: payload });
        },
        /** DELETE /admin/cms/kategori/{id} — hapus baris + berkas gambar. */
        deleteCmsKategori(id) { return PandansariAPI.fetch(`/admin/cms/kategori/${id}`, { method: 'DELETE' }); },

        /* -- 19.10-19.12 Galeri, Profil Usaha, Fasilitas ( Kelola admin ) -- */
        /** GET /admin/cms/galeri — info galeri (judul wajib saat pertama) + daftar foto terurut. */
        cmsGaleri() { return PandansariAPI.fetch('/admin/cms/galeri'); },
        /** PUT /admin/cms/galeri — buat/ubah info galeri (judul wajib saat pertama). */
        updateCmsGaleri(payload) { return PandansariAPI.fetch('/admin/cms/galeri', { method: 'PUT', body: payload }); },
        /** POST /admin/cms/galeri/gambar — multipart `gambar` wajib (unggah sebelum galeri ada -> 404). */
        uploadGaleriFoto(formData) { return PandansariAPI.fetch('/admin/cms/galeri/gambar', { method: 'POST', body: formData }); },
        /** POST + `_method=PUT` /admin/cms/galeri/gambar/{id} — ganti gambar / teks alternatif / urutan (PUT multipart tak diparse PHP). */
        updateGaleriFoto(id, formData) {
            if (typeof FormData !== 'undefined' && formData instanceof FormData) {
                formData.set('_method', 'PUT');
                return PandansariAPI.fetch(`/admin/cms/galeri/gambar/${id}`, { method: 'POST', body: formData });
            }
            return PandansariAPI.fetch(`/admin/cms/galeri/gambar/${id}`, { method: 'PUT', body: formData });
        },
        /** DELETE /admin/cms/galeri/gambar/{id} — hapus foto + berkasnya. */
        deleteGaleriFoto(id) { return PandansariAPI.fetch(`/admin/cms/galeri/gambar/${id}`, { method: 'DELETE' }); },
        /** GET /admin/cms/profil-usaha — identitas toko, alamat, maps, jam_operasional (array objek). */
        profilUsaha() { return PandansariAPI.fetch('/admin/cms/profil-usaha'); },
        /** PUT /admin/cms/profil-usaha — parsial; multipart dikirim POST + `_method=PUT` (alasan sama dengan updateCmsKategori). */
        updateProfilUsaha(payload) {
            if (typeof FormData !== 'undefined' && payload instanceof FormData) {
                payload.set('_method', 'PUT');
                return PandansariAPI.fetch('/admin/cms/profil-usaha', { method: 'POST', body: payload });
            }
            return PandansariAPI.fetch('/admin/cms/profil-usaha', { method: 'PUT', body: payload });
        },
        /** GET /admin/cms/fasilitas — semua fasilitas (admin), urut urutan_tampilan. */
        fasilitas() { return PandansariAPI.fetch('/admin/cms/fasilitas'); },
        /** POST /admin/cms/fasilitas — nama_fasilitas wajib. */
        createFasilitas(payload) { return PandansariAPI.fetch('/admin/cms/fasilitas', { method: 'POST', body: payload }); },
        /** PUT /admin/cms/fasilitas/{id} — parsial. */
        updateFasilitas(id, payload) { return PandansariAPI.fetch(`/admin/cms/fasilitas/${id}`, { method: 'PUT', body: payload }); },
        /** DELETE /admin/cms/fasilitas/{id}. */
        deleteFasilitas(id) { return PandansariAPI.fetch(`/admin/cms/fasilitas/${id}`, { method: 'DELETE' }); },

        /* -- 3.13b Konfigurasi Banner Promo (cms_promo) -- */
        /** GET /admin/cms/promo — konfigurasi banner promo + voucher terpilih. */
        cmsPromoKonfig() { return PandansariAPI.fetch('/admin/cms/promo'); },
        /**
         * PUT /admin/cms/promo — multipart (gambar opsional).
         * PUT multipart tak diparse PHP → dikirim POST + `_method=PUT` (alasan sama dengan updateHero).
         */
        cmsPromoKonfigSimpan(payload) {
            if (payload instanceof FormData) {
                if (!payload.has('_method')) payload.append('_method', 'PUT');
                return PandansariAPI.fetch('/admin/cms/promo', { method: 'POST', body: payload });
            }
            return PandansariAPI.fetch('/admin/cms/promo', { method: 'PUT', body: payload });
        },
        /** DELETE /admin/cms/promo/gambar — kosongkan gambar banner tanpa menghapus konfigurasi. */
        cmsPromoKonfigHapusGambar() { return PandansariAPI.fetch('/admin/cms/promo/gambar', { method: 'DELETE' }); },

        cmsPromos() { return PandansariAPI.fetch('/admin/cms/promos'); },
        createCmsPromo(payload) { return PandansariAPI.fetch('/admin/cms/promos', { method: 'POST', body: payload }); },
        updateCmsPromo(id, payload) {
            if (typeof FormData !== 'undefined' && payload instanceof FormData) {
                payload.set('_method', 'PUT');
                return PandansariAPI.fetch(`/admin/cms/promos/${id}`, { method: 'POST', body: payload });
            }
            return PandansariAPI.fetch(`/admin/cms/promos/${id}`, { method: 'PUT', body: payload });
        },
        deleteCmsPromo(id) { return PandansariAPI.fetch(`/admin/cms/promos/${id}`, { method: 'DELETE' }); },

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

        /* -- 4.9 Inventaris -- */
        /** GET /admin/inventory/low-stock — varian dengan stok <= threshold. */
        inventoryLowStock() { return PandansariAPI.fetch('/admin/inventory/low-stock'); },
        /** GET /admin/inventory/valuation — { total_nilai_modal, total_nilai_jual, margin_potensial }. */
        inventoryValuation() { return PandansariAPI.fetch('/admin/inventory/valuation'); },
        /** GET /admin/inventory/export — CSV export. */
        inventoryExport() { return PandansariAPI.downloadCsv('/admin/inventory/export', 'inventaris.csv'); },
        /** POST /admin/inventory/adjustments — { id_variant, tipe: 'masuk'|'keluar'|'koreksi', jumlah, catatan? }. */
        inventoryAdjustment(payload) { return PandansariAPI.fetch('/admin/inventory/adjustments', { method: 'POST', body: payload }); },
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
     * URL gambar dari field backend `path_gambar` (relatif, mis. "products/x.jpg").
     * File storage diproksikan lewat frontend (/media/products/...) agar request
     * gambar tidak terkena halaman peringatan ngrok free. URL non-storage
     * (mis. CDN/Unsplash/data/blob) dikembalikan apa adanya.
     */
    imgUrl(path) {
        const p = String(path ?? '').trim();
        if (!p) return '';

        let pathname = p;

        if (/^https?:\/\//i.test(p)) {
            try {
                const u = new URL(p);
                if (!u.pathname.startsWith('/storage/')) return p;
                pathname = u.pathname;
            } catch {
                return p;
            }
        }

        let clean = pathname.replace(/^\/+/, '');
        if (clean.startsWith('storage/')) clean = clean.slice('storage/'.length);

        if (clean.startsWith('cms/hero/')) {
            const filename = clean.slice('cms/hero/'.length);
            if (!filename || filename.includes('/')) return p;

            return '/media/cms-hero/' + encodeURIComponent(filename);
        }

        if (clean.startsWith('cms/kategori/')) {
            const filename = clean.slice('cms/kategori/'.length);
            if (!filename || filename.includes('/')) return p;

            return '/media/cms-kategori/' + encodeURIComponent(filename);
        }

        if (clean.startsWith('cms/galeri/')) {
            const filename = clean.slice('cms/galeri/'.length);
            if (!filename || filename.includes('/')) return p;

            return '/media/cms-galeri/' + encodeURIComponent(filename);
        }

        if (clean.startsWith('cms/promo/')) {
            const filename = clean.slice('cms/promo/'.length);
            if (!filename || filename.includes('/')) return p;

            return '/media/cms-promo/' + encodeURIComponent(filename);
        }

        if (clean.startsWith('vouchers/')) {
            const filename = clean.slice('vouchers/'.length);
            if (!filename || filename.includes('/')) return p;

            return '/media/vouchers/' + encodeURIComponent(filename);
        }

        if (!clean.startsWith('products/')) return p;

        const filename = clean.slice('products/'.length);
        if (!filename || filename.includes('/')) return p;

        return '/media/products/' + encodeURIComponent(filename);
    },

    /**
     * Ambil URL gambar utama dari daftar `images` produk.
     * Utama = is_primary true, fallback elemen pertama. Kosong '' jika belum ada gambar.
     */
    coverUrl(images) {
        if (!images) return '';
        if (typeof images === 'string') return this.imgUrl(images);
        if (typeof images === 'object' && !Array.isArray(images)) {
            if (images.path_gambar || images.url) return this.imgUrl(images.path_gambar ?? images.url);
        }
        const list = Array.isArray(images) ? images : [];
        const img = list.find(x => x && x.is_primary) ?? list[0];
        return this.imgUrl(img?.path_gambar ?? img?.url ?? '');
    },

    /* ---------- Cache & Resolver Gambar Produk Terpusat ---------- */
    _productImageCache: {
        byId: {},
        byName: {},
        byVariant: {},
        bySku: {},
    },
    _catalogLoadingPromise: null,

    _normalizeName(name) {
        return String(name ?? '')
            .toLowerCase()
            .replace(/[_\-\/\\]+/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    },

    _initImageCache() {
        try {
            const raw = localStorage.getItem('pandansari_product_images');
            if (raw) {
                const parsed = JSON.parse(raw);
                if (parsed && typeof parsed === 'object') {
                    this._productImageCache.byId = { ...this._productImageCache.byId, ...(parsed.byId || {}) };
                    this._productImageCache.byName = { ...this._productImageCache.byName, ...(parsed.byName || {}) };
                    this._productImageCache.byVariant = { ...this._productImageCache.byVariant, ...(parsed.byVariant || {}) };
                    this._productImageCache.bySku = { ...this._productImageCache.bySku, ...(parsed.bySku || {}) };
                }
            }
        } catch (_) {}
    },

    _persistImageCache() {
        try {
            localStorage.setItem('pandansari_product_images', JSON.stringify(this._productImageCache));
        } catch (_) {}
    },

    cacheProductImages(products) {
        const list = Array.isArray(products) ? products : [products];
        let changed = false;
        list.forEach(p => {
            if (!p) return;
            const cover = this.coverUrl(p.images);
            if (cover) {
                if (p.id_product) {
                    this._productImageCache.byId[p.id_product] = cover;
                    changed = true;
                }
                if (p.nama_produk) {
                    this._productImageCache.byName[this._normalizeName(p.nama_produk)] = cover;
                    changed = true;
                }
                if (p.sku) {
                    this._productImageCache.bySku[String(p.sku).trim().toLowerCase()] = cover;
                    changed = true;
                }
                if (Array.isArray(p.variants)) {
                    p.variants.forEach(v => {
                        if (v?.id_variant) {
                            this._productImageCache.byVariant[v.id_variant] = cover;
                            if (v.sku) this._productImageCache.bySku[String(v.sku).trim().toLowerCase()] = cover;
                            changed = true;
                        }
                    });
                }
            }
        });
        if (changed) this._persistImageCache();
    },

    cacheVariantsForProduct(productId, variants) {
        const cover = this._productImageCache.byId[productId];
        if (!cover || !Array.isArray(variants)) return;
        let changed = false;
        variants.forEach(v => {
            if (v?.id_variant) {
                this._productImageCache.byVariant[v.id_variant] = cover;
                if (v.sku) this._productImageCache.bySku[String(v.sku).trim().toLowerCase()] = cover;
                changed = true;
            }
        });
        if (changed) this._persistImageCache();
    },

    cacheImageForProduct(productId, coverUrl) {
        if (!productId || !coverUrl) return;
        this._productImageCache.byId[productId] = coverUrl;
        this._persistImageCache();
    },

    async loadCatalogImages() {
        if (this._catalogLoadingPromise) return this._catalogLoadingPromise;
        this._catalogLoadingPromise = (async () => {
            try {
                const res = await this.fetch('/products?per_page=100');
                if (res.ok && Array.isArray(res.data)) {
                    this.cacheProductImages(res.data);
                }
            } catch (_) {
            } finally {
                this._catalogLoadingPromise = null;
            }
        })();
        return this._catalogLoadingPromise;
    },

    /**
     * Resolusi URL gambar produk dari berbagai bentuk data (item keranjang, checkout, pesanan, dsb).
     * Mengecek direct image, nested variant/product, lalu cache berdasarkan id_product, id_variant, sku, atau nama_produk.
     */
    getProductImage(item) {
        if (!item) return '';

        // 1. Gambar string langsung
        if (typeof item === 'string' && item.trim()) {
            return this.imgUrl(item);
        }

        // 2. Properti gambar pada item
        if (item.image && typeof item.image === 'string' && !item.image.includes('katalog1.webp')) {
            return this.imgUrl(item.image);
        }
        if (item.images) {
            const c = this.coverUrl(item.images);
            if (c) return c;
        }
        if (item.product?.images) {
            const c = this.coverUrl(item.product.images);
            if (c) return c;
        }
        if (item.variant?.product?.images) {
            const c = this.coverUrl(item.variant.product.images);
            if (c) return c;
        }
        if (item.variant?.images) {
            const c = this.coverUrl(item.variant.images);
            if (c) return c;
        }

        // 3. Berdasarkan id_variant
        const vid = item.id_variant ?? item.variant?.id_variant;
        if (vid && this._productImageCache.byVariant[vid]) {
            return this._productImageCache.byVariant[vid];
        }

        // 4. Berdasarkan id_product
        const pid = item.id_product ?? item.product?.id_product ?? item.variant?.product?.id_product ?? item.variant?.id_product;
        if (pid && this._productImageCache.byId[pid]) {
            return this._productImageCache.byId[pid];
        }

        // 5. Berdasarkan SKU
        const sku = item.sku ?? item.variant?.sku;
        if (sku) {
            const cleanSku = String(sku).trim().toLowerCase();
            if (this._productImageCache.bySku[cleanSku]) {
                return this._productImageCache.bySku[cleanSku];
            }
        }

        // 6. Berdasarkan nama produk
        const name = item.nama_produk ?? item.name ?? item.product?.nama_produk ?? item.variant?.product?.nama_produk;
        if (name) {
            const norm = this._normalizeName(name);
            if (this._productImageCache.byName[norm]) {
                return this._productImageCache.byName[norm];
            }
            // Fuzzy substring match
            for (const [cachedName, url] of Object.entries(this._productImageCache.byName)) {
                if (norm.length > 4 && (norm.includes(cachedName) || cachedName.includes(norm))) {
                    return url;
                }
            }
        }

        return '';
    },
};

window.PandansariAPI._init();

// Store global untuk badge navbar (Keranjang & Wishlist) — dibaca via $store.lencana.
document.addEventListener('alpine:init', () => {
    if (window.Alpine && typeof window.Alpine.store === 'function') {
        window.Alpine.store('lencana', { cart: 0, wishlist: 0 });
    }
});
