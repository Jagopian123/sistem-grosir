<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\HargaTingkat;
use App\Models\Kategori;
use App\Models\Pelanggan;
use App\Models\Produk;
use App\Models\SatuanProduk;
use App\Models\Sopir;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedKategoris();
        $this->seedProduks();
        $this->seedHargaTingkat();
        $this->seedPelanggans();
        $this->seedSuppliers();
        $this->seedSopirs();
    }

    private function seedKategoris(): void
    {
        $kategoris = [
            'Makanan', 'Minuman', 'Sembako', 'Bumbu Dapur',
            'Kebutuhan RT', 'Sabun & Detergen', 'Rokok', 'Snack',
        ];

        foreach ($kategoris as $nama) {
            Kategori::firstOrCreate(['nama' => $nama]);
        }
    }

    private function seedProduks(): void
    {
        $data = [
            [
                'kategori' => 'Makanan',
                'produk' => [
                    ['nama' => 'Indomie Goreng', 'satuan_dasar' => 'pcs', 'stok' => 480, 'stok_min' => 48, 'harga_beli' => 2_800, 'lacak_kadaluarsa' => true],
                    ['nama' => 'Indomie Rebus', 'satuan_dasar' => 'pcs', 'stok' => 240, 'stok_min' => 48, 'harga_beli' => 2_700, 'lacak_kadaluarsa' => true],
                    ['nama' => 'Beras Premium 5kg', 'satuan_dasar' => 'karung', 'stok' => 100, 'stok_min' => 10, 'harga_beli' => 62_000, 'lacak_kadaluarsa' => true],
                ],
                'satuan' => [
                    'Indomie Goreng' => [
                        ['nama_satuan' => 'pcs', 'konversi' => 1, 'harga_jual' => 3_500],
                        ['nama_satuan' => 'dus', 'konversi' => 40, 'harga_jual' => 130_000],
                    ],
                    'Indomie Rebus' => [
                        ['nama_satuan' => 'pcs', 'konversi' => 1, 'harga_jual' => 3_300],
                        ['nama_satuan' => 'dus', 'konversi' => 40, 'harga_jual' => 126_000],
                    ],
                    'Beras Premium 5kg' => [
                        ['nama_satuan' => 'karung', 'konversi' => 1, 'harga_jual' => 68_000],
                    ],
                ],
            ],
            [
                'kategori' => 'Minuman',
                'produk' => [
                    ['nama' => 'Aqua 600ml', 'satuan_dasar' => 'botol', 'stok' => 480, 'stok_min' => 24, 'harga_beli' => 2_000, 'lacak_kadaluarsa' => true],
                    ['nama' => 'Teh Kotak 250ml', 'satuan_dasar' => 'pcs', 'stok' => 240, 'stok_min' => 24, 'harga_beli' => 2_500, 'lacak_kadaluarsa' => true],
                ],
                'satuan' => [
                    'Aqua 600ml' => [
                        ['nama_satuan' => 'botol', 'konversi' => 1, 'harga_jual' => 3_000],
                        ['nama_satuan' => 'dus', 'konversi' => 24, 'harga_jual' => 68_000],
                    ],
                    'Teh Kotak 250ml' => [
                        ['nama_satuan' => 'pcs', 'konversi' => 1, 'harga_jual' => 3_500],
                        ['nama_satuan' => 'dus', 'konversi' => 24, 'harga_jual' => 80_000],
                    ],
                ],
            ],
            [
                'kategori' => 'Sabun & Detergen',
                'produk' => [
                    ['nama' => 'Sabun Lifebuoy 85gr', 'satuan_dasar' => 'pcs', 'stok' => 144, 'stok_min' => 12, 'harga_beli' => 4_500],
                    ['nama' => 'Rinso 900gr', 'satuan_dasar' => 'pcs', 'stok' => 12, 'stok_min' => 12, 'harga_beli' => 21_000],
                ],
                'satuan' => [
                    'Sabun Lifebuoy 85gr' => [
                        ['nama_satuan' => 'pcs', 'konversi' => 1, 'harga_jual' => 5_500],
                        ['nama_satuan' => 'lusin', 'konversi' => 12, 'harga_jual' => 62_000],
                    ],
                    'Rinso 900gr' => [
                        ['nama_satuan' => 'pcs', 'konversi' => 1, 'harga_jual' => 25_000],
                    ],
                ],
            ],
            [
                'kategori' => 'Rokok',
                'produk' => [
                    ['nama' => 'Gudang Garam Merah', 'satuan_dasar' => 'batang', 'stok' => 2_400, 'stok_min' => 200, 'harga_beli' => 950],
                    ['nama' => 'Sampoerna Mild', 'satuan_dasar' => 'batang', 'stok' => 1_200, 'stok_min' => 200, 'harga_beli' => 1_050],
                ],
                'satuan' => [
                    'Gudang Garam Merah' => [
                        ['nama_satuan' => 'batang', 'konversi' => 1, 'harga_jual' => 1_200],
                        ['nama_satuan' => 'bungkus', 'konversi' => 12, 'harga_jual' => 14_000],
                        ['nama_satuan' => 'slop', 'konversi' => 240, 'harga_jual' => 270_000],
                    ],
                    'Sampoerna Mild' => [
                        ['nama_satuan' => 'batang', 'konversi' => 1, 'harga_jual' => 1_400],
                        ['nama_satuan' => 'bungkus', 'konversi' => 16, 'harga_jual' => 22_000],
                        ['nama_satuan' => 'slop', 'konversi' => 160, 'harga_jual' => 210_000],
                    ],
                ],
            ],
        ];

        foreach ($data as $group) {
            $kategori = Kategori::where('nama', $group['kategori'])->first();

            if (! $kategori) {
                continue;
            }

            foreach ($group['produk'] as $produkData) {
                $produk = Produk::firstOrCreate(
                    ['nama' => $produkData['nama']],
                    array_merge($produkData, ['kategori_id' => $kategori->id, 'aktif' => true])
                );

                $satuans = $group['satuan'][$produkData['nama']] ?? [];
                foreach ($satuans as $satuanData) {
                    SatuanProduk::firstOrCreate(
                        ['produk_id' => $produk->id, 'nama_satuan' => $satuanData['nama_satuan']],
                        $satuanData
                    );
                }
            }
        }
    }

    /**
     * Contoh harga bertingkat (grosir per kuantitas) untuk sebagian satuan.
     * Format: [nama produk, nama satuan] => [min_qty => harga].
     */
    private function seedHargaTingkat(): void
    {
        $tingkatan = [
            ['Indomie Goreng', 'dus', [5 => 128_000, 10 => 125_000]],
            ['Aqua 600ml', 'dus', [10 => 66_000, 25 => 64_000]],
            ['Gudang Garam Merah', 'slop', [5 => 265_000, 10 => 260_000]],
        ];

        foreach ($tingkatan as [$namaProduk, $namaSatuan, $tiers]) {
            $satuan = SatuanProduk::whereHas('produk', fn ($q) => $q->where('nama', $namaProduk))
                ->where('nama_satuan', $namaSatuan)
                ->first();

            if (! $satuan) {
                continue;
            }

            foreach ($tiers as $minQty => $harga) {
                HargaTingkat::firstOrCreate(
                    ['satuan_id' => $satuan->id, 'min_qty' => $minQty],
                    ['harga' => $harga]
                );
            }
        }
    }

    private function seedPelanggans(): void
    {
        $pelanggans = [
            ['nama_toko' => 'Toko Makmur', 'nama_kontak' => 'Budi Santoso', 'telepon' => '08123456001', 'alamat' => 'Jl. Pasar Lama No. 12, Kota A'],
            ['nama_toko' => 'Warung Barokah', 'nama_kontak' => 'Siti Rahayu', 'telepon' => '08123456002', 'alamat' => 'Jl. Kemangi No. 5, Kota A'],
            ['nama_toko' => 'Mini Market Sejahtera', 'nama_kontak' => 'Hendra Wijaya', 'telepon' => '08123456003', 'alamat' => 'Jl. Merdeka No. 88, Kota B'],
            ['nama_toko' => 'Toko Pak Amin', 'nama_kontak' => null, 'telepon' => '08123456004', 'alamat' => 'Jl. Raya Selatan No. 2, Kota B'],
            ['nama_toko' => 'Kedai Berkah', 'nama_kontak' => 'Dewi Lestari', 'telepon' => '08123456005', 'alamat' => 'Jl. Flamboyan No. 17, Kota C'],
        ];

        foreach ($pelanggans as $data) {
            Pelanggan::firstOrCreate(['nama_toko' => $data['nama_toko']], $data);
        }
    }

    private function seedSuppliers(): void
    {
        $suppliers = [
            ['nama' => 'PT Indofood Sukses Makmur', 'telepon' => '02145670001', 'alamat' => 'Jl. Sudirman No. 1, Jakarta'],
            ['nama' => 'CV Maju Bersama', 'telepon' => '08567890002', 'alamat' => 'Jl. Industri No. 55, Surabaya'],
            ['nama' => 'UD Sumber Rejeki', 'telepon' => '08567890003', 'alamat' => 'Jl. Raya Industri No. 10, Semarang'],
        ];

        foreach ($suppliers as $data) {
            Supplier::firstOrCreate(['nama' => $data['nama']], $data);
        }
    }

    private function seedSopirs(): void
    {
        $sopirs = [
            ['nama' => 'Ahmad Fauzi', 'telepon' => '08111222001'],
            ['nama' => 'Joko Prayitno', 'telepon' => '08111222002'],
            ['nama' => 'Rudi Hartono', 'telepon' => '08111222003'],
        ];

        foreach ($sopirs as $data) {
            Sopir::firstOrCreate(['nama' => $data['nama']], $data);
        }
    }
}
