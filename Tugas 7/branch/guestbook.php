<?php
declare(strict_types=1);
 
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/GuestBook.php';
 
/* ------------------------------------------------------------
 * 1. Header keamanan dan sesi
 * ---------------------------------------------------------- */
header('X-Content-Type-Options: nosniff');
// Tanpa JavaScript; hanya CSS inline di halaman ini. Mengurangi dampak XSS bila ada celah.
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; form-action 'self'; frame-ancestors 'none'");
 
ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => !empty($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
 
/* ------------------------------------------------------------
 * 2. Fungsi bantu
 * ---------------------------------------------------------- */
 
/** Sanitasi keluaran: semua data dari pengguna/DB wajib melewati fungsi ini sebelum dicetak. */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
 
/** Menyamarkan email di tampilan publik: budi@mail.com -> b***@mail.com */
function maskEmail(string $email): string
{
    [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
 
    return mb_substr($local, 0, 1) . '***@' . $domain;
}
 
function newCsrfToken(): string
{
    return bin2hex(random_bytes(32));
}
 
/* ------------------------------------------------------------
 * 3. Inisialisasi
 * ---------------------------------------------------------- */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = newCsrfToken();
}
 
$guestBook = new GuestBook((new Database())->getConnection());
 
$errors  = [];
$old     = ['nama' => '', 'email' => '', 'pesan' => ''];
$success = $_SESSION['flash'] ?? null; // pesan sukses satu kali tampil
unset($_SESSION['flash']);
 
/* ------------------------------------------------------------
 * 4. Pemrosesan formulir (POST)
 * ---------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
 
    // Pertahanan 1: token CSRF (hash_equals mencegah timing attack)
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        $errors['umum'] = 'Sesi formulir tidak valid atau sudah kedaluwarsa. Muat ulang halaman, lalu coba lagi.';
    } else {
        $old    = GuestBook::normalize($_POST);
        $errors = $guestBook->validate($old);
 
        if ($errors === []) {
            try {
                $guestBook->save($old['nama'], $old['email'], $old['pesan']);
 
                // Token diganti setelah dipakai, lalu redirect (Post/Redirect/Get)
                // agar refresh browser tidak mengirim ulang pesan yang sama.
                $_SESSION['csrf_token'] = newCsrfToken();
                $_SESSION['flash']      = 'Terima kasih! Pesan Anda berhasil dikirim.';
                header('Location: guestbook.php');
                exit;
            } catch (PDOException $e) {
                error_log('GuestBook save error: ' . $e->getMessage());
                http_response_code(500);
                $errors['umum'] = 'Pesan belum dapat disimpan. Silakan coba beberapa saat lagi.';
            }
        }
    }
}