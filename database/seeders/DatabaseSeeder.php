<?php

namespace Database\Seeders;

use App\Enums\CommissionType;
use App\Enums\QualityType;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Category;
use App\Models\MotorModel;
use App\Models\PriceTier;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Price Tiers (Sistem Multi-Harga Dinamis bawaan)
        $retailTier = PriceTier::create([
            'code' => 'RETAIL',
            'name' => 'Harga Eceran (Default)',
            'is_default' => true,
            'is_active' => true,
        ]);

        $wholesaleTier = PriceTier::create([
            'code' => 'WHOLESALE',
            'name' => 'Harga Grosir',
            'is_default' => false,
            'is_active' => true,
        ]);

        $specialWholesaleTier = PriceTier::create([
            'code' => 'SPECIAL_WHOLESALE',
            'name' => 'Harga Spesial Grosir',
            'is_default' => false,
            'is_active' => true,
        ]);

        // 2. Cabang & Gudang
        $centralBranch = Branch::create([
            'code' => 'PST',
            'name' => 'Gudang Pusat Distribusi',
            'address' => 'Jl. Industri Otomotif No. 1, Jakarta Pusat',
            'phone' => '021-5551234',
            'is_warehouse_central' => true,
            'is_active' => true,
        ]);

        $sudirmanBranch = Branch::create([
            'code' => 'CB01',
            'name' => 'Bengkel Cabang Sudirman',
            'address' => 'Jl. Jenderal Sudirman No. 88, Jakarta Selatan',
            'phone' => '021-7775678',
            'is_warehouse_central' => false,
            'is_active' => true,
        ]);

        $ahmadyaniBranch = Branch::create([
            'code' => 'CB02',
            'name' => 'Bengkel Cabang Ahmad Yani',
            'address' => 'Jl. Ahmad Yani No. 45, Bekasi',
            'phone' => '021-8889900',
            'is_warehouse_central' => false,
            'is_active' => true,
        ]);

        // 3. User & Role
        // Super Admin (Tanpa terikat cabang spesifik)
        User::create([
            'branch_id' => null,
            'name' => 'Super Admin / Owner',
            'email' => 'admin@posbengkel.com',
            'password' => Hash::make('password'),
            'role' => UserRole::SuperAdmin,
            'phone' => '081234567890',
            'is_active' => true,
        ]);

        // Branch Manager Sudirman
        User::create([
            'branch_id' => $sudirmanBranch->id,
            'name' => 'Bambang (Branch Manager)',
            'email' => 'manager.sudirman@posbengkel.com',
            'password' => Hash::make('password'),
            'role' => UserRole::BranchManager,
            'phone' => '081234567891',
            'is_active' => true,
        ]);

        // Kasir Sudirman
        User::create([
            'branch_id' => $sudirmanBranch->id,
            'name' => 'Siti (Kasir)',
            'email' => 'kasir.sudirman@posbengkel.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Cashier,
            'phone' => '081234567892',
            'is_active' => true,
        ]);

        // Admin Gudang Pusat
        User::create([
            'branch_id' => $centralBranch->id,
            'name' => 'Hendra (Admin Gudang)',
            'email' => 'gudang.pusat@posbengkel.com',
            'password' => Hash::make('password'),
            'role' => UserRole::WarehouseAdmin,
            'phone' => '081234567893',
            'is_active' => true,
        ]);

        // Service Advisor Sudirman
        User::create([
            'branch_id' => $sudirmanBranch->id,
            'name' => 'Rian (Service Advisor)',
            'email' => 'sa.sudirman@posbengkel.com',
            'password' => Hash::make('password'),
            'role' => UserRole::ServiceAdvisor,
            'phone' => '081234567894',
            'is_active' => true,
        ]);

        // Mekanik Sudirman
        User::create([
            'branch_id' => $sudirmanBranch->id,
            'name' => 'Budi Santoso (Mekanik)',
            'email' => 'mekanik.budi@posbengkel.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Mechanic,
            'phone' => '081234567895',
            'is_active' => true,
        ]);

        // 4. Kategori & Brand
        $catOli = Category::create(['code' => 'OLI', 'name' => 'Oli & Pelumas Mesin']);
        $catRem = Category::create(['code' => 'REM', 'name' => 'Sistem Pengereman']);
        $catCvt = Category::create(['code' => 'CVT', 'name' => 'CVT & Transmisi']);
        $catBan = Category::create(['code' => 'BAN', 'name' => 'Ban & Roda']);

        $brandAhm = Brand::create(['name' => 'Honda (AHM)']);
        $brandYamalube = Brand::create(['name' => 'Yamalube']);
        $brandAspira = Brand::create(['name' => 'Aspira']);
        $brandShell = Brand::create(['name' => 'Shell Advance']);

        // 5. Motor Models
        $vario125 = MotorModel::create(['brand' => 'Honda', 'name' => 'Vario 125 eSP', 'engine_capacity' => 125]);
        $vario150 = MotorModel::create(['brand' => 'Honda', 'name' => 'Vario 150 eSP', 'engine_capacity' => 150]);
        $beatFi = MotorModel::create(['brand' => 'Honda', 'name' => 'BeAT FI', 'engine_capacity' => 110]);
        $nmax155 = MotorModel::create(['brand' => 'Yamaha', 'name' => 'NMAX 155', 'engine_capacity' => 155]);

        // 6. Katalog Jasa Servis
        Service::create([
            'code' => 'JAS-001',
            'name' => 'Servis Ringan / Tune Up',
            'default_price' => 50000,
            'commission_type' => CommissionType::Percentage,
            'commission_value' => 40, // 40% untuk mekanik = 20.000
            'is_active' => true,
        ]);

        Service::create([
            'code' => 'JAS-002',
            'name' => 'Jasa Ganti Oli Mesin',
            'default_price' => 10000,
            'commission_type' => CommissionType::Fixed,
            'commission_value' => 5000, // Rp 5.000 flat untuk mekanik
            'is_active' => true,
        ]);

        Service::create([
            'code' => 'JAS-003',
            'name' => 'Servis CVT Lengkap + Pembersihan',
            'default_price' => 65000,
            'commission_type' => CommissionType::Percentage,
            'commission_value' => 45, // 45% = 29.250
            'is_active' => true,
        ]);

        Service::create([
            'code' => 'JAS-004',
            'name' => 'Bongkar Pasang Ban Luar',
            'default_price' => 20000,
            'commission_type' => CommissionType::Fixed,
            'commission_value' => 10000,
            'is_active' => true,
        ]);

        // 7. Produk Contoh (Sparepart & Multi-Harga)
        $oliMpx2 = Product::create([
            'category_id' => $catOli->id,
            'brand_id' => $brandAhm->id,
            'sku' => 'AHM-MPX2-08L',
            'barcode' => '8991234560012',
            'name' => 'Oli AHM MPX2 Matic 0.8L',
            'unit' => 'Botol',
            'quality_type' => QualityType::Oem,
            'moving_avg_cost' => 45000,
            'is_active' => true,
        ]);

        // Set Multi-Harga untuk Oli MPX2
        ProductPrice::create([
            'product_id' => $oliMpx2->id,
            'price_tier_id' => $retailTier->id,
            'price' => 56000,
            'min_qty' => 1,
        ]);
        ProductPrice::create([
            'product_id' => $oliMpx2->id,
            'price_tier_id' => $wholesaleTier->id,
            'price' => 51000,
            'min_qty' => 6,
        ]);
        ProductPrice::create([
            'product_id' => $oliMpx2->id,
            'price_tier_id' => $specialWholesaleTier->id,
            'price' => 48500,
            'min_qty' => 24, // 1 Dus
        ]);

        // Hubungkan oli matic ke Vario & Beat
        $oliMpx2->motorModels()->attach([$vario125->id, $vario150->id, $beatFi->id], ['notes' => 'Cocok untuk semua motor matic Honda']);

        // Kampas Rem Depan
        $kampasRem = Product::create([
            'category_id' => $catRem->id,
            'brand_id' => $brandAspira->id,
            'sku' => 'ASP-PAD-K01',
            'barcode' => '8997890120034',
            'name' => 'Kampas Rem Depan Cakram Vario 125/150/Beat',
            'unit' => 'Set',
            'quality_type' => QualityType::Aftermarket,
            'moving_avg_cost' => 24000,
            'is_active' => true,
        ]);

        ProductPrice::create([
            'product_id' => $kampasRem->id,
            'price_tier_id' => $retailTier->id,
            'price' => 38000,
            'min_qty' => 1,
        ]);
        ProductPrice::create([
            'product_id' => $kampasRem->id,
            'price_tier_id' => $wholesaleTier->id,
            'price' => 32000,
            'min_qty' => 5,
        ]);
        ProductPrice::create([
            'product_id' => $kampasRem->id,
            'price_tier_id' => $specialWholesaleTier->id,
            'price' => 28000,
            'min_qty' => 20,
        ]);

        $kampasRem->motorModels()->attach([$vario125->id, $vario150->id, $beatFi->id], ['notes' => 'Kampas rem depan kaliper standar']);

        // Saldo Stok Awal di Cabang Sudirman
        $sudirmanBranch->branchStocks()->create([
            'product_id' => $oliMpx2->id,
            'qty' => 50,
            'bin_location' => 'RAK-OLI-01',
            'min_stock' => 10,
        ]);

        $sudirmanBranch->branchStocks()->create([
            'product_id' => $kampasRem->id,
            'qty' => 30,
            'bin_location' => 'RAK-REM-02',
            'min_stock' => 5,
        ]);

        // Saldo Stok di Gudang Pusat
        $centralBranch->branchStocks()->create([
            'product_id' => $oliMpx2->id,
            'qty' => 500,
            'bin_location' => 'GDG-PST-A01',
            'min_stock' => 100,
        ]);

        $centralBranch->branchStocks()->create([
            'product_id' => $kampasRem->id,
            'qty' => 300,
            'bin_location' => 'GDG-PST-B03',
            'min_stock' => 50,
        ]);
    }
}
