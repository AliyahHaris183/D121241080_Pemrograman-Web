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

/* ------------------------------------------------------------
 * 5. Ambil daftar pesan (SELECT)
 * ---------------------------------------------------------- */
$entries   = [];
$loadError = false;
 
try {
    $entries = $guestBook->getAll(50);
} catch (PDOException $e) {
    error_log('GuestBook load error: ' . $e->getMessage());
    $loadError = true;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Buku Tamu Perpustakaan</title>
    <style>
        :root { color-scheme: light dark; }
        body { font-family: system-ui, sans-serif; max-width: 860px; margin: 0 auto; padding: 24px 16px; line-height: 1.5; }
        h1 { margin-bottom: 4px; }
        .sub { margin-top: 0; opacity: .7; }
        form, .table-wrap { border: 1px solid #8884; border-radius: 8px; padding: 16px; margin-bottom: 24px; }
        label { display: block; font-weight: 600; margin-top: 12px; }
        input, textarea { width: 100%; box-sizing: border-box; padding: 8px; font: inherit; border: 1px solid #8888; border-radius: 6px; }
        textarea { min-height: 110px; resize: vertical; }
        button { margin-top: 16px; padding: 10px 20px; font: inherit; font-weight: 600; border: 0; border-radius: 6px; background: #1d4ed8; color: #fff; cursor: pointer; }
        .field-error { color: #c62828; font-size: .9rem; margin: 4px 0 0; }
        .alert { padding: 10px 14px; border-radius: 6px; margin-bottom: 16px; }
        .alert.ok  { background: #2e7d3226; border: 1px solid #2e7d32; }
        .alert.err { background: #c6282826; border: 1px solid #c62828; }
        .table-wrap { overflow-x: auto; padding: 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px 12px; text-align: left; vertical-align: top; border-bottom: 1px solid #8883; }
        th { background: #8882; }
        td.pesan { min-width: 220px; overflow-wrap: anywhere; }
    </style>
</head>
<body>
    <h1>Buku Tamu Perpustakaan</h1>
    <p class="sub">Tinggalkan kesan, saran, atau pertanyaan Anda.</p>
 
    <?php if ($success !== null): ?>
        <div class="alert ok" role="status"><?= e($success) ?></div>
    <?php endif; ?>
 
    <?php if (isset($errors['umum'])): ?>
        <div class="alert err" role="alert"><?= e($errors['umum']) ?></div>
    <?php endif; ?>
 
    <!-- novalidate: validasi otoritatif dilakukan di server (GuestBook::validate) -->
    <form method="post" action="guestbook.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
 
        <label for="nama">Nama</label>
        <input type="text" id="nama" name="nama" maxlength="<?= GuestBook::NAMA_MAX ?>" value="<?= e($old['nama']) ?>">
        <?php if (isset($errors['nama'])): ?><p class="field-error"><?= e($errors['nama']) ?></p><?php endif; ?>
 
        <label for="email">Email</label>
        <input type="email" id="email" name="email" maxlength="<?= GuestBook::EMAIL_MAX ?>" value="<?= e($old['email']) ?>">
        <?php if (isset($errors['email'])): ?><p class="field-error"><?= e($errors['email']) ?></p><?php endif; ?>
 
        <label for="pesan">Pesan</label>
        <textarea id="pesan" name="pesan" maxlength="<?= GuestBook::PESAN_MAX ?>"><?= e($old['pesan']) ?></textarea>
        <?php if (isset($errors['pesan'])): ?><p class="field-error"><?= e($errors['pesan']) ?></p><?php endif; ?>
 
        <button type="submit">Kirim Pesan</button>
    </form>
 
    <h2>Daftar Pesan</h2>
 
    <?php if ($loadError): ?>
        <div class="alert err" role="alert">Daftar pesan tidak dapat dimuat saat ini.</div>
    <?php elseif ($entries === []): ?>
        <p>Belum ada pesan. Jadilah yang pertama!</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>No</th><th>Nama</th><th>Email</th><th>Pesan</th><th>Tanggal Kirim</th></tr>
                </thead>
                <tbody>
                <?php foreach ($entries as $i => $row): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= e($row['nama']) ?></td>
                        <td><?= e(maskEmail($row['email'])) ?></td>
                        <td class="pesan"><?= nl2br(e($row['pesan'])) ?></td>
                        <td><?= e((new DateTimeImmutable($row['tanggal_kirim']))->format('d-m-Y H:i')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</body>
</html>