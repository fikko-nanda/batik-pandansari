<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Pembuat berkas PDF laporan keuangan memakai dompdf/dompdf.
 *
 * Library dipakai secara langsung (tanpa provider Laravel) agar tidak
 * menambah integrasi framework yang belum tentu kompatibel dengan versi
 * Laravel yang dipakai project.
 */
class ReportPdfService
{
    /**
     * Batas jumlah baris tabel transaksi dalam satu PDF. Data di luar
     * batas ini dilewati dan dilaporkan lewat field 'dilewati' agar
     * berkas tetap wajar ukurannya.
     */
    public const MAX_TRANSACTION_ROWS = 500;

    /**
     * Membuat berkas PDF laporan dari struktur data yang sudah dihitung.
     *
     * @param  array<string, mixed>  $ringkasan  angka ringkasan (konsisten
     *                                           dengan GET /admin/reports/summary)
     * @param  array<int, array<string, mixed>>  $transaksi  baris buku kas
     * @return array{binary: string, dilewati: int}
     */
    public function build(
        string $judul,
        string $periode,
        array $ringkasan,
        array $transaksi,
        array $filterTambahan = []
    ): array {
        $totalRows = count($transaksi);
        $dilewati = max(0, $totalRows - self::MAX_TRANSACTION_ROWS);
        $rows = array_slice($transaksi, 0, self::MAX_TRANSACTION_ROWS);

        $html = $this->renderHtml(
            $judul,
            $periode,
            $ringkasan,
            $rows,
            $dilewati,
            $filterTambahan
        );

        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('chroot', sys_get_temp_dir());

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return [
            'binary' => $dompdf->output(),
            'dilewati' => $dilewati,
        ];
    }

    /**
     * @param  array<string, mixed>  $ringkasan
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $filterTambahan
     */
    private function renderHtml(
        string $judul,
        string $periode,
        array $ringkasan,
        array $rows,
        int $dilewati,
        array $filterTambahan
    ): string {
        $pendapatan = $ringkasan['pendapatan'] ?? [];
        $pembayaran = $ringkasan['pembayaran'] ?? [];
        $pesanan = $ringkasan['pesanan'] ?? [];

        $periodeRows = '';

        foreach ($filterTambahan as $label => $nilai) {
            if ($nilai === null || $nilai === '') {
                continue;
            }

            $periodeRows .= '<tr><td class="label">'.$this->esc((string) $label).'</td>'
                .'<td>'.$this->esc((string) $nilai).'</td></tr>';
        }

        $ringkasanRows = [
            ['Total pendapatan', $this->rupiah($pendapatan['total_pendapatan'] ?? 0)],
            ['Jumlah pembayaran berhasil', (string) ($pendapatan['jumlah_transaksi_bayar'] ?? 0)],
            ['Jumlah transaksi pembayaran', (string) ($pembayaran['total_transaksi'] ?? 0)],
            ['Jumlah pesanan', (string) ($pesanan['jumlah_pesanan'] ?? 0)],
            ['Total nilai pesanan', $this->rupiah($pesanan['total_nilai_pesanan'] ?? 0)],
        ];

        $perStatusRows = '';

        foreach (($pembayaran['per_status'] ?? []) as $status => $jumlah) {
            $nilai = $pembayaran['nilai_per_status'][$status] ?? 0;
            $perStatusRows .= '<tr>'
                .'<td class="label">'.$this->esc((string) $status).'</td>'
                .'<td class="num">'.(int) $jumlah.'</td>'
                .'<td class="num">'.$this->rupiah($nilai).'</td>'
                .'</tr>';
        }

        $transaksiBody = '';

        if ($rows === []) {
            $transaksiBody = '<tr><td colspan="7" class="empty">'
                .'Tidak ada transaksi pada periode tersebut.'
                .'</td></tr>';
        } else {
            foreach ($rows as $index => $row) {
                $transaksiBody .= '<tr>'
                    .'<td class="num">'.($index + 1).'</td>'
                    .'<td>'.$this->esc((string) ($row['nomor_pesanan'] ?? '-')).'</td>'
                    .'<td>'.$this->esc((string) ($row['waktu'] ?? '-')).'</td>'
                    .'<td>'.$this->esc((string) ($row['jenis_transaksi'] ?? 'pembayaran')).'</td>'
                    .'<td>'.$this->esc((string) ($row['status_pembayaran'] ?? '-')).'</td>'
                    .'<td>'.$this->esc((string) ($row['provider'] ?? '-')).'</td>'
                    .'<td class="num">'.$this->rupiah($row['jumlah_pembayaran'] ?? 0).'</td>'
                    .'</tr>';
            }
        }

        $catatanDilewati = $dilewati > 0
            ? '<p class="note">Catatan: '.number_format($dilewati)
                .' baris transaksi terakhir tidak ditampilkan karena melebihi batas '
                .self::MAX_TRANSACTION_ROWS.' baris per berkas. Gunakan filter rentang '
                .'tanggal lebih sempit untuk melihat rinciannya.</p>'
            : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>{$this->esc($judul)}</title>
<style>
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; margin: 0; }
    h1 { font-size: 16px; margin: 0 0 2px 0; }
    h2 { font-size: 12px; margin: 16px 0 6px 0; border-bottom: 1px solid #333; padding-bottom: 3px; }
    .subtitle { color: #444; margin: 0 0 12px 0; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    th, td { border: 1px solid #999; padding: 4px 6px; text-align: left; vertical-align: top; }
    th { background: #eee; font-weight: bold; }
    td.label { width: 45%; }
    td.num, th.num { text-align: right; }
    td.empty { text-align: center; color: #666; font-style: italic; }
    .note { color: #555; font-size: 9px; margin: 4px 0 0 0; }
    .footer { margin-top: 18px; font-size: 9px; color: #666; border-top: 1px solid #999; padding-top: 4px; }
</style>
</head>
<body>
<h1>{$this->esc($judul)}</h1>
<p class="subtitle">Periode: {$this->esc($periode)}</p>

<h2>Ringkasan</h2>
<table>
<tbody>
{$periodeRows}<tr><td class="label">Total pendapatan</td><td>{$ringkasanRows[0][1]}</td></tr>
<tr><td class="label">Jumlah pembayaran berhasil</td><td>{$ringkasanRows[1][1]}</td></tr>
<tr><td class="label">Jumlah transaksi pembayaran</td><td>{$ringkasanRows[2][1]}</td></tr>
<tr><td class="label">Jumlah pesanan</td><td>{$ringkasanRows[3][1]}</td></tr>
<tr><td class="label">Total nilai pesanan</td><td>{$ringkasanRows[4][1]}</td></tr>
</tbody>
</table>
<p class="note">Catatan: "Total nilai pesanan" adalah akumulasi nilai pesanan, bukan laba bersih, karena data biaya dan pengeluaran belum tersedia.</p>

<h2>Pembayaran per Status</h2>
<table>
<thead><tr><th>Status</th><th class="num">Jumlah</th><th class="num">Nilai</th></tr></thead>
<tbody>{$perStatusRows}</tbody>
</table>

<h2>Buku Kas Transaksi</h2>
<table>
<thead>
<tr>
<th class="num">No</th>
<th>Nomor Pesanan</th>
<th>Waktu</th>
<th>Jenis</th>
<th>Status</th>
<th>Provider</th>
<th class="num">Nominal</th>
</tr>
</thead>
<tbody>{$transaksiBody}</tbody>
</table>
{$catatanDilewati}

<div class="footer">Dokumen dibuat pada {$this->esc(now()->format('Y-m-d H:i:s'))}oleh sistem Batik Pandansari.</div>
</body>
</html>
HTML;
    }

    /**
     * Mengubah nilai menjadi format rupiah tanpa simbol agar aman untuk
     * segala charset dan mudah dibaca mesin.
     */
    private function rupiah($nilai): string
    {
        return 'Rp '.number_format((float) $nilai, 2, ',', '.');
    }

    /**
     * Escape konten HTML agar data dari database tidak merusak struktur
     * dokumen (mencegah injeksi markup/Script).
     */
    private function esc(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
