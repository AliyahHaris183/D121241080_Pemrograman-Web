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

    /**
     * Validasi masukan. Hasil kosong berarti semua data valid.
     *
     * @param  array{nama: string, email: string, pesan: string}  $data
     * @return array<string, string>  [nama_field => pesan galat]
     */
    public function validate(array $data): array
    {
        $errors = [];
 
        // Nama: tidak boleh kosong
        if ($data['nama'] === '') {
            $errors['nama'] = 'Nama tidak boleh kosong.';
        } elseif (mb_strlen($data['nama']) > self::NAMA_MAX) {
            $errors['nama'] = 'Nama maksimal ' . self::NAMA_MAX . ' karakter.';
        }
 
        // Email: format valid
        if (filter_var($data['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Format email tidak valid.';
        } elseif (mb_strlen($data['email']) > self::EMAIL_MAX) {
            $errors['email'] = 'Email maksimal ' . self::EMAIL_MAX . ' karakter.';
        }
 
        // Pesan: minimal lima karakter (mb_strlen agar karakter UTF-8 dihitung benar)
        if (mb_strlen($data['pesan']) < self::PESAN_MIN) {
            $errors['pesan'] = 'Pesan minimal ' . self::PESAN_MIN . ' karakter.';
        } elseif (mb_strlen($data['pesan']) > self::PESAN_MAX) {
            $errors['pesan'] = 'Pesan maksimal ' . self::PESAN_MAX . ' karakter.';
        }
 
        return $errors;
    }