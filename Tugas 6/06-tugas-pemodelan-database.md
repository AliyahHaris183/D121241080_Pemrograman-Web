# Tugas Mandiri Modul 6: Perancangan Basis Data E-Library Kampus (Normalisasi hingga 3NF)

| | |
| --- | --- |
| **Nama** | [Isi nama lengkap] |
| **NIM** | [Isi NIM] |
| **Mata Kuliah** | Pemrograman Website |
| **Modul** | 6 — Pemodelan Data dan Konsep Basis Data Relasional |
| **Institusi** | Departemen Teknik Informatika, Fakultas Teknik, Universitas Hasanuddin |

## Daftar Isi

1. [Skenario dan Ruang Lingkup](#1-skenario-dan-ruang-lingkup)
2. [Asumsi dan Aturan Bisnis](#2-asumsi-dan-aturan-bisnis)
3. [Desain ERD Logis: Entitas Inti sesuai Spesifikasi](#3-desain-erd-logis-entitas-inti-sesuai-spesifikasi)
4. [Simulasi Normalisasi Bertahap (UNF sampai 3NF)](#4-simulasi-normalisasi-bertahap-unf-sampai-3nf)
5. [Rancangan Tabel Akhir (Skema 3NF Lengkap)](#5-rancangan-tabel-akhir-skema-3nf-lengkap)
6. [Aturan Integritas Referensial](#6-aturan-integritas-referensial)
7. [Visualisasi Relasi Kunci](#7-visualisasi-relasi-kunci)
8. [Verifikasi 3NF per Tabel](#8-verifikasi-3nf-per-tabel)
9. [Referensi](#9-referensi)

---

## 1. Skenario dan Ruang Lingkup

Sistem **E-Library Kampus** mengelola peminjaman buku perpustakaan kampus. Spesifikasi tugas meminta perancangan ERD logis untuk empat entitas inti: **Mahasiswa**, **Buku**, **Penerbit**, dan **Transaksi Peminjaman**, mencakup pencatatan riwayat peminjaman dan pengembalian.

Dokumen ini sengaja tidak mengulang definisi teoritis ACID/model relasional dari slide modul, karena hal itu bukan bagian dari kriteria spesifikasi tugas. Fokus dokumen ini murni pada lima poin yang diminta: desain ERD, identifikasi atribut/kunci, simulasi normalisasi, rancangan tabel akhir, dan visualisasi relasi kunci.

Bagian 3 merancang keempat entitas tersebut persis sesuai spesifikasi. Bagian 4 kemudian menyimulasikan normalisasi dari data gabungan keempat entitas itu. Dari sanalah kebutuhan tabel pendukung (program studi, fakultas, penulis, eksemplar fisik, denda) **terungkap secara alami** melalui analisis ketergantungan fungsional, bukan ditambahkan begitu saja di awal tanpa alasan.