/**
 * Batik Pandansari — Engineering Console (modul teknisi)
 * ------------------------------------------------------------------
 * Komponen Alpine yang dipakai halaman `resources/views/teknisi/*`:
 *
 *   teknisiDashboard        → widget GET /teknisi/dashboard + GET /teknisi/health
 *   teknisiLogExplorer(kind)→ tabel GET /teknisi/{audit|webhook|notification|failed}-logs
 *                             + modal JSON detail GET /teknisi/.../{id}
 *
 * Semua request lewat `PandansariAPI.fetch()` sehingga otomatis membawa
 * header `ngrok-skip-browser-warning: true` + `Authorization: Bearer <token>`.
 */

(function () {
    'use strict';

    const api = () => window.PandansariAPI;

    const KIND_META = {
        audit: { list: 'auditLogs', detail: 'auditLog', label: 'Audit Log', endpoint: '/teknisi/audit-logs' },
        webhook: { list: 'webhookLogs', detail: 'webhookLog', label: 'Webhook Log', endpoint: '/teknisi/webhook-logs' },
        notification: { list: 'notificationLogs', detail: 'notificationLog', label: 'Notification Log', endpoint: '/teknisi/notification-logs' },
        failed: { list: 'failedJobs', detail: 'failedJob', label: 'Failed Job', endpoint: '/teknisi/failed-jobs' },
    };

    /* ------------------------------------------------------------------
     * Helper umum
     * ------------------------------------------------------------------ */

    /** Nilai pertama yang terisi (bukan undefined/null/string kosong). */
    function first(...values) {
        for (const value of values) {
            if (value !== undefined && value !== null && value !== '') return value;
        }
        return undefined;
    }

    function pretty(value) {
        if (value === undefined || value === null) return '';
        if (typeof value === 'string') {
            const trimmed = value.trim();
            if (trimmed.startsWith('{') || trimmed.startsWith('[')) {
                try { return JSON.stringify(JSON.parse(trimmed), null, 2); } catch (_) { /* bukan JSON valid */ }
            }
            return value;
        }
        try { return JSON.stringify(value, null, 2); } catch (_) { return String(value); }
    }

    /** Parse string JSON (mis. kolom payload webhook) tanpa melempar error. */
    function parseJson(value) {
        if (value && typeof value === 'object') return value;
        if (typeof value === 'string') {
            try { return JSON.parse(value); } catch (_) { return null; }
        }
        return null;
    }

    function text(value) {
        if (value === undefined || value === null || value === '') return '';
        if (typeof value === 'object') return pretty(value);
        return String(value);
    }

    /** Ringkas pesan/exception jadi satu baris pendek. */
    function oneLine(value, max = 180) {
        const raw = text(value).replace(/\s*\r?\n\s*/g, ' ').trim();
        return raw.length > max ? raw.slice(0, max) + '…' : raw;
    }

    function formatDate(value) {
        if (!value) return '—';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return String(value);
        return date.toLocaleString('id-ID', {
            day: '2-digit', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit', second: '2-digit',
        });
    }

    function latency(value) {
        if (value === undefined || value === null || value === '') return '';
        const num = Number(value);
        if (Number.isFinite(num)) return `${num.toLocaleString('id-ID')} ms`;
        return String(value);
    }

    function badge(value, fallback) {
        const raw = text(value).trim();
        return (raw || fallback || 'LOG').toUpperCase();
    }

    /** Normalisasi envelope list → { rows, meta } (toleran paginator di dalam data). */
    function unpackList(res) {
        const payload = res ? res.data : null;
        let rows = [];
        let meta = res ? res.meta : null;

        if (Array.isArray(payload)) {
            rows = payload;
        } else if (payload && typeof payload === 'object') {
            const arrayKey = ['data', 'items', 'logs', 'jobs', 'results', 'records']
                .find((key) => Array.isArray(payload[key]));
            if (arrayKey) rows = payload[arrayKey];
            if (!meta && payload.meta) meta = payload.meta;
            if (!meta && payload.current_page !== undefined) {
                meta = {
                    current_page: payload.current_page,
                    last_page: payload.last_page,
                    total: payload.total,
                    per_page: payload.per_page,
                };
            }
        }

        return { rows, meta };
    }

    /** Detail bisa dibungkus { log: {...} } / { job: {...} } / { detail: {...} }. */
    function unpackDetail(res) {
        const data = res ? res.data : null;
        if (!data || typeof data !== 'object' || Array.isArray(data)) return data ?? null;
        for (const key of ['log', 'job', 'detail', 'item', 'record']) {
            const inner = data[key];
            if (inner && typeof inner === 'object' && !Array.isArray(inner)) return inner;
        }
        return data;
    }

    /* ------------------------------------------------------------------
     * Normalisasi baris log → bentuk seragam untuk tabel
     * ------------------------------------------------------------------ */
    function normalizeRow(kind, raw, index) {
        const row = (raw && typeof raw === 'object') ? raw : { value: raw };
        const id = first(
            row.id,
            row.id_audit_log,
            row.id_webhook_log,
            row.id_notification_log,
            row.id_failed_job,
            row.id_log,
            row.id_notifikasi,
            row.job_id,
            row.id_job,
            row.uuid,
            row.uuid_job,
        );
        const time = first(
            row.dibuat_pada,
            row.created_at,
            row.waktu,
            row.timestamp,
            row.failed_at,
            row.sent_at,
            row.attempted_at,
            row.queued_at,
        );

        const base = {
            key: id !== undefined ? String(id) : `row-${index}`,
            id: id !== undefined ? id : null,
            hasId: id !== undefined,
            raw: row,
            time: formatDate(time),
            badge: 'LOG',
            title: '—',
            subtitle: '',
            target: '',
            targetDesc: '',
            status: '',
            extra: '',
        };

        if (kind === 'audit') {
            const actor = first(
                row.user && row.user.nama_pengguna,
                row.pengguna && row.pengguna.nama_pengguna,
                row.nama_pengguna,
                row.aktor,
                row.user_email,
                row.user && row.user.email,
                row.id_user,
                row.id_pengguna,
                row.request_summary && row.request_summary.email,
                row.response_summary && row.response_summary.data
                    && row.response_summary.data.user && row.response_summary.data.user.nama_pengguna,
                row.response_summary && row.response_summary.data
                    && row.response_summary.data.user && row.response_summary.data.user.email,
            );
            const action = first(
                row.aksi, row.action, row.event, row.nama_event,
                row.event_type, row.jenis_aktivitas, row.modul,
            );
            const scope = first(row.service_name, row.modul, row.model_type, row.tabel, row.entitas, row.subject_type);
            const reference = first(
                row.correlation_id, row.referensi, row.reference,
                row.id_record, row.record_id, row.model_id,
            );
            const endpoint = first(row.endpoint, row.path, row.url);
            const method = first(row.http_method, row.method);
            const httpStatus = first(row.status, row.hasil, row.http_status);
            const duration = first(row.latency_ms, row.durasi_ms, row.response_time_ms);
            const ip = first(row.ip_address, row.ip, row.alamat_ip, row.user_agent);

            const title = action !== undefined
                ? text(action)
                : (endpoint !== undefined ? `${text(method)} ${text(endpoint)}`.trim() : 'Aktivitas sistem');

            return Object.assign(base, {
                badge: badge(scope, 'AUDIT'),
                title,
                subtitle: oneLine(first(
                    row.deskripsi, row.description, row.message, row.error_message,
                    row.request_summary,
                    row.response_summary && row.response_summary.message,
                )),
                target: actor !== undefined ? text(actor) : 'Sistem',
                targetDesc: ip !== undefined ? text(ip) : '',
                status: text(httpStatus) || 'LOGGED',
                extra: duration !== undefined
                    ? latency(duration)
                    : (reference !== undefined ? `ref: ${text(reference).slice(0, 18)}` : ''),
            });
        }

        if (kind === 'webhook') {
            const payload = parseJson(row.payload);
            const channel = first(row.provider, row.channel, row.kanal, row.sumber, row.source);
            const event = first(
                row.event, row.event_type, row.jenis_event, row.topik, row.topic,
                row.action, row.endpoint,
            );
            const source = first(
                row.catatan, row.status_message, row.error_message,
                row.payload_summary, row.provider,
            );
            const endpoint = first(row.endpoint, row.target, row.url, row.destination, row.path);
            const method = first(row.http_method, row.method);
            const reference = first(
                row.order_id, row.referensi, row.external_id, row.id_transaksi,
                payload && payload.order_id,
                row.order && row.order.nomor_pesanan,
                row.id_order,
            );
            const duration = first(row.latency_ms, row.durasi_ms, row.response_time_ms, row.latency, row.duration);
            const attempts = first(row.attempt_count, row.attempts, row.attempt, row.retry_count);
            const httpStatus = first(
                row.http_status, row.status_code, row.response_code,
                row.processing_status, row.status,
            );

            return Object.assign(base, {
                badge: badge(channel, 'WEBHOOK'),
                title: text(event) || 'Event webhook',
                subtitle: oneLine(source),
                target: endpoint !== undefined
                    ? `${text(method)} ${text(endpoint)}`.trim()
                    : '',
                targetDesc: reference !== undefined ? text(reference) : '',
                status: text(httpStatus) || 'RECEIVED',
                extra: duration !== undefined
                    ? latency(duration)
                    : (attempts !== undefined ? `percobaan ${text(attempts)}` : ''),
            });
        }

        if (kind === 'notification') {
            const channel = first(row.channel, row.kanal, row.provider, row.tipe, row.jenis);
            const event = first(
                row.notification_type, row.event, row.jenis, row.tipe,
                row.template, row.subject, row.subjek,
            );
            const recipient = first(
                row.penerima, row.recipient, row.recipient_masked, row.to,
                row.email, row.nomor_telepon, row.phone, row.nomor_hp,
                row.user && row.user.email,
                row.user && row.user.nama_pengguna,
            );
            const attempts = first(row.attempt_count, row.attempt, row.attempts, row.percobaan, row.retry_count);
            const sentAt = first(row.sent_at, row.terkirim_pada, row.delivered_at);

            return Object.assign(base, {
                badge: badge(channel, 'NOTIF'),
                title: text(event) || 'Notifikasi',
                subtitle: oneLine(first(
                    row.judul, row.subject, row.subjek, row.pesan, row.message,
                    row.provider, row.template,
                )),
                target: recipient !== undefined ? text(recipient) : '',
                targetDesc: oneLine(first(
                    row.reference_type, row.tujuan, row.destination, row.topik,
                ), 90),
                status: text(first(row.delivery_status, row.status, row.status_pengiriman, row.state)) || 'SENT',
                extra: sentAt !== undefined
                    ? `terkirim ${formatDate(sentAt)}`
                    : (attempts !== undefined ? `percobaan ${text(attempts)}` : ''),
            });
        }

        // kind === 'failed' — Laravel failed_jobs: utamakan uuid untuk endpoint detail.
        const failedId = first(row.uuid, row.uuid_job, row.id_failed_job, row.id);
        if (failedId !== undefined) {
            base.id = failedId;
            base.hasId = true;
            base.key = String(failedId);
        }

        const job = first(row.job, row.job_class, row.nama_job, row.name, row.job_name, row.payload && row.payload.displayName);
        const queue = first(row.queue, row.antrean, row.connection, row.koneksi);
        const attempts = first(row.attempts, row.attempt, row.percobaan);
        const failedAt = first(row.failed_at, row.gagal_pada);

        return Object.assign(base, {
            badge: badge(queue, 'QUEUE'),
            title: text(job) || 'Job gagal',
            subtitle: oneLine(first(row.exception, row.error, row.pesan_error, row.message, row.exception_message)),
            target: queue !== undefined ? text(queue) : '',
            targetDesc: oneLine(first(row.payload, row.data), 90),
            status: text(first(row.status)) || 'FAILED',
            extra: attempts !== undefined
                ? `percobaan ${text(attempts)}`
                : (failedAt !== undefined ? formatDate(failedAt) : ''),
        });
    }

    /* ------------------------------------------------------------------
     * Komponen Alpine: dashboard + health
     * ------------------------------------------------------------------ */
    document.addEventListener('alpine:init', () => {
        if (!window.Alpine) return;

        /* Profil user login di top navbar (nama + avatar inisial dinamis). */
        window.Alpine.data('teknisiTopbar', () => ({
            user: null,

            init() {
                const layer = api();
                this.user = (layer && typeof layer.getUser === 'function' ? layer.getUser() : null) || null;

                if (!layer || typeof layer.getToken !== 'function' || !layer.getToken()) return;
                if (typeof layer.me !== 'function') return;

                // Segarkan profil dari /me agar nama/role selalu sinkron dengan token aktif.
                layer.me()
                    .then((res) => {
                        if (res && res.ok && res.data && typeof res.data === 'object') {
                            this.user = res.data;
                        }
                    })
                    .catch(() => {});
            },

            /** Dukung beberapa bentuk payload backend: name / nama / nama_pengguna. */
            get displayName() {
                const name = this.user && (this.user.name || this.user.nama || this.user.nama_pengguna);
                return name || (this.user && this.user.email) || 'Teknisi';
            },

            getInitials(name) {
                const safe = String(name || '').trim();
                if (!safe) return 'TN';

                const parts = safe.split(/\s+/).filter(Boolean);
                const initials = parts.length > 1
                    ? parts[0].charAt(0) + parts[parts.length - 1].charAt(0)
                    : parts[0].slice(0, 2);

                return initials.toUpperCase();
            },

            get roleLabel() {
                const role = String(
                    (this.user && (this.user.peran || this.user.role)) || 'teknisi'
                ).toLowerCase();

                if (role === 'admin') return 'Administrator';
                if (role === 'teknisi') return 'Teknisi Operator';
                return role.charAt(0).toUpperCase() + role.slice(1);
            },
        }));

        window.Alpine.data('teknisiDashboard', () => ({
            loading: true,
            healthLoading: true,
            needLogin: false,
            dashboard: null,
            health: null,
            dashboardError: '',
            healthError: '',
            lastUpdated: null,
            showRaw: false,

            async init() {
                if (!api() || !api().getToken()) {
                    this.needLogin = true;
                    this.loading = false;
                    this.healthLoading = false;
                    return;
                }
                await this.loadAll();
            },

            async loadAll() {
                if (!api()) {
                    this.dashboardError = 'Layer PandansariAPI belum termuat — jalankan Vite (npm run dev / npm run build).';
                    this.loading = false;
                    this.healthLoading = false;
                    return;
                }
                await Promise.all([this.loadDashboard(), this.loadHealth()]);
                this.lastUpdated = new Date();
            },

            async loadDashboard() {
                this.loading = true;
                this.dashboardError = '';
                const res = await api().teknisi.dashboard();
                this.loading = false;
                if (res.status === 401) { this.needLogin = true; return; }
                if (res.ok) {
                    this.dashboard = res.data;
                } else {
                    this.dashboardError = res.message || 'Gagal memuat ringkasan dashboard.';
                }
            },

            async loadHealth() {
                this.healthLoading = true;
                this.healthError = '';
                const res = await api().teknisi.health();
                this.healthLoading = false;
                if (res.status === 401) { this.needLogin = true; return; }
                if (res.ok) {
                    this.health = res.data;
                } else {
                    this.healthError = res.message || 'Health check gagal.';
                }
            },

            /** Cari nilai berdasar beberapa kemungkinan nama kunci (rekursif, maks. 4 level). */
            findDeep(source, keys, depth = 0) {
                if (!source || typeof source !== 'object' || depth > 4) return undefined;
                for (const key of keys) {
                    if (source[key] !== undefined && source[key] !== null) return source[key];
                }
                for (const value of Object.values(source)) {
                    if (value && typeof value === 'object' && !Array.isArray(value)) {
                        const found = this.findDeep(value, keys, depth + 1);
                        if (found !== undefined) return found;
                    }
                }
                return undefined;
            },

            /** Ambil nilai nested berdasar path, mis. ['queue', 'pending_jobs']. */
            pathValue(source, path) {
                if (!source || typeof source !== 'object') return undefined;
                let current = source;
                for (const key of path) {
                    if (!current || typeof current !== 'object') return undefined;
                    current = current[key];
                    if (current === undefined || current === null) return undefined;
                }
                return current;
            },

            cards() {
                if (!this.dashboard) return [];
                // Bentuk asli GET /api/teknisi/dashboard (lihat docs/FRONTEND.md):
                // audit.total_24h, webhooks.received_24h, notifications.sent_24h, queue.pending_jobs, dst.
                const defs = [
                    { label: 'Audit 24 Jam', path: ['audit', 'total_24h'], keys: ['total_audit_logs', 'audit_logs_count'], icon: 'fa-code-commit', cls: 'bg-slate-200 text-slate-700' },
                    { label: 'Error Rate Audit', path: ['audit', 'error_rate_24h'], suffix: '%', keys: ['error_rate', 'audit_error_rate'], icon: 'fa-percent', cls: 'bg-amber-100 text-amber-700' },
                    { label: 'Webhook Diterima', path: ['webhooks', 'received_24h'], keys: ['total_webhook_logs', 'webhook_logs_count'], icon: 'fa-bolt', cls: 'bg-sky-100 text-sky-700' },
                    { label: 'Webhook Gagal', path: ['webhooks', 'failed_24h'], keys: ['failed_webhooks', 'webhook_failed'], icon: 'fa-plug-circle-xmark', cls: 'bg-rose-100 text-rose-700' },
                    { label: 'Notifikasi Terkirim', path: ['notifications', 'sent_24h'], keys: ['total_notification_logs', 'notification_logs_count'], icon: 'fa-envelope-circle-check', cls: 'bg-emerald-100 text-emerald-700' },
                    { label: 'Notifikasi Gagal', path: ['notifications', 'failed_24h'], keys: ['failed_notifications', 'notification_failed'], icon: 'fa-envelope-open-text', cls: 'bg-rose-100 text-rose-700' },
                    { label: 'Pending Jobs', path: ['queue', 'pending_jobs'], keys: ['total_pending_jobs', 'pending_jobs_count'], icon: 'fa-hourglass-half', cls: 'bg-amber-100 text-amber-700' },
                    { label: 'Failed Jobs', path: ['queue', 'failed_jobs'], keys: ['total_failed_jobs', 'failed_jobs_count'], icon: 'fa-triangle-exclamation', cls: 'bg-rose-100 text-rose-700' },
                ];

                const out = [];
                for (const def of defs) {
                    const value = this.pathValue(this.dashboard, def.path)
                        ?? this.findDeep(this.dashboard, def.keys);
                    if (value === undefined) continue;
                    out.push({
                        label: def.label,
                        value: this.formatStat(value, !!def.money) + (def.suffix || ''),
                        icon: def.icon,
                        cls: def.cls,
                    });
                }
                return out;
            },

            /** Fallback bila backend memakai nama kunci lain: tampilkan semua skalar. */
            genericStats() {
                if (!this.dashboard) return [];
                const out = [];
                const walk = (obj, prefix = '', depth = 0) => {
                    if (!obj || typeof obj !== 'object' || depth > 2 || out.length >= 12) return;
                    for (const [key, value] of Object.entries(obj)) {
                        if (out.length >= 12) return;
                        const label = prefix ? `${prefix}.${key}` : key;
                        if (value === null || ['string', 'number', 'boolean'].includes(typeof value)) {
                            out.push({
                                label,
                                value: this.formatStat(value, /revenue|pendapatan|omzet|total_nilai/i.test(key)),
                            });
                        } else if (value && typeof value === 'object' && !Array.isArray(value)) {
                            walk(value, label, depth + 1);
                        }
                    }
                };
                walk(this.dashboard);
                return out;
            },

            formatStat(value, money) {
                if (value === null || value === undefined) return '—';
                if (typeof value === 'boolean') return value ? 'Ya' : 'Tidak';
                if (money) return api().formatRp(value);
                if (typeof value === 'number') return value.toLocaleString('id-ID');
                const asNumber = Number(value);
                if (String(value).trim() !== '' && Number.isFinite(asNumber)) return asNumber.toLocaleString('id-ID');
                return oneLine(value, 80);
            },

            healthStatusLabel() {
                if (this.healthLoading) return 'MEMERIKSA…';
                if (this.healthError) return 'TIDAK TERHUBUNG';
                const status = first(
                    this.health && this.health.status,
                    this.health && this.health.state,
                    this.health && this.health.kondisi,
                    this.health && this.health.kesehatan,
                );
                return status !== undefined ? String(status).toUpperCase() : (this.health ? 'TERHUBUNG' : 'TIDAK DIKETAHUI');
            },

            healthOk() {
                if (this.healthError || !this.health) return false;
                const status = String(first(
                    this.health.status,
                    this.health.state,
                    this.health.kondisi,
                    this.health.kesehatan,
                ) ?? '').toLowerCase();
                if (['ok', 'up', 'healthy', 'sehat', 'online', 'aktif', 'active', 'success'].includes(status)) return true;
                if (['down', 'error', 'fail', 'failed', 'gagal', 'offline', 'degraded'].includes(status)) return false;
                return true;
            },

            healthItems() {
                if (!this.health || typeof this.health !== 'object') return [];
                const out = [];
                // Flatten bertingkat: database.driver, queue.pending_jobs, storage.writable, dst.
                const walk = (obj, prefix = '', depth = 0) => {
                    if (!obj || typeof obj !== 'object' || depth > 2 || out.length >= 12) return;
                    for (const [key, value] of Object.entries(obj)) {
                        if (out.length >= 12) return;
                        const label = prefix ? `${prefix}.${key}` : key;
                        if (value === null || value === undefined) continue;
                        if (typeof value === 'object') {
                            walk(value, label, depth + 1);
                        } else {
                            out.push({
                                label,
                                value: typeof value === 'boolean' ? (value ? 'Ya' : 'Tidak') : String(value),
                            });
                        }
                    }
                };
                walk(this.health);
                return out;
            },

            jsonDashboard() { return pretty(this.dashboard) || '// Belum ada data'; },
            jsonHealth() { return pretty(this.health) || '// Belum ada data'; },

            updatedText() {
                if (!this.lastUpdated) return '—';
                try { return this.lastUpdated.toLocaleTimeString('id-ID'); } catch (_) { return '—'; }
            },
        }));

        /* ------------------------------------------------------------------
         * Komponen Alpine: tabel log + modal JSON
         * ------------------------------------------------------------------ */
        window.Alpine.data('teknisiLogExplorer', (kind) => {
            const meta = KIND_META[kind] || KIND_META.audit;

            return {
                kind,
                meta,
                rows: [],
                metaPage: null,
                page: 1,
                perPage: 15,
                search: '',
                loading: true,
                refreshing: false,
                needLogin: false,
                error: '',
                lastUpdated: null,
                autoRefresh: true,
                _timer: null,

                detailOpen: false,
                detailLoading: false,
                detail: null,
                detailRow: null,
                detailTab: 'payload',
                detailError: '',

                async init() {
                    if (!api() || !api().getToken()) {
                        this.needLogin = true;
                        this.loading = false;
                        return;
                    }
                    await this.load();
                    if (this.autoRefresh) this.startAuto();
                },

                destroy() {
                    this.stopAuto();
                },

                get lastPage() {
                    return Math.max(1, Number(this.metaPage && this.metaPage.last_page) || 1);
                },

                get totalRows() {
                    return Number(this.metaPage && this.metaPage.total) || this.rows.length;
                },

                async load(silent = false) {
                    if (!api()) {
                        this.error = 'Layer PandansariAPI belum termuat — jalankan Vite (npm run dev / npm run build).';
                        this.loading = false;
                        return;
                    }

                    this.error = '';
                    if (silent) this.refreshing = true;
                    else this.loading = true;

                    const res = await api().teknisi[meta.list]({
                        page: this.page,
                        per_page: this.perPage,
                        search: this.search.trim() || undefined,
                    });

                    if (silent) this.refreshing = false;
                    else this.loading = false;
                    this.lastUpdated = new Date();

                    if (res.status === 401) {
                        this.needLogin = true;
                        this.stopAuto();
                        this.rows = [];
                        return;
                    }
                    if (res.status === 403) {
                        this.error = res.message || 'Akses ditolak — akun ini tidak berwenang membuka modul teknisi.';
                        this.rows = [];
                        return;
                    }

                    const unpacked = unpackList(res);
                    if (res.ok) {
                        this.rows = unpacked.rows.map((raw, index) => normalizeRow(kind, raw, index));
                        this.metaPage = unpacked.meta;
                        if (unpacked.meta && unpacked.meta.current_page) {
                            this.page = Number(unpacked.meta.current_page);
                        }
                    } else {
                        this.error = res.message || 'Gagal memuat data dari server.';
                        this.rows = [];
                        this.metaPage = null;
                    }
                },

                goto(target) {
                    const next = Math.min(Math.max(1, Number(target) || 1), this.lastPage);
                    if (next === this.page) return;
                    this.page = next;
                    this.load();
                },

                toggleAuto() {
                    this.autoRefresh = !this.autoRefresh;
                    if (this.autoRefresh) this.startAuto();
                    else this.stopAuto();
                },

                startAuto() {
                    this.stopAuto();
                    this._timer = setInterval(() => {
                        if (!this.detailOpen && !this.loading && !this.refreshing) this.load(true);
                    }, 15000);
                },

                stopAuto() {
                    clearInterval(this._timer);
                    this._timer = null;
                },

                statusClass(status) {
                    const value = String(status || '').toLowerCase();
                    if (/(success|ok|sukses|sent|delivered|terkirim|200|done|selesai|up|healthy|logged|received)/.test(value)) {
                        return 'bg-emerald-50 text-emerald-700 border-emerald-200';
                    }
                    if (/(fail|gagal|error|timeout|dead|down|500|exception|rejected)/.test(value)) {
                        return 'bg-rose-50 text-rose-700 border-rose-200';
                    }
                    if (/(pending|queue|queued|retry|menunggu|processing|waiting|attempt)/.test(value)) {
                        return 'bg-amber-50 text-amber-700 border-amber-200';
                    }
                    return 'bg-slate-100 text-slate-600 border-slate-200';
                },

                updatedText() {
                    if (!this.lastUpdated) return '—';
                    try { return this.lastUpdated.toLocaleTimeString('id-ID'); } catch (_) { return '—'; }
                },

                /* ---------------- Detail modal ---------------- */
                async openDetail(row) {
                    this.detailRow = row;
                    this.detailOpen = true;
                    this.detailTab = 'payload';
                    this.detailError = '';
                    this.detail = null;

                    if (!row || !row.hasId) {
                        this.detail = row ? row.raw : null;
                        this.detailLoading = false;
                        this.detailError = 'Baris ini tidak menyertakan ID — menampilkan data mentah dari tabel.';
                        return;
                    }

                    this.detailLoading = true;
                    const res = await api().teknisi[meta.detail](row.id);
                    this.detailLoading = false;

                    if (res.ok) {
                        this.detail = unpackDetail(res);
                    } else {
                        this.detail = row.raw;
                        this.detailError = res.message || 'Detail gagal dimuat — menampilkan data tabel.';
                    }
                },

                closeDetail() {
                    this.detailOpen = false;
                    this.detail = null;
                    this.detailRow = null;
                    this.detailError = '';
                },

                detailPayload() {
                    const data = this.detail;
                    if (!data || typeof data !== 'object') return data;
                    const payload = first(
                        data.payload,
                        data.request_payload,
                        data.request,
                        data.request_body,
                        data.body,
                        data.data && data.data.payload,
                    );
                    return payload !== undefined ? payload : data;
                },

                detailResponse() {
                    const data = this.detail;
                    if (!data || typeof data !== 'object') return data;
                    const response = first(
                        data.response,
                        data.response_body,
                        data.result,
                        data.output,
                        data.data && data.data.response,
                    );
                    return response !== undefined ? response : data;
                },

                detailJson() {
                    if (this.detailLoading) return '';
                    if (this.detail === null || this.detail === undefined) return '// Tidak ada data';
                    if (this.detailTab === 'response') return pretty(this.detailResponse());
                    if (this.detailTab === 'all') return pretty(this.detail);
                    return pretty(this.detailPayload());
                },

                async copyDetail() {
                    const value = this.detailJson();
                    try {
                        await navigator.clipboard.writeText(value);
                        api().toast('JSON disalin ke clipboard.');
                    } catch (_) {
                        api().toast('Gagal menyalin — pilih teks JSON secara manual.', false);
                    }
                },
            };
        });
    });
})();
