@extends('layouts.teknisi')

@section('content')

<div x-data="{
    searchQuery: '',
    selectedChannel: 'ALL',
    selectedStatus: 'ALL',
    autoSync: true,
    activeInspector: 1,

    // Stream Webhook Logs
    logs: [
        {
            id: 1,
            time: '14:31:12',
            timeDetail: 'detik ke-02',
            channel: 'MIDTRANS',
            event: 'payment.settlement',
            orderId: 'BN-88294 • BCA VA',
            target: '/api/v1/webhook/snap',
            targetDesc: 'Internal Dispatcher',
            latency: '34ms',
            eventId: 'evt_7f02d9a9041b',
            idempotencyKey: 'idemp_bp_9941a20f',
            originSource: 'Midtrans Snap Webhook Agent',
            dispatchTarget: 'POST /api/v1/webhook/snap',
            timestamp: '2026-05-18 14:31:12 WIB',
            jsonPayload: `{
  'transaction_time': '2026-05-18 14:30:58',
  'transaction_status': 'settlement',
  'transaction_id': '712a8190-21ba-45d2-98aa-f91848b191c2',
  'status_code': '200',
  'signature_key': 'f7c9b209e8471b0aa447192837bc901efcba58291402',
  'order_id': 'BN-88294',
  'gross_amount': '3659000.00',
  'fraud_status': 'accept',
  'currency': 'IDR'
}`
        },
        {
            id: 2,
            time: '14:28:45',
            timeDetail: 'detik ke-18',
            channel: 'RAJAONGKIR',
            event: 'shipment.dispatched',
            orderId: 'resi: SC-881920391 • SiCepat BEST',
            target: '/api/v1/logistics/push',
            targetDesc: 'Sync Klaten Hub',
            latency: '52ms'
        },
        {
            id: 3,
            time: '14:25:02',
            timeDetail: 'detik ke-55',
            channel: 'WHATSAPP',
            event: 'notification.receipt',
            orderId: 'dest: +628129988xxxx • Batik Tulis',
            target: 'https://wa-gate.pandansari.id/v2',
            targetDesc: 'Provider Meta API',
            latency: '1.200ms'
        },
        {
            id: 4,
            time: '14:20:19',
            timeDetail: 'detik ke-40',
            channel: 'EMAIL',
            event: 'certificate.sent',
            orderId: 'koleksi: Parang Kusumo Sutra',
            target: 'https://api.resend.com/emails',
            targetDesc: 'Transactional Resend',
            latency: '89ms'
        },
        {
            id: 5,
            time: '14:12:00',
            timeDetail: 'detik ke-11',
            channel: 'MIDTRANS',
            event: 'payment.expire',
            orderId: 'order_id: BN-88289 • Timeout',
            target: '/api/v1/webhook/snap',
            targetDesc: 'Inventory Unreserve',
            latency: 'TIMEOUT'
        },
        {
            id: 6,
            time: '14:05:33',
            timeDetail: 'detik ke-09',
            channel: 'RAJAONGKIR',
            event: 'courier.delivered',
            orderId: 'resi: JNE-99481120 • Menteng, Jakpus',
            target: '/api/v1/logistics/pod',
            targetDesc: 'Customer Auto-Review',
            latency: '41ms'
        },
        {
            id: 7,
            time: '13:58:14',
            timeDetail: 'detik ke-30',
            channel: 'MIDTRANS',
            event: 'payment.capture',
            orderId: 'order_id: BN-88291 • Credit Card',
            target: '/api/v1/webhook/snap',
            targetDesc: '3DS Authenticated',
            latency: '28ms'
        },
        {
            id: 8,
            time: '13:42:50',
            timeDetail: 'detik ke-04',
            channel: 'WHATSAPP',
            event: 'shipment.notify',
            orderId: 'dest: +628771128xxxx • Waybill Push',
            target: 'https://wa-gate.pandansari.id/v2',
            targetDesc: 'Queued Worker',
            latency: '610ms'
        }
    ]
}" class="space-y-6 font-sans">

    <!-- TITLE HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest block mb-0.5">ATELIER CORE RELAY • EVENT DISPATCHER</span>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Log Webhook & Notifikasi</h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Pengawasan relai data webhook Midtrans, kuotasi RajaOngkir, dan dispatcher notifikasi pesanan batik adibusana real-time.
            </p>
        </div>

        <!-- AUTO-SYNC CONTROL -->
        <div class="bg-white border border-slate-200 rounded-xl p-2 flex items-center gap-3 text-xs font-bold shrink-0 shadow-sm">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
            <span class="text-slate-700">Auto-Sync</span>
            <span class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded font-mono text-[10px]">5s</span>
        </div>
    </div>

    <!-- FILTER BAR -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4 space-y-3">
        <div class="flex flex-wrap items-center gap-2">
            <!-- Search Event ID -->
            <div class="relative flex-1 min-w-[240px]">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" x-model="searchQuery" placeholder="Cari Event ID, Order ID (e.g. BN-88294)..." class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-4 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400">
            </div>

            <!-- Kanal Filter Tabs -->
            <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl text-[10px] font-bold">
                <button type="button" class="bg-[#0A192F] text-white px-3 py-1.5 rounded-lg shadow-sm">SEMUA</button>
                <button type="button" class="text-slate-600 hover:text-slate-900 px-3 py-1.5">MIDTRANS</button>
                <button type="button" class="text-slate-600 hover:text-slate-900 px-3 py-1.5">RAJAONGKIR</button>
                <button type="button" class="text-slate-600 hover:text-slate-900 px-3 py-1.5">WHATSAPP</button>
                <button type="button" class="text-slate-600 hover:text-slate-900 px-3 py-1.5">EMAIL</button>
            </div>

            <!-- Status Badges -->
            <div class="flex items-center gap-1.5 text-[10px] font-bold">
                <span class="bg-[#0A192F] text-white px-3 py-1.5 rounded-xl">ALL <span class="text-sky-300 font-mono">842</span></span>
                <span class="bg-emerald-50 text-emerald-800 border border-emerald-200 px-3 py-1.5 rounded-xl">200 OK <span class="text-emerald-600 font-mono">839</span></span>
                <span class="bg-amber-50 text-amber-800 border border-amber-200 px-3 py-1.5 rounded-xl">RETRY <span class="text-amber-600 font-mono">2</span></span>
                <span class="bg-rose-50 text-rose-800 border border-rose-200 px-3 py-1.5 rounded-xl">FAILED <span class="text-rose-600 font-mono">1</span></span>
            </div>
        </div>
    </div>

    <!-- MAIN STREAM + INSPECTOR PANEL GRID -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- STREAM LOGS TABLE (7 COLS) -->
        <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h2 class="text-xs font-black uppercase tracking-wider text-slate-800">ANTREAN WEBHOOK EVENT BUS (REAL-TIME STREAM)</h2>
                <span class="text-[10px] font-bold text-slate-400">Menampilkan 8 rekaman terkini</span>
            </div>

            <!-- Stream List -->
            <div class="space-y-2 text-xs">
                <template x-for="log in logs" :key="log.id">
                    <div @click="activeInspector = log.id" 
                         :class="activeInspector === log.id ? 'bg-sky-50/80 border-sky-300 ring-2 ring-sky-200' : 'bg-slate-50/60 border-slate-200/80 hover:bg-slate-100/60'" 
                         class="border rounded-xl p-3 flex items-center justify-between transition cursor-pointer">
                        
                        <!-- Waktu -->
                        <div class="w-20 shrink-0">
                            <strong class="text-slate-900 font-black text-xs block font-mono" x-text="log.time"></strong>
                            <span class="text-[9px] text-slate-400 font-mono block" x-text="log.timeDetail"></span>
                        </div>

                        <!-- Event & Sumber -->
                        <div class="flex-1 px-3">
                            <div class="flex items-center gap-1.5">
                                <span class="text-[9px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded bg-slate-200 text-slate-700" x-text="log.channel"></span>
                                <strong class="text-slate-900 font-bold text-xs" x-text="log.event"></strong>
                            </div>
                            <span class="text-[10px] text-slate-500 block font-mono mt-0.5" x-text="log.orderId"></span>
                        </div>

                        <!-- Target Destination -->
                        <div class="w-44 text-left hidden sm:block">
                            <span class="text-[10px] font-mono text-slate-700 block truncate" x-text="log.target"></span>
                            <span class="text-[9px] text-slate-400 block" x-text="log.targetDesc"></span>
                        </div>

                        <!-- Latency -->
                        <div class="w-16 text-right">
                            <span class="text-[10px] font-mono font-bold" :class="log.latency === 'TIMEOUT' ? 'text-rose-600' : 'text-slate-700'" x-text="log.latency"></span>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Pagination -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-slate-500">
                <span class="text-[10px]">Menampilkan 1-8 dari 842 total event log (24 jam terakhir)</span>
                <div class="flex items-center gap-1 text-xs">
                    <button type="button" class="px-2 py-1 text-slate-400 hover:text-slate-700">Sebelumnya</button>
                    <span class="w-6 h-6 bg-[#0A192F] text-white rounded-lg flex items-center justify-center text-xs font-black">1</span>
                    <button type="button" class="w-6 h-6 text-slate-600 hover:text-slate-900">2</button>
                    <button type="button" class="w-6 h-6 text-slate-600 hover:text-slate-900">3</button>
                    <button type="button" class="px-2 py-1 text-slate-700 hover:text-slate-900">Berikutnya</button>
                </div>
            </div>
        </div>

        <!-- WEBHOOK INSPECTOR DARK PANEL (5 COLS) -->
        <div class="lg:col-span-5 bg-[#0A111E] text-slate-300 rounded-2xl p-5 space-y-4 border border-slate-800 shadow-xl font-mono">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-code text-sky-400 text-sm"></i>
                    <h3 class="text-xs font-black text-white uppercase tracking-wider font-sans">WEBHOOK INSPECTOR</h3>
                </div>
                <span class="bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-[9px] font-extrabold px-2 py-0.5 rounded uppercase tracking-wider">
                    SHA256 MATCHED
                </span>
            </div>

            <!-- Metadata List -->
            <div class="space-y-1.5 text-[10px] text-slate-400">
                <div class="flex justify-between">
                    <span>Event ID:</span>
                    <strong class="text-sky-400">evt_7f02d9a9041b</strong>
                </div>
                <div class="flex justify-between">
                    <span>Idempotency Key:</span>
                    <strong class="text-slate-200">idemp_bp_9941a20f</strong>
                </div>
                <div class="flex justify-between">
                    <span>Origin Source:</span>
                    <strong class="text-slate-200">Midtrans Snap Webhook Agent</strong>
                </div>
                <div class="flex justify-between">
                    <span>Dispatch Target:</span>
                    <strong class="text-emerald-400">POST /api/v1/webhook/snap</strong>
                </div>
                <div class="flex justify-between">
                    <span>Timestamp (UTC/WIB):</span>
                    <strong class="text-slate-400">2026-05-18 14:31:12 WIB</strong>
                </div>
            </div>

            <!-- Tabs Payload / Response / Backoff -->
            <div class="grid grid-cols-3 gap-1 bg-slate-900/80 p-1 rounded-xl text-center text-[10px] font-bold border border-slate-800 font-sans">
                <button type="button" class="bg-sky-600 text-white py-1.5 rounded-lg shadow-sm">PAYLOAD (JSON)</button>
                <button type="button" class="text-slate-400 hover:text-white py-1.5">RESPONSE BODY</button>
                <button type="button" class="text-slate-400 hover:text-white py-1.5">BACKOFF & RETRIES</button>
            </div>

            <!-- JSON Payload Viewer Code Box -->
            <div class="bg-[#050A12] border border-slate-800 rounded-xl p-3 font-mono text-[10px] text-sky-300 leading-relaxed overflow-x-auto h-52">
<pre class="m-0">{
  "transaction_time": "2026-05-18 14:30:58",
  "transaction_status": "settlement",
  "transaction_id": "712a8190-21ba-45d2-98aa-f91848b191c2",
  "status_code": "200",
  "signature_key": "f7c9b209e8471b0aa447192837bc901efcba58291482",
  "order_id": "BN-88294",
  "gross_amount": "3659000.00",
  "fraud_status": "accept",
  "currency": "IDR",
  "va_numbers": [ ... ]
}</pre>
            </div>

            <!-- Inspector Action Buttons -->
            <div class="space-y-2 pt-1 font-sans">
                <div class="grid grid-cols-2 gap-2 text-xs font-bold">
                    <button type="button" class="bg-slate-800 hover:bg-slate-700 text-slate-200 py-2 rounded-xl transition flex items-center justify-center gap-1.5 border border-slate-700 cursor-pointer">
                        <i class="fa-regular fa-copy text-xs"></i>
                        <span>Salin cURL</span>
                    </button>
                    <button type="button" class="bg-slate-800 hover:bg-slate-700 text-slate-200 py-2 rounded-xl transition flex items-center justify-center gap-1.5 border border-slate-700 cursor-pointer">
                        <i class="fa-solid fa-download text-xs"></i>
                        <span>Unduh Log</span>
                    </button>
                </div>

                <button type="button" class="w-full bg-[#1EABF1] hover:bg-[#1892ce] text-white py-2.5 rounded-xl font-black text-xs transition shadow-md flex items-center justify-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-rotate text-xs"></i>
                    <span>Re-trigger Webhook (Manual Emulate)</span>
                </button>
            </div>
        </div>

    </div>

</div>

@endsection