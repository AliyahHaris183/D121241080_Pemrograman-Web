<?php
declare(strict_types=1);
 
/**
 * GuestBook - logika data untuk modul buku tamu perpustakaan.
 *
 * Tanggung jawab kelas ini:
 *  1. normalize() : merapikan masukan mentah (trim, pastikan bertipe string)
 *  2. validate()  : memeriksa aturan bisnis dan mengembalikan daftar galat
 *  3. save()      : menyimpan pesan   -> INSERT dengan prepared statement
 *  4. getAll()    : mengambil pesan   -> SELECT dengan prepared statement
 *
 * Koneksi PDO disuntikkan lewat konstruktor (dependency injection) dan
 * diasumsikan memakai PDO::ERRMODE_EXCEPTION (lihat Database.php).
 * Sanitasi KELUARAN (htmlspecialchars) sengaja dilakukan di lapisan tampilan
 * (guestbook.php), bukan di sini, agar data di basis data tetap apa adanya.
 */
class GuestBook
{
    public const NAMA_MAX  = 100;
    public const EMAIL_MAX = 254;
    public const PESAN_MIN = 5;
    public const PESAN_MAX = 1000;
 
    public function __construct(private PDO $pdo)
    {
    }
 
    /**
     * Merapikan masukan mentah (mis. $_POST). Nilai non-string, termasuk
     * array dari parameter seperti nama[]=x, diubah menjadi string kosong.
     *
     * @param  array<string, mixed>  $data
     * @return array{nama: string, email: string, pesan: string}
     */
    public static function normalize(array $data): array
    {
        $clean = static fn (string $key): string =>
            is_string($data[$key] ?? null) ? trim($data[$key]) : '';
 
        return [
            'nama'  => $clean('nama'),
            'email' => $clean('email'),
            'pesan' => $clean('pesan'),
        ];
    }