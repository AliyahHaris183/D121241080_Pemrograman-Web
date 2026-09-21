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