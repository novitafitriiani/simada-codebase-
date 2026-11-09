<?php

class DashboardController extends Controller
{
    public function index(): void
    {
        Auth::requireRole();
        $semua = (new PaketPengadaan())->withKetepatan();
        $count = fn($s) => count(array_filter($semua, fn($p) => $p['status'] === $s));
        $kt    = fn($k) => count(array_filter($semua, fn($p) => ($p['ket']['kode'] ?? '') === $k));

        // Filter dashboard: jenis paket & status ketepatan waktu
        $fJenis  = in_array($_GET['jenis'] ?? '', PaketPengadaan::JENIS, true) ? $_GET['jenis'] : '';
        $fStatus = in_array($_GET['ket'] ?? '', ['cepat', 'tepat', 'delay'], true) ? $_GET['ket'] : '';
        $paket = array_values(array_filter($semua, fn($p) =>
            ($fJenis === '' || $p['jenis_paket'] === $fJenis) && ($fStatus === '' || ($p['ket']['kode'] ?? '') === $fStatus)));

        // Kelompokkan per tahap: 0 = belum dimulai, 1..6 = tahap baku
        $perTahap = array_fill(0, count(Tender::TAHAP) + 1, []);
        foreach ($paket as $p) $perTahap[$p['pos']['no']][] = $p;

        // Rekap per jenis paket
        $rekap = [];
        foreach (PaketPengadaan::JENIS as $j) {
            $x = array_filter($semua, fn($p) => $p['jenis_paket'] === $j);
            $c = fn($k) => count(array_filter($x, fn($p) => ($p['ket']['kode'] ?? '') === $k));
            $rekap[$j] = ['total' => count($x), 'cepat' => $c('cepat'), 'tepat' => $c('tepat'), 'delay' => $c('delay')];
        }

        $this->render('dashboard/index', [
            'aktif' => count($semua) - $count('Selesai'), 'tender' => $count('Berjalan'), 'selesai' => $count('Selesai'),
            'totalUser' => (new User())->countAll(),
            'cepat' => $kt('cepat'), 'tepat' => $kt('tepat'), 'delay' => $kt('delay'),
            'perTahap' => $perTahap, 'rekap' => $rekap, 'fJenis' => $fJenis, 'fStatus' => $fStatus,
            'terbaru' => array_slice($semua, 0, 5),
        ], 'dashboard');
    }
}
