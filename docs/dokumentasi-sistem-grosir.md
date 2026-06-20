# Dokumentasi & Roadmap — Sistem Manajemen Grosir

> Stack: **Laravel + Filament + MySQL** · Tipe: aplikasi internal (admin-only) · Pembayaran langsung (tanpa tempo) · Pengiriman armada sendiri
>
> Dokumen ini dipakai sebagai **peta jalan pembangunan** sekaligus **konteks untuk vibe coding bareng Claude**. Bangun **satu fitur per sesi**, ikuti standar di bagian 3–7, lalu centang progres di bagian 11.
>
> Catatan versi: gunakan **versi stabil terbaru** Laravel & Filament saat memulai (cek di laravel.com dan filamentphp.com). Konsep di dokumen ini tidak bergantung pada nomor versi.

---

## Daftar Isi
1. [Ringkasan Proyek](#1-ringkasan-proyek)
2. [Tech Stack & Tooling](#2-tech-stack--tooling)
3. [Arsitektur & Struktur Project](#3-arsitektur--struktur-project)
4. [Struktur Database](#4-struktur-database)
5. [Standar Kode (Clean Code)](#5-standar-kode-clean-code)
6. [Standar Performa & Query (Anti-Ngelag)](#6-standar-performa--query-anti-ngelag)
7. [Standar UI/UX (Desktop + Mobile)](#7-standar-uiux-desktop--mobile)
8. [Roadmap Fase (0 → Semua Fitur)](#8-roadmap-fase-0--semua-fitur)
9. [Definition of Done (per fitur)](#9-definition-of-done-per-fitur)
10. [Panduan Vibe Coding dengan Claude](#10-panduan-vibe-coding-dengan-claude)
11. [Checklist Progres](#11-checklist-progres)

---

## 1. Ringkasan Proyek

### Tujuan
Sistem internal untuk mengelola operasional toko grosir: data produk multi-satuan, stok, transaksi penjualan, restock dari supplier, pengiriman armada sendiri, dan laporan. Dipakai oleh staf toko (bukan pelanggan).

### Pengguna & Peran
- **Owner** — akses penuh, fokus laporan & laba.
- **Admin** — kelola master data & transaksi.
- **Kasir** — input penjualan & pembayaran.
- **Gudang** — stok masuk, stock opname, siapkan barang.
- **Sopir** — lihat tugas kirim, update status (opsional, fase lanjutan).

### Tiga Prinsip Data (jangan dilanggar)
1. **Stok disimpan dalam satuan terkecil.** Semua stok dihitung dalam satuan dasar (mis. `pcs`). Jual/beli per dus → dikali `konversi`. Angka stok selalu konsisten.
2. **Harga di-"foto" saat transaksi.** Harga jual & beli disalin ke baris transaksi (`detail_*`), bukan diambil ulang dari master. Nota lama harus tetap memakai harga lamanya.
3. **Semua pergerakan stok masuk satu buku besar (`mutasi_stok`).** Tiap masuk/keluar/retur/penyesuaian menulis satu baris ledger. Stok bisa selalu ditelusuri & diaudit.

### Sasaran Non-Fungsional
- **Clean code & clean architecture** — logika bisnis terpisah, mudah dirawat.
- **Cepat, tidak ngelag** — query teroptimasi, search instan (bagian 6).
- **UI/UX ramah** — jelas, cepat, **desktop & mobile friendly** (bagian 7).

---

## 2. Tech Stack & Tooling

| Komponen | Pilihan | Catatan |
|---|---|---|
| Framework | Laravel (terbaru) | Inti aplikasi |
| Admin Panel | Filament (terbaru) | Semua UI CRUD, resource, widget, form |
| Database | MySQL 8 / MariaDB | Relational, dukung FULLTEXT index |
| Cache / Queue / Session | Redis | Dipasang sejak awal, dipakai serius di fase optimasi |
| Auth & Roles | `spatie/laravel-permission` + `bezhanSalleh/filament-shield` | Hak akses per peran |
| Activity Log | `spatie/laravel-activitylog` | Audit perubahan data |
| PDF (surat jalan/invoice) | `barryvdh/laravel-dompdf` (ringan) atau `spatie/laravel-pdf` (presisi) | Pilih salah satu |
| Static Analysis | Larastan (PHPStan) | Tangkap bug sebelum runtime |
| Formatting | Laravel Pint | Format kode otomatis (PSR-12) |
| Testing | Pest | Test fitur kritis (stok, transaksi) |
| Asset build | Vite | Bawaan Laravel |

**Paket opsional (fase lanjutan):** Laravel Scout + Meilisearch/Typesense (search instan), Laravel Octane + FrankenPHP (throughput), Filament native Export Action (Excel/CSV).

---

## 3. Arsitektur & Struktur Project

**Prinsip:** Filament Resource & Page hanya untuk **tampilan + orkestrasi**. **Logika bisnis hidup di Action class.** Model untuk relasi & data, bukan tempat menumpuk logika.

### Struktur Folder
```
app/
├── Actions/                  # Logika bisnis (1 action = 1 use case)
│   ├── Sales/
│   │   ├── CreateSaleAction.php
│   │   └── CancelSaleAction.php
│   ├── Purchasing/
│   │   └── ReceiveStockAction.php
│   └── Stock/
│       └── RecordStockMovementAction.php
├── Enums/                    # Status & tipe (native PHP enum)
│   ├── StockMovementType.php
│   ├── PaymentMethod.php
│   └── DeliveryStatus.php
├── Models/                   # Eloquent + relasi + scope
├── Support/                  # Helper, value object (mis. konversi satuan)
├── Filament/
│   ├── Resources/            # CRUD per entitas
│   ├── Pages/                # Halaman custom (mis. kasir cepat)
│   └── Widgets/              # Widget dashboard
└── Policies/                 # Aturan akses per model
database/
├── migrations/
├── factories/
└── seeders/
tests/
```

### Aturan Pembagian Tanggung Jawab
- **Action class** — operasi yang mengubah data penting (buat penjualan, terima stok, retur). Selalu `DB::transaction()` kalau menyentuh > 1 tabel atau mengubah stok.
- **Model** — relasi, `casts` (termasuk cast ke Enum), query scope reusable (`scopeActive`, `scopeLowStock`), accessor turunan (mis. `total`).
- **Enum** — semua status string jadi enum, jangan magic string. Contoh:

```php
enum StockMovementType: string
{
    case Masuk = 'masuk';
    case Keluar = 'keluar';
    case ReturMasuk = 'retur_masuk';
    case Penyesuaian = 'penyesuaian';

    public function isInbound(): bool
    {
        return in_array($this, [self::Masuk, self::ReturMasuk]);
    }
}
```

- **Form Request / validasi Filament** — validasi input di pintu masuk, jangan di tengah logika.
- **Policy** — keputusan "boleh/tidak" terpusat, dipakai Filament otomatis.

### Contoh Action Inti (referensi pola)
```php
// app/Actions/Sales/CreateSaleAction.php
class CreateSaleAction
{
    public function __construct(
        private RecordStockMovementAction $recordMovement,
    ) {}

    public function execute(Pelanggan $pelanggan, array $items, PaymentMethod $metode): Penjualan
    {
        return DB::transaction(function () use ($pelanggan, $items, $metode) {
            $penjualan = Penjualan::create([
                'pelanggan_id'  => $pelanggan->id,
                'tanggal'       => now(),
                'metode_bayar'  => $metode,
                'status_kirim'  => DeliveryStatus::SiapKirim,
                'total'         => 0,
            ]);

            foreach ($items as $item) {
                $satuan  = SatuanProduk::with('produk')->findOrFail($item['satuan_id']);
                $qtyBase = $item['qty'] * $satuan->konversi;

                $penjualan->details()->create([
                    'produk_id'     => $satuan->produk_id,
                    'satuan_id'     => $satuan->id,
                    'qty'           => $item['qty'],
                    'harga_satuan'  => $satuan->harga_jual, // di-"foto"
                    'subtotal'      => $item['qty'] * $satuan->harga_jual,
                ]);

                $this->recordMovement->execute(
                    produk: $satuan->produk,
                    tipe: StockMovementType::Keluar,
                    qtyBase: $qtyBase,
                    referensi: "penjualan:{$penjualan->id}",
                );
            }

            $penjualan->update(['total' => $penjualan->details()->sum('subtotal')]);
            return $penjualan;
        });
    }
}
```

```php
// app/Actions/Stock/RecordStockMovementAction.php
class RecordStockMovementAction
{
    public function execute(Produk $produk, StockMovementType $tipe, int $qtyBase, string $referensi): MutasiStok
    {
        $delta = $tipe->isInbound() ? $qtyBase : -$qtyBase;
        $stokSebelum = $produk->stok;

        $produk->increment('stok', $delta); // operasi atomik, aman dari race condition

        return MutasiStok::create([
            'produk_id'     => $produk->id,
            'tipe'          => $tipe,
            'qty'           => $qtyBase,
            'referensi'     => $referensi,
            'stok_sebelum'  => $stokSebelum,
            'stok_sesudah'  => $stokSebelum + $delta,
        ]);
    }
}
```

---

## 4. Struktur Database

Semua tabel pakai `id` bigint auto-increment + `created_at`/`updated_at`. Kolom uang pakai tipe `decimal(15,2)`. Status pakai kolom string yang dipetakan ke Enum.

### Tabel

**kategori**
| Kolom | Tipe | Index |
|---|---|---|
| id | bigint PK | |
| nama | string | |

**produk**
| Kolom | Tipe | Index |
|---|---|---|
| id | bigint PK | |
| kategori_id | bigint FK | index |
| nama | string | FULLTEXT (search) |
| satuan_dasar | string | mis. "pcs" |
| stok | int | jumlah dalam satuan dasar |
| stok_min | int | ambang alert |
| harga_beli | decimal(15,2) | HPP terakhir |
| aktif | boolean | |

**satuan_produk** (jantung multi-satuan)
| Kolom | Tipe | Index |
|---|---|---|
| id | bigint PK | |
| produk_id | bigint FK | index |
| nama_satuan | string | unique(produk_id, nama_satuan) |
| konversi | int | jumlah satuan dasar per 1 unit ini (pcs=1, dus=24) |
| harga_jual | decimal(15,2) | per unit ini |

**pelanggan**
| Kolom | Tipe | Index |
|---|---|---|
| id | bigint PK | |
| nama_toko | string | FULLTEXT (search) |
| nama_kontak | string nullable | |
| telepon | string | |
| alamat | text | |

**supplier**: id, nama (FULLTEXT), telepon, alamat.
**sopir**: id, nama, telepon.

**penjualan**
| Kolom | Tipe | Index |
|---|---|---|
| id | bigint PK | |
| no_invoice | string | unique |
| pelanggan_id | bigint FK | index |
| sopir_id | bigint FK nullable | index |
| tanggal | datetime | index (laporan) |
| total | decimal(15,2) | |
| metode_bayar | string (Enum) | |
| status_kirim | string (Enum) | index |
| catatan | text nullable | |

**detail_penjualan**
| Kolom | Tipe | Index |
|---|---|---|
| id | bigint PK | |
| penjualan_id | bigint FK | index |
| produk_id | bigint FK | index |
| satuan_id | bigint FK | index |
| qty | int | |
| harga_satuan | decimal(15,2) | snapshot |
| subtotal | decimal(15,2) | |

**pembelian**: id, no_pembelian (unique), supplier_id (index), tanggal (index), total.
**detail_pembelian**: id, pembelian_id (index), produk_id (index), qty, harga_beli, subtotal.

**mutasi_stok** (buku besar)
| Kolom | Tipe | Index |
|---|---|---|
| id | bigint PK | |
| produk_id | bigint FK | composite index (produk_id, created_at) |
| tipe | string (Enum) | index |
| qty | int | dalam satuan dasar |
| referensi | string | mis. "penjualan:12" |
| stok_sebelum | int | |
| stok_sesudah | int | |

### Relasi
- `kategori` 1—* `produk`
- `produk` 1—* `satuan_produk`, 1—* `mutasi_stok`, 1—* `detail_penjualan`, 1—* `detail_pembelian`
- `pelanggan` 1—* `penjualan`; `sopir` 1—* `penjualan`
- `penjualan` 1—* `detail_penjualan`
- `supplier` 1—* `pembelian`; `pembelian` 1—* `detail_pembelian`

### Strategi Index (fondasi anti-ngelag)
- **Semua foreign key di-index** (Laravel `foreignId()->constrained()` membuat index otomatis).
- **Kolom yang sering difilter/sort**: `penjualan.tanggal`, `penjualan.status_kirim`, `produk.kategori_id`.
- **Composite index** untuk pola query gabungan: `mutasi_stok (produk_id, created_at)` → riwayat per produk cepat.
- **FULLTEXT index** di kolom yang dicari: `produk.nama`, `pelanggan.nama_toko`, `supplier.nama` (lihat bagian 6 soal search).

---

## 5. Standar Kode (Clean Code)

- **PSR-12**, dirapikan otomatis pakai **Pint** sebelum commit.
- **`declare(strict_types=1)`** di semua file PHP. Type hint argumen & return lengkap.
- **Nama jelas & konsisten** — tabel & kolom `snake_case`, class `PascalCase`, method `camelCase`. Istilah domain pakai Bahasa Indonesia (Penjualan, Pelanggan), istilah teknis Inggris (Action, Service).
- **Controller/Resource tipis** — tidak ada logika bisnis di sana; delegasikan ke Action.
- **Satu Action = satu use case.** Method publik `execute()`.
- **Validasi di pintu masuk** (Form Request / schema Filament), bukan di tengah logika.
- **Enum, bukan magic string.** Status & tipe selalu enum + `casts`.
- **DRY via query scope & helper** — query yang berulang jadi scope (`Produk::active()`), konversi satuan jadi helper/value object.
- **Tidak ada query di Blade/view.** Data disiapkan di backend, view hanya menampilkan.
- **Migrasi yang reversible** — selalu isi `down()`.
- **Komentar seperlunya** — kode jelas > komentar banyak. Komentari "kenapa", bukan "apa".
- **Larastan level menengah** dijalankan rutin; perbaiki temuan.
- **Test untuk jalur kritis**: pembuatan penjualan mengurangi stok dengan benar, retur menambah stok, transaksi rollback saat gagal.

---

## 6. Standar Performa & Query (Anti-Ngelag)

> Di skala satu toko, lag hampir selalu datang dari **query yang tidak rapi**, bukan dari Laravel. Patuhi aturan ini dan aplikasi tetap ngebut.

### Aturan Wajib
1. **Eager loading — basmi N+1.** Setiap menampilkan relasi, pakai `with()`. Aktifkan `Model::preventLazyLoading()` di environment lokal agar N+1 langsung ketahuan sebagai error saat development.
   ```php
   Penjualan::query()
       ->with(['pelanggan', 'sopir', 'details.produk', 'details.satuan'])
       ->latest('tanggal')
       ->paginate(25);
   ```
2. **Selalu paginasi list panjang.** Jangan pernah `->get()` semua baris untuk ditampilkan. Filament sudah paginasi tabel secara default — jangan dimatikan.
3. **Ambil kolom seperlunya** untuk list besar: `->select(['id', 'nama', 'stok'])`.
4. **Operasi stok pakai operasi atomik** (`increment`/`decrement`) di dalam `DB::transaction()` — konsisten & aman dari race condition. Jangan baca-lalu-tulis manual.
5. **Index sesuai pola query** (lihat bagian 4). Tinjau ulang index tiap menambah filter/sort baru.
6. **Agregasi berat dihitung efisien**: `withCount()`, `withSum()` daripada loop PHP. Untuk dashboard, **cache** hasilnya.
7. **Pekerjaan berat masuk queue** (generate PDF, kirim notifikasi, export besar) — jangan blokir request.

### Strategi Search (harus cepat)
Masalah klasik: `LIKE '%kata%'` dengan wildcard di depan **tidak bisa pakai index** → lambat saat data banyak. Solusi bertingkat:

- **Awal (data masih kecil):** Filament `->searchable()` sudah cukup. Pasang **debounce** agar tidak query tiap ketikan:
  ```php
  $table->searchDebounce('500ms');
  ```
- **Data tumbuh (ribuan+ baris):** pakai **MySQL FULLTEXT index** di kolom yang dicari, lalu cari dengan relevansi:
  ```php
  // migration
  $table->fullText('nama');

  // query
  Produk::query()
      ->whereFullText('nama', $term)
      ->limit(20)
      ->get();
  ```
- **Butuh search instan & toleran typo (skala besar):** pasang **Laravel Scout + Meilisearch/Typesense**. Hasil sub-100ms + typo tolerance. Ini fase optimasi, bukan MVP.

### Caching (fase optimasi)
- Pakai **Redis** untuk cache, queue, session.
- Cache angka dashboard/laporan yang mahal, invalidasi saat transaksi baru masuk (event/observer).
- Pertimbangkan **Laravel Octane + FrankenPHP** hanya jika throughput benar-benar jadi isu (kemungkinan kecil untuk satu toko).

---

## 7. Standar UI/UX (Desktop + Mobile)

> Filament responsif secara bawaan (sidebar bisa collapse, tabel adaptif). Tugas kita: **memanfaatkannya dengan benar** agar enak di desktop maupun HP.

### Prinsip Umum
- **Bahasa Indonesia** untuk semua label, tombol, pesan, notifikasi. Konsisten istilahnya.
- **Aksi utama gampang dijangkau**, langkah sesedikit mungkin (kasir butuh kecepatan).
- **Feedback cepat**: notifikasi sukses/gagal, konfirmasi untuk aksi berisiko (batal transaksi, hapus).
- **Empty state & loading state** yang jelas.
- **Validasi inline** dengan pesan yang manusiawi.

### Khusus Mobile
- **Sembunyikan kolom sekunder di layar kecil** agar tabel tidak penuh sesak:
  ```php
  Tables\Columns\TextColumn::make('alamat')->visibleFrom('md');
  Tables\Columns\TextColumn::make('telepon')->toggleable(isToggledHiddenByDefault: true);
  ```
- **Target sentuh cukup besar**, hindari tombol mungil berdempetan.
- **Form panjang pakai layout responsif** (`->columns(['default' => 1, 'md' => 2])`) — satu kolom di HP, dua kolom di desktop.
- Uji benar-benar di lebar layar HP, bukan cuma di-resize browser.

### Kecepatan yang Terasa di UI
- **Debounce search** (bagian 6) agar tidak nge-lag saat mengetik.
- **Filter cepat** (status kirim, kategori, rentang tanggal) sebagai `Tables\Filters`.
- **Default sort** yang masuk akal (transaksi terbaru di atas).
- **Global search** Filament untuk lompat cepat antar entitas.

---

## 8. Roadmap Fase (0 → Semua Fitur)

Kerjakan berurutan. **MVP dianggap jadi di akhir Fase 4.** Fase 5–7 menyelesaikan seluruh fitur.

### Fase 0 — Setup & Fondasi
**Tujuan:** project siap dibangun dengan standar terpasang.
- Install Laravel + Filament, koneksi MySQL, panel admin & login.
- Pasang Pint, Larastan, Pest. Inisialisasi Git + `.gitignore`.
- Aktifkan `preventLazyLoading()` di lokal.
- Siapkan base layout, tema, dan label Bahasa Indonesia.
- Pasang Redis (boleh driver default dulu, Redis dipakai serius di Fase 7).

**Selesai bila:** bisa login ke panel kosong, tooling jalan, commit pertama masuk.

### Fase 1 — Master Data
**Tujuan:** semua data acuan bisa dikelola.
- Resource: **Kategori**, **Produk** (+ relasi **Satuan Produk** sebagai Relation Manager: pcs/dus/karton + konversi + harga_jual), **Pelanggan**, **Supplier**, **Sopir**.
- Validasi, search (sesuai bagian 6), filter (mis. produk per kategori, produk stok menipis).
- Seeder data contoh untuk testing.

**Selesai bila:** CRUD kelima entitas jalan, satu produk bisa punya banyak satuan, search & filter berfungsi.

### Fase 2 — Inti Stok & Transaksi *(inti MVP)*
**Tujuan:** transaksi jalan & stok bergerak otomatis.
- **Stok Masuk (Pembelian)**: pilih supplier → tambah produk + qty + harga beli → `ReceiveStockAction` menambah stok, update HPP, tulis `mutasi_stok`.
- **Penjualan**: pilih pelanggan → tambah item (produk + satuan + qty) → total otomatis → catat metode bayar (lunas) → `CreateSaleAction` (transaksi DB) mengurangi stok & menulis `mutasi_stok`.
- **Mutasi Stok**: halaman read-only buku besar, bisa difilter per produk/tanggal/tipe.
- **Alert stok minimum** (badge/indikator).

**Selesai bila:** buat penjualan otomatis mengurangi stok dengan benar (uji multi-satuan: jual 2 dus mengurangi 48 pcs), stok masuk menambah stok, semua tercatat di mutasi, dan transaksi rollback saat ada error.

### Fase 3 — Pengiriman & Armada
**Tujuan:** kelola pengiriman pakai armada sendiri.
- Assign penjualan ke **sopir** + status kirim (siap → dikirim → terkirim).
- **Cetak Surat Jalan (PDF)** per transaksi.
- Daftar/antrian pengiriman dengan filter status.

**Selesai bila:** transaksi bisa di-assign ke sopir, status berubah, dan surat jalan tercetak rapi.

### Fase 4 — Laporan & Dashboard *(MVP selesai)*
**Tujuan:** owner bisa lihat kondisi toko.
- **Dashboard** (Filament Widgets): omzet hari/bulan ini, jumlah transaksi, stok menipis.
- **Laporan**: penjualan harian/bulanan, produk terlaris, stok saat ini, laba kotor (memakai HPP).
- Filter rentang tanggal.

**Selesai bila:** dashboard & laporan tampil dengan angka benar dan query efisien (pakai agregasi, bukan loop). ✅ **MVP JADI.**

### Fase 5 — User & Hak Akses
**Tujuan:** multi-user yang aman.
- `spatie/laravel-permission` + **Filament Shield**: peran Owner/Admin/Kasir/Gudang/Sopir dengan izin berbeda.
- **Policy** per resource (mis. hanya Owner lihat laporan laba).
- **Activity log** (`spatie/laravel-activitylog`) untuk audit perubahan.

**Selesai bila:** tiap peran hanya melihat & melakukan yang sesuai izinnya, perubahan terekam.

### Fase 6 — Fitur Lanjutan (lengkapi semua fitur)
**Tujuan:** menutup seluruh kebutuhan operasional.
- **Retur barang** (penjualan & pembelian) → menambah/mengurangi stok + tulis mutasi (`retur_masuk`/`retur_keluar`).
- **Stock opname** → penyesuaian stok terhadap hitung fisik, tercatat sebagai mutasi `penyesuaian`.
- **Cetak invoice/struk** (PDF; opsi format thermal 58/80mm).
- **Tanggal kadaluarsa / batch** per stok masuk (relevan untuk minyak, gula, dll) + alert mendekati ED.
- **Harga bertingkat** (opsional): harga khusus per kategori pelanggan atau per kuantitas.
- **Promo & diskon** (opsional).

**Selesai bila:** retur, opname, cetak dokumen, dan tracking ED berfungsi dan konsisten dengan buku besar stok.

### Fase 7 — Optimasi, Skala & Integrasi
**Tujuan:** poles performa & tambahan integrasi.
- **Redis** aktif untuk cache/queue/session; cache laporan berat.
- **Search instan**: FULLTEXT → atau Laravel Scout + Meilisearch/Typesense bila data besar.
- **Queue** untuk PDF, export, notifikasi.
- **Notifikasi WA/email** ke pelanggan (via gateway WA Indonesia, mis. Fonnte/Wablas) — konfirmasi/struk.
- **Export Excel/PDF** laporan (Filament Export Action).
- **Audit performa**: tinjau index, jalankan profiler, perbaiki query lambat.
- **Opsional besar**: multi-gudang/cabang, API + aplikasi mobile untuk sopir (update status di lapangan), Laravel Octane.

**Selesai bila:** search & laporan terasa instan di data besar, pekerjaan berat tidak memblokir UI, integrasi yang dibutuhkan jalan.

---

## 9. Definition of Done (per fitur)

Sebuah fitur baru dianggap **selesai** bila semua ini terpenuhi:
- [ ] Logika bisnis ada di **Action class**, bukan di Resource/Controller.
- [ ] Operasi yang menyentuh stok/banyak tabel dibungkus **`DB::transaction()`**.
- [ ] Semua relasi yang ditampilkan pakai **eager loading** (tidak ada N+1).
- [ ] List panjang **dipaginasi**; kolom yang dicari/difilter **ter-index**.
- [ ] Status/tipe pakai **Enum**, bukan magic string.
- [ ] Input **divalidasi** dengan pesan Bahasa Indonesia yang jelas.
- [ ] **Responsif**: dicek di lebar layar desktop **dan** HP.
- [ ] **Hak akses** lewat Policy (sejak Fase 5).
- [ ] Jalur kritis punya **test**; **Pint** & **Larastan** lolos.
- [ ] Aksi berisiko punya **konfirmasi**; sukses/gagal memberi **notifikasi**.

---

## 10. Panduan Vibe Coding dengan Claude

**Cara pakai dokumen ini:**
1. Taruh file ini di root project (mis. `docs/dokumentasi-sistem-grosir.md`). Jadikan **konteks utama** tiap sesi.
2. Bangun **satu fase / satu fitur per sesi** — jangan minta semua sekaligus. Hasil lebih rapi & mudah direview.
3. Setelah tiap fitur, jalankan **Pint + Larastan + test**, lalu **commit**.

**Pola prompt yang efektif (contoh):**

> "Acuannya dokumen `dokumentasi-sistem-grosir.md`. Kerjakan **Fase 1 — Resource Produk** beserta Relation Manager Satuan Produk. Patuhi standar bagian 3 (Action class), 5 (clean code), 6 (eager loading + search), dan 7 (responsif). Sertakan migration, model, enum bila perlu, Filament Resource, validasi, dan factory/seeder. Akhiri dengan ringkasan singkat + langkah uji."

> "Buatkan `CreateSaleAction` sesuai contoh di bagian 3: transaksi DB, multi-satuan (qty × konversi), harga di-snapshot, kurangi stok lewat `RecordStockMovementAction`, tulis mutasi. Tambahkan test: jual 2 dus (1 dus=24) harus mengurangi 48 pcs dan menulis 1 baris mutasi."

> "Review kode ini terhadap Definition of Done bagian 9. Tunjukkan pelanggaran (N+1, magic string, logika di Resource, index kurang) dan perbaiki."

**Tips:**
- Selalu sebut bagian dokumen yang relevan agar Claude konsisten dengan standar.
- Minta Claude **menjelaskan keputusan** kalau menyimpang dari dokumen.
- Untuk performa, minta Claude **menunjukkan query yang dihasilkan** dan memastikan eager loading + index sudah pas.

---

## 11. Checklist Progres

### Fase 0 — Setup & Fondasi
- [ ] Laravel + Filament + MySQL + login
- [ ] Pint, Larastan, Pest, Git
- [ ] `preventLazyLoading()` lokal, base layout, label ID

### Fase 1 — Master Data
- [ ] Kategori
- [ ] Produk + Satuan Produk (konversi & harga)
- [ ] Pelanggan
- [ ] Supplier
- [ ] Sopir
- [ ] Search + filter + seeder

### Fase 2 — Inti Stok & Transaksi
- [ ] Stok Masuk (Pembelian) + `ReceiveStockAction`
- [ ] Penjualan + `CreateSaleAction` (transaksi DB, multi-satuan)
- [ ] `RecordStockMovementAction` + buku besar Mutasi Stok
- [ ] Alert stok minimum

### Fase 3 — Pengiriman & Armada
- [ ] Assign sopir + status kirim
- [ ] Cetak Surat Jalan (PDF)
- [ ] Antrian/daftar pengiriman

### Fase 4 — Laporan & Dashboard *(MVP)*
- [ ] Dashboard widget (omzet, transaksi, stok menipis)
- [ ] Laporan penjualan, produk terlaris, stok, laba
- [ ] ✅ **MVP SELESAI**

### Fase 5 — User & Hak Akses
- [ ] Roles & permissions (Filament Shield)
- [ ] Policy per resource
- [ ] Activity log

### Fase 6 — Fitur Lanjutan
- [ ] Retur (penjualan & pembelian)
- [ ] Stock opname
- [ ] Cetak invoice/struk
- [ ] Tanggal kadaluarsa / batch + alert
- [ ] Harga bertingkat (opsional)
- [ ] Promo & diskon (opsional)

### Fase 7 — Optimasi, Skala & Integrasi
- [ ] Redis (cache/queue/session) + cache laporan
- [ ] Search instan (FULLTEXT / Scout)
- [ ] Queue untuk tugas berat
- [ ] Notifikasi WA/email
- [ ] Export Excel/PDF
- [ ] Audit performa & index
- [ ] (Opsional) multi-gudang, API + app sopir, Octane

---

*Bangun bertahap, uji tiap langkah, jaga buku besar stok tetap jadi sumber kebenaran. Selamat ngoding!*
