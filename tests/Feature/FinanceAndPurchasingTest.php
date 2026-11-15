<?php

namespace Tests\Feature;

use App\Enums\PaymentType;
use App\Enums\PurchaseStatus;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ProfitLossService;
use App\Services\PurchaseService;
use Carbon\Carbon;
use Tests\TestCase;

class FinanceAndPurchasingTest extends TestCase
{
    public function test_purchase_service_lifecycle_and_hpp_update(): void
    {
        $purchaseService = app(PurchaseService::class);
        $branch = Branch::where('code', 'CB01')->first();
        $user = User::where('email', 'admin@posbengkel.com')->first();
        $product = Product::first();

        // 1. Buat Supplier
        $supplier = Supplier::create([
            'name' => 'PT. Astra Otoparts Demo',
            'phone' => '021-456789',
        ]);

        $initialStock = (float) BranchStock::where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->value('qty');

        // 2. Buat PO Baru
        $purchase = $purchaseService->createPurchase([
            'branch_id' => $branch->id,
            'supplier_id' => $supplier->id,
            'user_id' => $user->id,
            'payment_type' => PaymentType::Cash,
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 10,
                    'cost_price' => 40000,
                ],
            ],
        ]);

        $this->assertEquals(PurchaseStatus::Ordered, $purchase->status);
        $this->assertEquals(400000, $purchase->total_amount);

        // 3. Konfirmasi Terima Barang di Gudang
        $receivedPurchase = $purchaseService->receivePurchase($purchase->id, $user->id);
        $this->assertEquals(PurchaseStatus::Received, $receivedPurchase->status);

        // Periksa bahwa stok cabang bertambah 10
        $stockAfterReceive = (float) BranchStock::where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->value('qty');
        $this->assertEquals($initialStock + 10, $stockAfterReceive);
    }

    public function test_profit_loss_service_calculation(): void
    {
        $plService = app(ProfitLossService::class);
        $branch = Branch::where('code', 'CB01')->first();
        $user = User::first();

        // Catat Beban Operasional Cabang
        Expense::create([
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'category' => 'Listrik & Air',
            'amount' => 150000,
            'expense_date' => Carbon::now()->toDateString(),
        ]);

        $report = $plService->calculate(
            branchId: $branch->id,
            startDate: Carbon::now()->startOfMonth(),
            endDate: Carbon::now()->endOfMonth()
        );

        $this->assertArrayHasKey('revenue', $report);
        $this->assertArrayHasKey('cogs', $report);
        $this->assertArrayHasKey('gross_profit', $report);
        $this->assertArrayHasKey('expenses', $report);
        $this->assertArrayHasKey('net_profit', $report);

        $this->assertGreaterThanOrEqual(150000, $report['expenses']['branch_expenses']);
    }

    public function test_android_api_finance_and_purchasing(): void
    {
        $admin = User::where('email', 'admin@posbengkel.com')->first();
        $branch = Branch::first();
        $product = Product::first();
        $supplier = Supplier::first() ?? Supplier::create(['name' => 'Demo Supplier']);

        // 1. Get P&L via API
        $resPl = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/finance/profit-loss');

        $resPl->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['revenue', 'cogs', 'gross_profit', 'expenses', 'net_profit']]);

        // 2. Create Purchase via API
        $resPo = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/finance/purchases', [
                'supplier_id' => $supplier->id,
                'branch_id' => $branch->id,
                'payment_type' => 'cash',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'qty' => 5,
                        'cost_price' => 35000,
                    ],
                ],
            ]);

        $resPo->assertStatus(201);
        $poId = $resPo->json('data.id');

        // 3. Receive Purchase via API
        $resReceive = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/finance/purchases/{$poId}/receive");

        $resReceive->assertStatus(200);
        $this->assertEquals(PurchaseStatus::Received->value, $resReceive->json('data.status'));
    }
}
