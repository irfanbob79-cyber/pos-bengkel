<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Enums\WorkOrderStatus;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\CashRegister;
use App\Models\Customer;
use App\Models\MotorModel;
use App\Models\PriceTier;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\PosCheckoutService;
use App\Services\WorkOrderService;
use Tests\TestCase;

class PosCheckoutTest extends TestCase
{
    public function test_pos_checkout_flow_and_atomic_stock_decrease(): void
    {
        $posService = app(PosCheckoutService::class);
        $branch = Branch::where('code', 'CB01')->first();
        $cashier = User::where('email', 'kasir.sudirman@posbengkel.com')->first();
        $mechanic = User::where('email', 'mekanik.budi@posbengkel.com')->first();
        $tier = PriceTier::where('is_default', true)->first();
        $product = Product::first();
        $service = Service::where('code', 'JAS-001')->first(); // 50.000, komisi 20.000

        // 1. Buka Shift Kasir
        $register = $posService->openRegister(
            branchId: $branch->id,
            userId: $cashier->id,
            openingBalance: 200000
        );

        $this->assertNotNull($register);
        $this->assertEquals(200000, $register->opening_balance);

        // Ambil stok awal
        $initialStock = (float) BranchStock::where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->value('qty');

        // 2. Checkout Transaksi Hibrida (Part + Jasa)
        $sale = $posService->checkout([
            'branch_id' => $branch->id,
            'cash_register_id' => $register->id,
            'user_id' => $cashier->id,
            'price_tier_id' => $tier->id,
            'total_gross' => 106000, // Misal Oli 56.000 + Jasa 50.000
            'discount_amount' => 6000,
            'tax_amount' => 0,
            'grand_total' => 100000,
            'payments' => [
                ['payment_method' => PaymentMethod::Cash, 'amount' => 60000],
                ['payment_method' => PaymentMethod::Qris, 'amount' => 40000],
            ],
            'items' => [
                [
                    'item_type' => 'product',
                    'item_id' => $product->id,
                    'qty' => 1,
                    'unit_price' => 56000,
                    'subtotal' => 56000,
                    'commission_amount' => 0,
                ],
                [
                    'item_type' => 'service',
                    'item_id' => $service->id,
                    'qty' => 1,
                    'unit_price' => 50000,
                    'subtotal' => 50000,
                    'mechanic_id' => $mechanic->id,
                    'commission_amount' => 20000,
                ],
            ],
        ]);

        $this->assertEquals(SaleStatus::Completed, $sale->status);
        $this->assertStringStartsWith('INV-', $sale->invoice_number);
        $this->assertEquals(100000, $sale->total_paid);

        // Periksa bahwa stok produk berkurang tepat 1
        $stockAfterSale = (float) BranchStock::where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->value('qty');
        $this->assertEquals($initialStock - 1, $stockAfterSale);

        // Periksa snapshot HPP dan komisi mekanik
        $saleItemPart = $sale->items->where('item_type', 'product')->first();
        $this->assertEquals($product->moving_avg_cost, $saleItemPart->cost_snapshot);

        $saleItemService = $sale->items->where('item_type', 'service')->first();
        $this->assertEquals(20000, $saleItemService->mechanic_commission);

        // 3. Tutup Shift Kasir (Uang Masuk Tunai: 60.000, Modal: 200.000 -> Expected: 260.000)
        $closedRegister = $posService->closeRegister(
            registerId: $register->id,
            physicalBalance: 260000
        );

        $this->assertEquals(260000, $closedRegister->expected_balance);
        $this->assertEquals(0, $closedRegister->difference);
    }

    public function test_work_order_to_pos_checkout(): void
    {
        $posService = app(PosCheckoutService::class);
        $woService = app(WorkOrderService::class);
        $branch = Branch::where('code', 'CB01')->first();
        $cashier = User::where('email', 'kasir.sudirman@posbengkel.com')->first();
        $model = MotorModel::first();
        $tier = PriceTier::where('is_default', true)->first();

        $customer = Customer::create(['name' => 'Pak Joko POS']);
        $uniquePlate = 'B ' . rand(1000, 9999) . ' ' . strtoupper(substr(uniqid(), -3));
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'motor_model_id' => $model->id,
            'plate_number' => $uniquePlate,
        ]);

        $wo = $woService->createWorkOrder([
            'branch_id' => $branch->id,
            'vehicle_id' => $vehicle->id,
        ]);

        // Finish work order
        $wo = $woService->finishWorkOrder($wo->id);
        $this->assertEquals(WorkOrderStatus::ReadyForPayment, $wo->status);

        // Kasir checkout PKB
        $sale = $posService->checkout([
            'branch_id' => $branch->id,
            'user_id' => $cashier->id,
            'customer_id' => $customer->id,
            'work_order_id' => $wo->id,
            'price_tier_id' => $tier->id,
            'total_gross' => 50000,
            'grand_total' => 50000,
            'payments' => [
                ['payment_method' => PaymentMethod::Cash, 'amount' => 50000],
            ],
            'items' => [
                [
                    'item_type' => 'service',
                    'item_id' => Service::first()->id,
                    'qty' => 1,
                    'unit_price' => 50000,
                    'subtotal' => 50000,
                ],
            ],
        ]);

        $this->assertEquals(WorkOrderStatus::Completed, $wo->fresh()->status);
    }

    public function test_android_api_pos_checkout(): void
    {
        $cashier = User::where('email', 'kasir.sudirman@posbengkel.com')->first();
        $tier = PriceTier::where('is_default', true)->first();
        $product = Product::first();

        // 1. Buka shift via API
        $resOpen = $this->actingAs($cashier, 'sanctum')
            ->postJson('/api/v1/pos/register/open', [
                'opening_balance' => 150000,
            ]);

        $resOpen->assertStatus(201);
        $registerId = $resOpen->json('data.id');

        // 2. Checkout via API
        $resCheckout = $this->actingAs($cashier, 'sanctum')
            ->postJson('/api/v1/pos/checkout', [
                'cash_register_id' => $registerId,
                'price_tier_id' => $tier->id,
                'total_gross' => 56000,
                'grand_total' => 56000,
                'payments' => [
                    ['payment_method' => 'cash', 'amount' => 60000],
                ],
                'items' => [
                    [
                        'item_type' => 'product',
                        'item_id' => $product->id,
                        'qty' => 1,
                        'unit_price' => 56000,
                        'subtotal' => 56000,
                    ],
                ],
            ]);

        $resCheckout->assertStatus(201)
            ->assertJsonPath('success', true);
        $this->assertEquals(4000, $resCheckout->json('data.change_amount')); // Kembalian 60.000 - 56.000 = 4.000

        // 3. Tutup shift via API
        $resClose = $this->actingAs($cashier, 'sanctum')
            ->postJson('/api/v1/pos/register/close', [
                'cash_register_id' => $registerId,
                'physical_balance' => 206000, // 150.000 + 56.000
            ]);

        $resClose->assertStatus(200);
        $this->assertEquals(0, $resClose->json('data.difference'));
    }
}
