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