<?php

declare(strict_types=1);

/**
 * Merepresentasikan satu transaksi keuangan (deposit/withdrawal).
 * Properti private + akses lewat method = enkapsulasi.
 */
class Transaction
{
    // Constructor Property Promotion (PHP 8.x): deklarasi + inisialisasi
    // properti private langsung di parameter constructor.
    public function __construct(
        private string $id,
        private string $type,
        private float $amount
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * Memproses transaksi terhadap saldo di session.
     * Menolak penarikan bila saldo tidak cukup.
     */
    public function process(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['balance'])) {
            $_SESSION['balance'] = 0.0;
        }

        $currentBalance = (float) $_SESSION['balance'];

                // match: cocokkan jenis transaksi secara ketat, tanpa break.
        $success = match ($this->type) {
            'deposit' => $this->processDeposit($currentBalance),
            'withdrawal' => $this->processWithdrawal($currentBalance),
            default => false,
        };

        // Catat ke riwayat hanya jika berhasil.
        if ($success) {
            $_SESSION['transactions'][] = [
                'id' => $this->id,
                'type' => $this->type,
                'amount' => $this->amount,
                'balance_after' => $_SESSION['balance'],
                'time' => date('Y-m-d H:i:s'),
            ];
        }

        return $success;
    }

    // Deposit selalu berhasil, saldo bertambah.
    private function processDeposit(float $currentBalance): bool
    {
        $_SESSION['balance'] = $currentBalance + $this->amount;

        return true;
    }

    // Penarikan ditolak jika nominal melebihi saldo.
    private function processWithdrawal(float $currentBalance): bool
    {
        if ($this->amount > $currentBalance) {
            return false;
        }

        $_SESSION['balance'] = $currentBalance - $this->amount;

        return true;
    }
}