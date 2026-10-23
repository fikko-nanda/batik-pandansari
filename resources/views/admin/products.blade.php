@extends('layouts.admin')

@section('content')

<div x-data="{
    // === Gate admin ===
    needLogin: false,
    notAdmin: false,

    // === Data API ===
    products: [],
    meta: null,
    page: 1,
    perPage: 10,
    categories: [],
    selectedCategory: 'ALL',
    searchQuery: '',
    loading: true,
    notice: '',
    noticeOk: true,

    // === Form produk (dipakai komponen x-admin.modal-add-product) ===
    photoMax: 5,
    sizeOptions: ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'],
    colorOptions: ['Hitam', 'Putih', 'Abu-abu', 'Merah', 'Biru Utama', 'Kuning', 'Hijau', 'Oranye', 'Ungu', 'Biru Muda', 'Cokelat', 'Merah Muda'],

    showModalTambah: false,
    savingProduk: false,
    savingStep: '',
    formMode: 'create',
    formStep: 'identitas',
    stepOrder: ['identitas', 'atribut', 'varian', 'panduan'],

    form: {
        id_product: null,
        nama_produk: '',
        sku: '',
        deskripsi: '',
        id_category: '',
        berat_produk: '',
        bahan_kain: '',
        status_produk: 'draft',
    },

    existingPhotos: [],
    newPhotos: [],
    deletedPhotoIds: [],
    existingSizeChart: null,
    newSizeChart: null,
    deleteSizeChart: false,
    photoSeq: 0,

    sizesSelected: [],
    colorsSelected: [],
    variantRows: [],
    bulkHarga: '',
    bulkStok: '',

    // === Helper tampilan ===
    fmt(n) { return PandansariAPI.formatRp(n); },
    num(n) { return Number(n ?? 0).toLocaleString('id-ID'); },
    coverOf(p) { return PandansariAPI.coverUrl(p.images) || '{{ asset('images/katalog1.webp') }}'; },
    stokOf(p) {
        const v = (p.variants ?? []).filter(x => x.status_varian !== 'nonaktif');
        return v.reduce((s, x) => s + Number(x.stok ?? 0), 0);
    },
    hargaMinOf(p) {
        const v = (p.variants ?? []).filter(x => x.status_varian !== 'nonaktif' && x.harga != null);
        return v.length ? Math.min(...v.map(x => Number(x.harga))) : null;
    },
    pesan(ok, msg) {
        this.noticeOk = ok;
        this.notice = msg;
        clearTimeout(this._t);
        this._t = setTimeout(() => this.notice = '', 8000);
    },
    apiError(res, prefix) {
        const errs = Object.values(res.errors ?? {}).flat();
        this.pesan(false, prefix + ': ' + (errs[0] ?? res.message));
    },

    // === Data produk ===
    async loadProduk() {
        this.loading = true;
        const res = await PandansariAPI.admin.adminProducts({
            page: this.page,
            per_page: this.perPage,
            search: this.searchQuery.trim() || undefined,
            category: this.selectedCategory !== 'ALL' ? this.selectedCategory : undefined,
        });
        this.loading = false;
        if (res.ok) {
            this.products = res.data ?? [];
            this.meta = res.meta;
        } else {
            this.pesan(false, res.message);
        }
    },
    async loadKategori() {
        const res = await PandansariAPI.categories();
        const URUT = ['Batik Tulis', 'Batik Cap', 'Bahan Handprint', 'Pakaian Wanita', 'Pakaian Pria', 'Pakaian Anak-Anak', 'Sarimbit'];
        const list = (res.data ?? []).filter(c => c.status_aktif !== false);
        list.sort((a, b) => {
            const ia = URUT.indexOf(a.nama_kategori);
            const ib = URUT.indexOf(b.nama_kategori);
            if (ia === -1 && ib === -1) return (a.nama_kategori ?? '').localeCompare(b.nama_kategori ?? '');
            if (ia === -1) return 1;
            if (ib === -1) return -1;
            return ia - ib;
        });
        this.categories = list;
    },
    setKategori(id) { this.selectedCategory = id; this.page = 1; this.loadProduk(); },
    goto(p) {
        if (p < 1 || (this.meta && p > this.meta.last_page)) return;
        this.page = p;
        this.loadProduk();
    },

    // === Form: buka / tutup ===
    openCreate() {
        this.resetForm();
        this.showModalTambah = true;
    },
    async openEdit(p, step = 'identitas') {
        this.resetForm();
        this.formMode = 'edit';
        this.form.id_product = p.id_product;
        this.form.nama_produk = p.nama_produk ?? '';
        this.form.sku = p.sku ?? '';
        this.form.deskripsi = p.deskripsi ?? '';
        this.form.id_category = p.id_category ?? p.category?.id_category ?? '';
        this.form.berat_produk = p.berat_produk ?? '';
        this.form.bahan_kain = p.bahan_kain ?? '';
        this.showModalTambah = true;
        this.formStep = 'identitas';
        this.savingStep = 'Memuat data produk...';

        const res = await PandansariAPI.admin.adminProduct(p.id_product);
        if (!res.ok) {
            this.savingStep = '';
            this.apiError(res, 'Gagal memuat detail produk');
            return;
        }

        const prod = res.data ?? p;
        this.form.nama_produk = prod.nama_produk ?? '';
        this.form.sku = prod.sku ?? '';
        this.form.deskripsi = prod.deskripsi ?? '';
        this.form.id_category = prod.id_category ?? prod.category?.id_category ?? '';
        this.form.berat_produk = prod.berat_produk ?? '';
        this.form.bahan_kain = prod.bahan_kain ?? '';

        const [fotoRes, chartRes] = await Promise.all([
            PandansariAPI.admin.adminProductImages(prod.id_product, 'produk'),
            PandansariAPI.admin.adminProductImages(prod.id_product, 'size_chart'),
        ]);

        if (fotoRes.ok) {
            this.existingPhotos = (fotoRes.data ?? []).map(img => ({
                ...img,
                url: PandansariAPI.imgUrl(img.path_gambar ?? img.url ?? ''),
            }));
        }

        const chartRaw = (chartRes.ok ? (chartRes.data ?? [])[0] : null) ?? prod.size_chart ?? null;
        const chart = Array.isArray(chartRaw) ? (chartRaw[0] ?? null) : chartRaw;
        this.existingSizeChart = chart ? {
            ...chart,
                    url: PandansariAPI.imgUrl(chart.path_gambar ?? chart.url ?? ''),
        } : null;

        const aktif = (prod.variants ?? []).filter(v => v.status_varian !== 'nonaktif');
        this.variantRows = aktif.map(v => ({
            key: (v.ukuran ?? '') + '|' + (v.warna ?? ''),
            id_variant: v.id_variant,
            ukuran: v.ukuran ?? '',
            warna: v.warna ?? '',
            nama_varian: v.nama_varian ?? '',
            sku: v.sku ?? '',
            harga: v.harga ?? '',
            stok: v.stok ?? 0,
            berat_gram: v.berat_gram ?? (Number(prod.berat_produk) || 500),
            status_varian: v.status_varian ?? 'aktif',
        }));
        this.sizesSelected = [...new Set(aktif.map(v => v.ukuran).filter(Boolean))];
        this.colorsSelected = [...new Set(aktif.map(v => v.warna).filter(Boolean))];

        this.formStep = step;
        this.savingStep = '';
    },
    closeModal() {
        if (this.savingProduk) return;
        this.showModalTambah = false;
        this.resetForm();
    },
    resetForm() {
        this.revokeNewPhotos();
        this.revokeSizeChart();
        this.formMode = 'create';
        this.formStep = 'identitas';
        this.form = {
            id_product: null,
            nama_produk: '',
            sku: '',
            deskripsi: '',
            id_category: '',
            berat_produk: '',
            bahan_kain: '',
            status_produk: 'draft',
        };
        this.existingPhotos = [];
        this.newPhotos = [];
        this.deletedPhotoIds = [];
        this.existingSizeChart = null;
        this.newSizeChart = null;
        this.deleteSizeChart = false;
        this.sizesSelected = [];
        this.colorsSelected = [];
        this.variantRows = [];
        this.bulkHarga = '';
        this.bulkStok = '';
        this.savingStep = '';
    },
    revokeNewPhotos() {
        this.newPhotos.forEach(p => {
            if (p.url && String(p.url).startsWith('blob:')) URL.revokeObjectURL(p.url);
        });
    },
    revokeSizeChart() {
        if (this.newSizeChart?.url && String(this.newSizeChart.url).startsWith('blob:')) {
            URL.revokeObjectURL(this.newSizeChart.url);
        }
    },
    nextStep() {
        if (this.formStep === 'identitas') {
            if (!this.form.nama_produk.trim() || !this.form.sku.trim() || !this.form.id_category || !Number(this.form.berat_produk)) {
                this.pesan(false, 'Lengkapi nama produk, SKU, kategori, dan berat produk.');
                return;
            }
            if (this.photoTotal() < 1) {
                this.pesan(false, 'Unggah minimal 1 foto produk.');
                return;
            }
        }
        if (this.formStep === 'varian') {
            if (!this.sizesSelected.length) {
                this.pesan(false, 'Pilih minimal satu ukuran.');
                return;
            }
            this.syncVariantRows();
        }
        const i = this.stepOrder.indexOf(this.formStep);
        if (i >= 0 && i < this.stepOrder.length - 1) this.formStep = this.stepOrder[i + 1];
    },
    prevStep() {
        const i = this.stepOrder.indexOf(this.formStep);
        if (i > 0) this.formStep = this.stepOrder[i - 1];
    },

    // === Foto produk ===
    photoTotal() { return this.existingPhotos.length + this.newPhotos.length; },
    addPhotos(e) {
        const files = Array.from(e.target.files ?? []);
        e.target.value = '';
        if (!files.length) return;

        const remaining = this.photoMax - this.photoTotal();
        if (remaining <= 0) {
            this.pesan(false, 'Maksimal ' + this.photoMax + ' foto produk.');
            return;
        }

        let added = 0;
        for (const file of files) {
            if (added >= remaining) break;
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                this.pesan(false, file.name + ' harus JPG, PNG, atau WebP.');
                continue;
            }
            if (file.size > 2 * 1024 * 1024) {
                this.pesan(false, file.name + ' melebihi batas 2 MB.');
                continue;
            }
            this.photoSeq++;
            this.newPhotos.push({
                key: 'new-' + this.photoSeq,
                file,
                url: URL.createObjectURL(file),
                is_primary: false,
            });
            added++;
        }

        if (files.length > remaining) {
            this.pesan(false, 'Hanya ' + remaining + ' foto tambahan yang diterima (maksimal ' + this.photoMax + ').');
        }
        if (!this.existingPhotos.some(f => f.is_primary) && !this.newPhotos.some(f => f.is_primary) && this.newPhotos.length) {
            this.newPhotos[0].is_primary = true;
        }
    },
    setNewCover(idx) {
        this.existingPhotos.forEach(f => f.is_primary = false);
        this.newPhotos.forEach((f, i) => f.is_primary = i === idx);
    },
    removeExistingPhoto(idx) {
        const foto = this.existingPhotos[idx];
        if (!foto) return;
        if (foto.id_image) this.deletedPhotoIds.push(foto.id_image);
        this.existingPhotos.splice(idx, 1);
    },
    removeNewPhoto(idx) {
        const foto = this.newPhotos[idx];
        if (!foto) return;
        if (foto.url) URL.revokeObjectURL(foto.url);
        this.newPhotos.splice(idx, 1);
        if (!this.newPhotos.some(f => f.is_primary) && this.newPhotos.length) {
            this.newPhotos[0].is_primary = true;
        }
    },

    // === Size chart ===
    pickSizeChart(e) {
        const file = e.target.files?.[0];
        e.target.value = '';
        if (!file) return;
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            this.pesan(false, 'Size chart harus JPG, PNG, atau WebP.');
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            this.pesan(false, 'Size chart melebihi batas 2 MB.');
            return;
        }
        this.revokeSizeChart();
        this.newSizeChart = { file, url: URL.createObjectURL(file) };
        if (this.existingSizeChart) this.deleteSizeChart = true;
    },
    removeSizeChart() {
        this.revokeSizeChart();
        this.newSizeChart = null;
        if (this.existingSizeChart) this.deleteSizeChart = true;
    },

    // === Varian ===
    sanitizeSku(v) {
        return String(v ?? '').toUpperCase().replace(/[^A-Z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    },
    makeVariantSku(size, color, used) {
        const base = this.sanitizeSku(this.form.sku || 'VAR');
        const core = this.sanitizeSku([base, size, color].filter(Boolean).join('-')).slice(0, 100) || 'VAR';
        let sku = core;
        let n = 2;
        while (used.has(sku)) {
            const suffix = '-' + n;
            sku = (core.slice(0, 100 - suffix.length) || 'VAR') + suffix;
            n++;
        }
        return sku;
    },
    syncVariantRows() {
        const colors = this.colorsSelected.length ? this.colorsSelected : [''];
        const oldRows = this.variantRows;
        const used = new Set(oldRows.map(r => r.sku).filter(Boolean));
        const rows = [];

        for (const size of this.sizesSelected) {
            for (const color of colors) {
                const key = size + '|' + color;
                const found = oldRows.find(r => r.key === key);
                if (found) {
                    rows.push({
                        ...found,
                        berat_gram: Number(this.form.berat_produk) || found.berat_gram || 500,
                    });
                    continue;
                }

                const sku = this.makeVariantSku(size, color, used);
                used.add(sku);
                rows.push({
                    key,
                    id_variant: null,
                    ukuran: size,
                    warna: color,
                    nama_varian: color ? size + '-' + color : size,
                    sku,
                    harga: '',
                    stok: 0,
                    berat_gram: Number(this.form.berat_produk) || 500,
                    status_varian: 'aktif',
                });
            }
        }
        this.variantRows = rows;
    },
    generateVariants() {
        if (!this.sizesSelected.length) {
            this.pesan(false, 'Pilih minimal satu ukuran.');
            return;
        }
        this.syncVariantRows();
        this.pesan(true, this.variantRows.length + ' varian siap diatur.');
    },
    applyAllHarga() {
        const n = Number(this.bulkHarga);
        if (this.bulkHarga === '' || !isFinite(n) || n < 0) {
            this.pesan(false, 'Harga massal tidak valid.');
            return;
        }
        this.variantRows.forEach(r => r.harga = n);
        this.pesan(true, 'Harga diterapkan ke semua varian.');
    },
    applyAllStok() {
        const n = Number(this.bulkStok);
        if (this.bulkStok === '' || !isFinite(n) || n < 0) {
            this.pesan(false, 'Stok massal tidak valid.');
            return;
        }
        this.variantRows.forEach(r => r.stok = Math.floor(n));
        this.pesan(true, 'Stok diterapkan ke semua varian.');
    },

    // === Validasi & simpan ===
    validateForm() {
        if (!this.form.nama_produk?.trim()) { this.formStep = 'identitas'; this.pesan(false, 'Nama produk wajib diisi.'); return false; }
        if (!this.form.sku?.trim()) { this.formStep = 'identitas'; this.pesan(false, 'SKU produk wajib diisi.'); return false; }
        if (!this.form.id_category) { this.formStep = 'identitas'; this.pesan(false, 'Kategori produk wajib dipilih.'); return false; }
        const berat = Number(this.form.berat_produk);
        if (!Number.isInteger(berat) || berat < 1 || berat > 1000000) { this.formStep = 'identitas'; this.pesan(false, 'Berat produk wajib berupa angka bulat 1-1.000.000 gram.'); return false; }
        if (this.photoTotal() < 1) { this.formStep = 'identitas'; this.pesan(false, 'Unggah minimal 1 foto produk.'); return false; }
        if (this.photoTotal() > this.photoMax) { this.formStep = 'identitas'; this.pesan(false, 'Foto produk maksimal ' + this.photoMax + '.'); return false; }

        if (!this.sizesSelected.length) { this.formStep = 'varian'; this.pesan(false, 'Pilih minimal satu ukuran varian.'); return false; }
        this.syncVariantRows();
        if (!this.variantRows.length) { this.formStep = 'varian'; this.pesan(false, 'Belum ada varian yang dibuat.'); return false; }
        if (this.variantRows.length > 500) { this.formStep = 'varian'; this.pesan(false, 'Maksimal 500 varian per produk.'); return false; }

        const skus = new Set();
        for (const row of this.variantRows) {
            if (!row.sku?.trim()) { this.formStep = 'varian'; this.pesan(false, 'SKU varian wajib diisi.'); return false; }
            if (skus.has(row.sku)) { this.formStep = 'varian'; this.pesan(false, 'SKU varian duplikat: ' + row.sku); return false; }
            skus.add(row.sku);
            if (row.harga === '' || row.harga === null || !isFinite(Number(row.harga)) || Number(row.harga) < 0) {
                this.formStep = 'varian';
                this.pesan(false, 'Harga varian ' + row.nama_varian + ' tidak valid.');
                return false;
            }
            if (row.stok === '' || row.stok === null || !Number.isInteger(Number(row.stok)) || Number(row.stok) < 0) {
                this.formStep = 'varian';
                this.pesan(false, 'Stok varian ' + row.nama_varian + ' harus berupa angka bulat minimal 0.');
                return false;
            }
        }
        return true;
    },
    productPayload() {
        return {
            id_category: this.form.id_category,
            nama_produk: this.form.nama_produk.trim(),
            sku: this.form.sku.trim().toUpperCase(),
            deskripsi: this.form.deskripsi?.trim() || null,
            berat_produk: Number(this.form.berat_produk),
            bahan_kain: this.form.bahan_kain?.trim() || null,
        };
    },
    variantPayload() {
        this.syncVariantRows();
        return this.variantRows.map(row => ({
            ...(row.id_variant ? { id_variant: row.id_variant } : {}),
            sku: row.sku,
            nama_varian: row.nama_varian,
            warna: row.warna || null,
            ukuran: row.ukuran,
            harga: Number(row.harga),
            stok: Math.floor(Number(row.stok || 0)),
            berat_gram: Math.floor(Number(this.form.berat_produk) || 500),
            status_varian: 'aktif',
        }));
    },
    async saveProduct() {
        if (this.savingProduk) return;
        if (!this.validateForm()) return;

        this.savingProduk = true;
        const isNew = this.formMode === 'create';
        let id = this.form.id_product;

        try {
            this.savingStep = isNew ? 'Membuat produk...' : 'Menyimpan identitas produk...';
            const payload = this.productPayload();
            if (isNew) payload.status_produk = 'draft';

            let res = id
                ? await PandansariAPI.admin.updateProduct(id, payload)
                : await PandansariAPI.admin.createProduct(payload);

            if (!res.ok) {
                this.apiError(res, 'Gagal menyimpan produk');
                return;
            }

            if (!id) {
                id = res.data?.id_product;
                if (!id) {
                    this.pesan(false, 'Respons produk tidak memuat id_product.');
                    return;
                }
                this.form.id_product = id;
                this.formMode = 'edit';
            }

            // Hapus foto lama yang ditandai
            for (const imageId of [...this.deletedPhotoIds]) {
                this.savingStep = 'Menghapus foto lama...';
                const del = await PandansariAPI.admin.deleteAdminProductImage(id, imageId);
                if (!del.ok) {
                    this.apiError(del, 'Gagal menghapus foto');
                    return;
                }
                this.deletedPhotoIds = this.deletedPhotoIds.filter(x => x !== imageId);
            }

            // Upload foto baru satu per satu
            let nextUrutan = this.existingPhotos.reduce((m, f) => Math.max(m, Number(f.urutan ?? 0)), 0) + 1;
            while (this.newPhotos.length) {
                const photo = this.newPhotos[0];
                this.savingStep = 'Mengunggah foto produk...';
                const fd = new FormData();
                fd.append('gambar', photo.file);
                fd.append('type', 'produk');
                fd.append('is_primary', photo.is_primary ? '1' : '0');
                fd.append('urutan', String(nextUrutan++));
                const up = await PandansariAPI.admin.uploadAdminProductImage(id, fd);
                if (!up.ok) {
                    this.apiError(up, 'Gagal mengunggah foto');
                    return;
                }
                if (up.data) {
                    this.existingPhotos.push({
                        ...up.data,
                        url: PandansariAPI.imgUrl(up.data.path_gambar ?? up.data.url ?? ''),
                    });
                }
                URL.revokeObjectURL(photo.url);
                this.newPhotos.shift();
            }

            // Size chart: hapus lama (jika diminta), lalu unggah yang baru
            if (this.deleteSizeChart && this.existingSizeChart?.id_image) {
                this.savingStep = 'Menghapus size chart lama...';
                const del = await PandansariAPI.admin.deleteAdminProductImage(id, this.existingSizeChart.id_image);
                if (!del.ok) {
                    this.apiError(del, 'Gagal menghapus size chart');
                    return;
                }
                this.existingSizeChart = null;
                this.deleteSizeChart = false;
            }
            if (this.newSizeChart?.file) {
                this.savingStep = 'Mengunggah size chart...';
                const fd = new FormData();
                fd.append('gambar', this.newSizeChart.file);
                fd.append('type', 'size_chart');
                fd.append('is_primary', '0');
                const up = await PandansariAPI.admin.uploadAdminProductImage(id, fd);
                if (!up.ok) {
                    this.apiError(up, 'Gagal mengunggah size chart');
                    return;
                }
                const chart = up.data ?? null;
                this.existingSizeChart = chart ? {
                    ...chart,
            url: PandansariAPI.imgUrl(chart.path_gambar ?? chart.url ?? ''),
                } : this.existingSizeChart;
                URL.revokeObjectURL(this.newSizeChart.url);
                this.newSizeChart = null;
            }

            // Simpan varian secara bulk
            this.savingStep = 'Menyimpan varian...';
            const items = this.variantPayload();
            const bulk = await PandansariAPI.admin.bulkVariants(id, items, !isNew);
            if (!bulk.ok) {
                // Fallback bila endpoint bulk belum tersedia di server.
                if (bulk.status === 404 || bulk.status === 405) {
                    for (const item of items) {
                        const r = item.id_variant
                            ? await PandansariAPI.admin.updateVariant(item.id_variant, item)
                            : await PandansariAPI.admin.createVariant(id, item);
                        if (!r.ok) {
                            this.apiError(r, 'Gagal menyimpan varian');
                            return;
                        }
                    }
                } else {
                    this.apiError(bulk, 'Gagal menyimpan varian');
                    return;
                }
            }

            // Aktifkan produk setelah foto + varian tersimpan
            this.savingStep = 'Mengaktifkan produk...';
            const aktif = await PandansariAPI.admin.updateProduct(id, { status_produk: 'aktif' });
            if (!aktif.ok) {
                this.apiError(aktif, 'Produk tersimpan sebagai draft, tetapi gagal diaktifkan');
                return;
            }

            this.showModalTambah = false;
            this.pesan(true, 'Produk ' + this.form.nama_produk + ' berhasil disimpan dan diaktifkan.');
            this.resetForm();
            await this.loadProduk();
        } finally {
            this.savingProduk = false;
            this.savingStep = '';
        }
    },
    async nonaktifkan(p) {
        if (!confirm('Nonaktifkan (sembunyikan dari toko) produk ' + p.nama_produk + '?')) return;
        const res = await PandansariAPI.admin.deleteProduct(p.id_product);
        if (res.ok) {
            this.pesan(true, 'Produk dinonaktifkan.');
            this.loadProduk();
        } else {
            this.pesan(false, res.message);
        }
    },

    async init() {
        if (!PandansariAPI.getToken()) { this.needLogin = true; this.loading = false; return; }
        const me = await PandansariAPI.me();
        if (!me.ok || me.data?.peran !== 'admin') { this.notAdmin = true; this.loading = false; return; }
        await Promise.all([this.loadProduk(), this.loadKategori()]);
        this.loading = false;

        const urlParams = new URLSearchParams(window.location.search);
        const varianId = urlParams.get('varian');
        if (varianId) {
            const produk = this.products.find(p => p.id_product == varianId);
            if (produk) {
                this.openEdit(produk, 'varian');
            } else {
                const res = await PandansariAPI.admin.adminProduct(varianId);
                if (res.ok && res.data) this.openEdit(res.data, 'varian');
            }
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    }
}" class="space-y-6">

    <!-- Gate -->
    <div x-show="needLogin" x-cloak class="bg-white rounded-2xl border border-slate-200 p-12 text-center space-y-4">
        <i class="fa-solid fa-lock text-3xl text-slate-300"></i>
        <p class="text-sm font-bold text-[#0F1A24]">Silakan login (akun admin) untuk membuka katalog admin</p>
        <a href="/login" class="inline-block bg-[#0A192F] text-white text-xs font-bold px-6 py-3 rounded-xl">LOGIN</a>
    </div>
    <div x-show="notAdmin" x-cloak class="bg-white rounded-2xl border border-slate-200 p-12 text-center space-y-4">
        <i class="fa-solid fa-user-slash text-3xl text-slate-300"></i>
        <p class="text-sm font-bold text-[#0F1A24]">Akun ini bukan admin - akses ditolak.</p>
    </div>

    <div x-show="!needLogin && !notAdmin" x-cloak class="space-y-6">

        <!-- 1. HEADER BANNER -->
        <div class="bg-[#0A192F] text-white p-6 sm:p-8 rounded-2xl relative overflow-hidden shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-4 max-w-2xl relative z-10">
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight leading-tight">
                    Katalog Mahakarya & Kurasi Varian Wastra
                </h1>
                <p class="text-xs text-slate-300 leading-relaxed font-normal">
                    Kelola identitas produk, foto, bahan, varian ukuran/warna, dan panduan ukuran.
                </p>

                <div class="flex items-center gap-2.5 pt-1">
                    <div class="relative w-full max-w-md">
                        <i class="fa-solid fa-sliders absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-600 text-xs"></i>
                        <input type="text"
                               x-model.debounce.600ms="searchQuery"
                               @input.debounce.600ms="page = 1; loadProduk()"
                               placeholder="Saring nama / deskripsi..."
                               class="w-full text-xs font-semibold bg-[#AEE2F0] text-slate-900 placeholder-slate-600 rounded-xl pl-9 pr-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-sky-400 border-none">
                    </div>
                    <button type="button" @click="PandansariAPI.admin.productsExport()"
                            class="bg-[#AEE2F0] hover:bg-[#97d7e8] text-slate-900 p-2.5 px-3 rounded-xl transition cursor-pointer shrink-0 font-bold" title="Unduh CSV (export produk)">
                        <i class="fa-solid fa-download text-xs"></i>
                    </button>
                </div>

                <div class="flex flex-wrap gap-2 pt-1 text-[10px] font-extrabold">
                    <button @click="setKategori('ALL')"
                            :class="selectedCategory === 'ALL' ? 'bg-[#73b9cb] text-slate-900 shadow-inner' : 'bg-[#AEE2F0] text-slate-900 hover:bg-[#90d2e3]'"
                            class="px-3.5 py-1.5 rounded-xl transition cursor-pointer uppercase tracking-wider">
                        SEMUA
                    </button>
                    <template x-for="c in categories" :key="c.id_category">
                        <button @click="setKategori(c.id_category)"
                                :class="selectedCategory === c.id_category ? 'bg-[#73b9cb] text-slate-900 shadow-inner' : 'bg-[#AEE2F0] text-slate-900 hover:bg-[#90d2e3]'"
                                class="px-3.5 py-1.5 rounded-xl transition cursor-pointer uppercase tracking-wider"
                                x-text="c.nama_kategori">
                        </button>
                    </template>
                </div>
            </div>

            <div class="absolute right-0 bottom-0 top-0 z-0 opacity-25 pointer-events-none md:opacity-40 flex items-end justify-end">
                <img src="{{ asset('images/wayang.png') }}" alt="Wayang Wastra" class="h-full w-auto object-contain mix-blend-luminosity filter brightness-150">
            </div>
        </div>

        <!-- Toast -->
        <div x-show="notice" x-cloak x-transition
             class="fixed top-4 right-4 z-[9999] max-w-md text-sm font-bold rounded-2xl px-5 py-4 shadow-2xl border flex items-start gap-3"
             :class="noticeOk ? 'bg-emerald-600 text-white border-emerald-700' : 'bg-rose-600 text-white border-rose-700'"
             role="alert">
            <i class="fa-solid mt-0.5" :class="noticeOk ? 'fa-circle-check' : 'fa-triangle-exclamation'"></i>
            <span x-text="notice"></span>
        </div>

        <!-- 2. STATS SUMMARY CARDS -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div class="bg-sky-100/60 border border-sky-200/80 p-5 rounded-2xl flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold text-sky-800 uppercase tracking-wider block mb-1">ARSIP KURASI AKTIF</span>
                    <div class="text-2xl font-extrabold text-slate-800"><span x-text="num(meta?.total ?? products.length)"></span> <span class="text-xs font-semibold text-slate-500">Produk</span></div>
                </div>
                <div class="w-10 h-10 bg-white text-sky-600 rounded-xl flex items-center justify-center text-base shadow-sm">
                    <i class="fa-solid fa-box-archive"></i>
                </div>
            </div>

            <div class="bg-slate-900 text-white p-5 rounded-2xl flex items-center justify-between border border-slate-800">
                <div>
                    <span class="text-[9px] font-bold text-rose-400 uppercase tracking-wider block mb-0.5">PERHATIAN MENDESAK</span>
                    <div class="text-2xl font-extrabold text-white"><span x-text="products.filter(p => stokOf(p) <= 2).length"></span> SKU <span class="text-xs font-semibold text-rose-300">Menipis/Habis</span></div>
                    <p class="text-[9px] text-slate-400 mt-0.5">Stok varian aktif &lt;= 2</p>
                </div>
                <div class="w-10 h-10 bg-rose-500/20 text-rose-400 rounded-xl flex items-center justify-center text-base border border-rose-500/30">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
            </div>

            <div class="bg-sky-100/60 border border-sky-200/80 p-5 rounded-2xl flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold text-sky-800 uppercase tracking-wider block mb-1">TOTAL STOK FISIK</span>
                    <div class="text-2xl font-extrabold text-slate-800"><span x-text="num(products.reduce((s, p) => s + stokOf(p), 0))"></span> <span class="text-xs font-semibold text-slate-500">unit</span></div>
                </div>
                <div class="w-10 h-10 bg-white text-sky-600 rounded-xl flex items-center justify-center text-base shadow-sm">
                    <i class="fa-solid fa-warehouse"></i>
                </div>
            </div>
        </div>

        <!-- 3. ACTION BAR & TABEL -->
        <div class="space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <button type="button" @click="openCreate()" class="bg-sky-400 hover:bg-sky-500 text-slate-900 font-extrabold text-xs px-4 py-2.5 rounded-xl transition flex items-center gap-2 self-start shadow-sm cursor-pointer">
                    <i class="fa-solid fa-circle-plus"></i>
                    <span>TAMBAH MAHAKARYA BARU</span>
                </button>

                <div class="flex items-center gap-3 self-end">
                    <span class="text-xs text-slate-500 font-bold">Arsip Master</span>
                    <span class="text-[10px] text-slate-400">Menampilkan <strong x-text="products.length"></strong> karya (hal. <span x-text="meta?.current_page ?? 1"></span>/<span x-text="meta?.last_page ?? 1"></span>)</span>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-sky-50/60 text-slate-700 text-[10px] font-bold uppercase tracking-wider border-b border-slate-200">
                                <th class="py-3 px-4">WASTRA & CANTING</th>
                                <th class="py-3 px-4 text-center">SKU</th>
                                <th class="py-3 px-4 text-center">STOK TOTAL</th>
                                <th class="py-3 px-4">HARGA MULAI</th>
                                <th class="py-3 px-4 text-center">VISIBILITAS TOKO</th>
                                <th class="py-3 px-4 text-center">AKSI</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            <template x-if="loading">
                                <tr><td colspan="6" class="py-10 text-center text-slate-400 font-semibold"><i class="fa-solid fa-circle-notch fa-spin mr-1"></i> Memuat produk...</td></tr>
                            </template>
                            <template x-if="!loading && !products.length">
                                <tr><td colspan="6" class="py-10 text-center text-slate-400 font-semibold">Tidak ada produk untuk filter ini.</td></tr>
                            </template>
                            <template x-for="p in products" :key="p.id_product">
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center gap-3">
                                            <button type="button" @click="openEdit(p)" title="Edit produk"
                                                    class="relative shrink-0 group cursor-pointer rounded-xl">
                                                <img :src="coverOf(p)" class="w-12 h-12 object-cover rounded-xl border border-slate-200 bg-slate-100">
                                                <span class="absolute inset-0 rounded-xl bg-slate-950/55 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white text-sm">
                                                    <i class="fa-solid fa-pen"></i>
                                                </span>
                                            </button>
                                            <div class="space-y-0.5">
                                                <div class="flex items-center gap-2">
                                                    <span class="bg-[#0A192F] text-white text-[9px] font-bold px-1.5 py-0.5 rounded" x-text="(p.category?.nama_kategori ?? 'TANPA').toUpperCase()"></span>
                                                    <strong class="text-slate-800 text-sm" x-text="p.nama_produk"></strong>
                                                </div>
                                                <p class="text-[10px] text-slate-400 line-clamp-1 max-w-xs truncate" x-text="p.deskripsi ?? '-'"></p>
                                                <span class="text-[9px] font-bold block" :class="stokOf(p) > 0 ? 'text-sky-600' : 'text-rose-600'"
                                                      x-text="stokOf(p) > 0 ? ((p.variants ?? []).length + ' varian aktif') : 'TANPA STOK'"></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 text-center font-mono text-[10px] text-slate-500 font-bold" x-text="p.sku ?? p.slug"></td>
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="font-extrabold text-xs px-2.5 py-1 rounded-lg inline-block" :class="stokOf(p) <= 2 ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-800'" x-text="stokOf(p) + ' unit'"></span>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <strong class="text-slate-800 block" x-text="hargaMinOf(p) != null ? fmt(hargaMinOf(p)) : '-'"></strong>
                                        <span class="text-[10px] text-slate-400 block">Harga varian terendah</span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="text-white text-[10px] font-bold px-3 py-1 rounded-full inline-block uppercase"
                                              :class="p.status_produk === 'aktif' ? 'bg-sky-700' : (p.status_produk === 'draft' ? 'bg-amber-500' : 'bg-slate-400')"
                                              x-text="p.status_produk"></span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <div class="flex flex-col items-center gap-1">
                                            <button @click="openEdit(p)" class="bg-sky-100 hover:bg-sky-200 text-sky-800 text-[10px] font-bold px-2.5 py-1 rounded-lg w-24 transition cursor-pointer">Edit Produk</button>
                                            <button @click="openEdit(p, 'varian')" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-bold px-2.5 py-1 rounded-lg w-24 transition cursor-pointer">Kelola Varian</button>
                                            <button @click="nonaktifkan(p)" class="bg-rose-100 hover:bg-rose-200 text-rose-700 text-[10px] font-bold px-2.5 py-1 rounded-lg w-24 transition cursor-pointer">Nonaktifkan</button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-end gap-1.5 p-4 border-t border-slate-100 font-bold text-xs">
                    <button type="button" @click="goto(page - 1)" :disabled="page <= 1"
                            class="w-7 h-7 rounded-xl bg-white border border-slate-200 text-slate-600 flex items-center justify-center hover:bg-slate-100 cursor-pointer disabled:opacity-40">&lt;</button>
                    <span class="px-2 text-slate-500" x-text="(meta?.current_page ?? 1) + ' / ' + (meta?.last_page ?? 1)"></span>
                    <button type="button" @click="goto(page + 1)" :disabled="meta && page >= meta.last_page"
                            class="w-7 h-7 rounded-xl bg-white border border-slate-200 text-slate-600 flex items-center justify-center hover:bg-slate-100 cursor-pointer disabled:opacity-40">&gt;</button>
                </div>
            </div>
        </div>

    </div>

    <x-admin.modal-add-product />

</div>

@endsection
