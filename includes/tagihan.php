<?php

// Status & pembebanan tagihan (tagihan): label, warna badge, info tenggat, dan penagihan anggota.

// Satu kosakata status di seluruh UI. Enum DB tetap (Belum_Bayar, Menunggu, Lunas, Ditolak, Disetujui).
function label_status($status)
{
    $label = [
        'Belum_Bayar' => 'Belum bayar',
        'Menunggu' => 'Menunggu verifikasi',
        'Lunas' => 'Lunas',
        'Ditolak' => 'Ditolak',
        'Disetujui' => 'Disetujui',
    ];

    return $label[(string) $status] ?? str_replace('_', ' ', (string) $status);
}

// Warna badge untuk status pembayaran (Disetujui/Ditolak/Menunggu) dan status tagihan anggota.
function kelas_status($status)
{
    $kelas = [
        'Lunas' => 'success',
        'Disetujui' => 'success',
        'Ditolak' => 'danger',
        'Menunggu' => 'warning',
    ];

    return $kelas[(string) $status] ?? '';
}

// Jarak tenggat dari hari ini: ['teks' => 'Lewat 3 hari' | 'Hari ini' | '5 hari lagi', 'lewat' => bool].
function info_tenggat($tenggat)
{
    $hari = (int) floor((strtotime((string) $tenggat) - strtotime(date('Y-m-d'))) / 86400);

    if ($hari < 0) {
        return ['teks' => 'Lewat ' . abs($hari) . ' hari', 'lewat' => true];
    }

    return ['teks' => $hari === 0 ? 'Hari ini' : $hari . ' hari lagi', 'lewat' => false];
}

// Bebankan satu tagihan ke semua anggota grup yang belum tercatat. Mengembalikan jumlah anggota yang ditambahkan.
function tagihkan_ke_anggota($pdo, $id_tagihan, $id_grup)
{
    $stmt = $pdo->prepare("
        INSERT INTO tagihan_anggota (id_tagihan, id_pengguna)
        SELECT ?, id_pengguna FROM anggota_grup WHERE id_grup = ?
        ON CONFLICT (id_tagihan, id_pengguna) DO NOTHING
    ");
    $stmt->execute([$id_tagihan, $id_grup]);

    return $stmt->rowCount();
}

// Anggota yang baru gabung ikut dibebani tagihan grup yang tenggatnya belum lewat.
// Anggota yang keluar lalu gabung lagi tetap memakai status lamanya (ON CONFLICT).
function tagihkan_tagihan_berjalan($pdo, $id_grup, $id_pengguna)
{
    $stmt = $pdo->prepare("
        INSERT INTO tagihan_anggota (id_tagihan, id_pengguna)
        SELECT id, ? FROM tagihan WHERE id_grup = ? AND tenggat_waktu >= CURRENT_DATE
        ON CONFLICT (id_tagihan, id_pengguna) DO NOTHING
    ");
    $stmt->execute([$id_pengguna, $id_grup]);

    return $stmt->rowCount();
}
