<?php

declare(strict_types=1);

require_once './Transaction.php';

session_start();

// Inisialisasi state session bila belum ada.
if (!isset($_SESSION['balance'])) {
    $_SESSION['balance'] = 0.0;
}

if (!isset($_SESSION['transactions'])) {
    $_SESSION['transactions'] = [];
}

// Bangkitkan CSRF token bila belum ada.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifikasi token CSRF (hash_equals = tahan timing attack).
    $postToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $postToken)) {
        die('Kesalahan Keamanan: Token CSRF tidak cocok.');
    }

    $type = $_POST['type'] ?? '';
    $rawAmount = trim($_POST['amount'] ?? '');

    // Validasi jenis transaksi pakai match.
    $validType = match ($type) {
        'deposit', 'withdrawal' => true,
        default => false,
    };

    if (!$validType) {
        $errors[] = 'Jenis transaksi tidak valid. Pilih deposit atau penarikan.';
    }

    // Validasi jumlah: harus angka desimal positif.
    if (!preg_match('/^\d+(\.\d{1,2})?$/', $rawAmount)) {
        $errors[] = 'Jumlah transaksi harus berupa angka desimal positif.';
    } else {
        $amount = (float) $rawAmount;
        if ($amount <= 0.0) {
            $errors[] = 'Jumlah transaksi harus lebih besar dari nol.';
        }
    }

    // Proses transaksi bila lolos validasi.
    if (empty($errors)) {
        $transaction = new Transaction(
            id: bin2hex(random_bytes(8)),
            type: $type,
            amount: $amount
        );

        if ($transaction->process()) {
            $success = 'Transaksi berhasil diproses.';
        } else {
            $errors[] = 'Saldo tidak mencukupi untuk melakukan penarikan.';
        }

        // Regenerasi token CSRF setelah pemrosesan.
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Sistem Manajemen Keuangan Sederhana</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 640px; margin: 40px auto; padding: 0 16px; }
        h1 { font-size: 1.4rem; }
        .balance { font-size: 1.2rem; font-weight: bold; margin: 16px 0; }
        form { margin: 24px 0; padding: 16px; border: 1px solid #ccc; border-radius: 6px; }
        label { display: block; margin-top: 12px; font-weight: bold; }
        select, input[type="text"] { width: 100%; padding: 8px; margin-top: 4px; box-sizing: border-box; }
        button { margin-top: 16px; padding: 10px 16px; cursor: pointer; }
        .errors { color: #b00020; }
        .success { color: #0a7a2b; }
        ul.history { list-style: none; padding: 0; }
        ul.history li { padding: 8px; border-bottom: 1px solid #eee; }
        .type-deposit { color: #0a7a2b; }
        .type-withdrawal { color: #b00020; }
    </style>
</head>
<body>
    <h1>Sistem Manajemen Keuangan Sederhana</h1>

    <!-- Saldo dicetak aman dengan htmlspecialchars -->
    <div class="balance">
        Saldo saat ini: Rp<?= htmlspecialchars(number_format((float) $_SESSION['balance'], 2, ',', '.')) ?>
    </div>

    <?php if ($success !== null): ?>
        <p class="success"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <ul class="errors">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="finance.php">
        <!-- Token CSRF tersembunyi, diverifikasi saat POST -->
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

        <label for="type">Jenis Transaksi</label>
        <select id="type" name="type" required>
            <option value="deposit">Deposit</option>
            <option value="withdrawal">Penarikan</option>
        </select>

        <label for="amount">Jumlah (Rp)</label>
        <input type="text" id="amount" name="amount" placeholder="Contoh: 150000.00" required>

        <button type="submit">Proses Transaksi</button>
    </form>