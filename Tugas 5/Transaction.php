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