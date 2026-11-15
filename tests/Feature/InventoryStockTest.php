<?php

namespace Tests\Feature;

use App\Enums\StockMovementType;
use App\Enums\StockOpnameScope;
use App\Enums\StockTransferStatus;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use App\Services\StockOpnameService;
use App\Services\StockService;
use Tests\TestCase;

class InventoryStockTest extends TestCase
{
    public function test_stock_service_increase_decrease_and_prevent_negative(): void
    {
        $stockService = app(StockService::class);
        $branch = Branch::first();
        $product = Product::first();
        $user = User::first();

        // Ambil stok awal
        $initialStock = (float) BranchStock::where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->value('qty');

        // Tambah 10 stok
        $stockService->increaseStock(
            branchId: $branch->id,
            productId: $product->id,
            qty: 10,
            type: StockMovementType::PurchaseIn,
            userId: $user->id,
            notes: 'Test penambahan stok'
        );

        $newStock = (float) BranchStock::where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->value('qty');

        $this->assertEquals($initialStock + 10, $newStock);

        // Kurang 5 stok
        $stockService->decreaseStock(
            branchId: $branch->id,
            productId: $product->id,
            qty: 5,
            type: StockMovementType::SaleOut,
            userId: $user->id,
            notes: 'Test pengurangan stok'
        );

        $finalStock = (float) BranchStock::where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->value('qty');

        $this->assertEquals($initialStock + 5, $finalStock);

        // Uji proteksi stok tidak mencukupi (minta 999999)
        $this->expectException(\Exception::class);
        $stockService->decreaseStock(
            branchId: $branch->id,
            productId: $product->id,
            qty: 999999,
            type: StockMovementType::SaleOut
        );
    }

    public function test_stock_transfer_dispatch_and_receive(): void
    {
        $stockService = app(StockService::class);
        $fromBranch = Branch::where('code', 'PST')->first();
        $toBranch = Branch::where('code', 'CB01')->first();
        $product = Product::first();
        $user = User::first();

        $fromInitial = (float) BranchStock::where('branch_id', $fromBranch->id)->where('product_id', $product->id)->value('qty');
        $toInitial = (float) BranchStock::where('branch_id', $toBranch->id)->where('product_id', $product->id)->value('qty');

        // Buat Draft Transfer
        $transfer = StockTransfer::create([
            'transfer_number' => 'TRF-TEST-' . uniqid(),
            'from_branch_id' => $fromBranch->id,
            'to_branch_id' => $toBranch->id,
            'user_id' => $user->id,
            'status' => StockTransferStatus::Draft,
        ]);

        StockTransferItem::create([
            'stock_transfer_id' => $transfer->id,
            'product_id' => $product->id,
            'qty_sent' => 20,
            'qty_received' => 0,
        ]);

        // Kirim (Dispatch) -> InTransit
        $stockService->dispatchTransfer($transfer, $user->id);

        $fromAfterSent = (float) BranchStock::where('branch_id', $fromBranch->id)->where('product_id', $product->id)->value('qty');
        $this->assertEquals($fromInitial - 20, $fromAfterSent);

        // Terima (Receive) -> Completed
        $stockService->receiveTransfer($transfer, [$transfer->items()->first()->id => 20], $user->id);

        $toAfterReceived = (float) BranchStock::where('branch_id', $toBranch->id)->where('product_id', $product->id)->value('qty');
        $this->assertEquals($toInitial + 20, $toAfterReceived);
    }

    public function test_stock_opname_flow_and_approval(): void
    {
        $opnameService = app(StockOpnameService::class);
        $branch = Branch::where('code', 'CB01')->first();
        $product = Product::first();
        $user = User::first();

        // 1. Buat Sesi Opname
        $opname = $opnameService->createSession(
            branchId: $branch->id,
            userId: $user->id,
            scopeType: StockOpnameScope::All,
            notes: 'Uji Coba Opname'
        );

        $this->assertNotNull($opname);

        // 2. Scan barcode
        $item = $opnameService->recordScan(
            opnameId: $opname->id,
            barcodeOrSku: $product->sku,
            qty: 77,
            replace: true
        );

        $this->assertEquals(77, $item->scanned_qty);

        // 3. Approval
        $opnameService->approveSession($opname->id, $user->id);

        $stockAfterOpname = (float) BranchStock::where('branch_id', $branch->id)->where('product_id', $product->id)->value('qty');
        $this->assertEquals(77, $stockAfterOpname);
    }

    public function test_android_api_quick_check(): void
    {
        $user = User::where('email', 'admin@posbengkel.com')->first();
        $product = Product::first();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/inventory/quick-check?barcode={$product->sku}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.sku', $product->sku);
    }
}
