<?php

namespace Tests\Feature;

use App\Enums\WorkOrderStatus;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\MotorModel;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\WorkOrderService;
use Tests\TestCase;

class WorkshopWorkOrderTest extends TestCase
{
    public function test_work_order_service_lifecycle_and_commission_calculation(): void
    {
        $service = app(WorkOrderService::class);
        $branch = Branch::where('code', 'CB01')->first();
        $model = MotorModel::first();
        $mechanic = User::where('email', 'mekanik.budi@posbengkel.com')->first();
        $sa = User::where('email', 'sa.sudirman@posbengkel.com')->first();
        $jasTuneUp = Service::where('code', 'JAS-001')->first(); // 50.000, komisi 40% = 20.000
        $productOli = Product::first();

        $testPlate = 'B ' . rand(1000, 9999) . ' ' . strtoupper(substr(uniqid(), -3));

        // 1. Buat Customer & Vehicle
        $customer = Customer::create([
            'name' => 'Pak Joko',
            'phone' => '081299998888',
        ]);

        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'motor_model_id' => $model->id,
            'plate_number' => $testPlate,
            'last_km' => 12000,
        ]);

        // 2. Buat PKB
        $wo = $service->createWorkOrder([
            'branch_id' => $branch->id,
            'vehicle_id' => $vehicle->id,
            'service_advisor_id' => $sa->id,
            'mechanic_id' => $mechanic->id,
            'current_km' => 12500,
            'complaints' => 'Tarikan berat',
        ]);

        $this->assertEquals(WorkOrderStatus::Queued, $wo->status);
        $this->assertStringStartsWith('WO-', $wo->wo_number);
        $this->assertEquals(12500, $vehicle->fresh()->last_km);

        // 3. Mulai Pengerjaan
        $wo = $service->startWorkOrder($wo->id);
        $this->assertEquals(WorkOrderStatus::InProgress, $wo->status);

        // 4. Tambah Jasa Servis (Tune Up)
        $itemJasa = $service->addItem(
            woId: $wo->id,
            itemType: 'service',
            itemId: $jasTuneUp->id,
            qty: 1,
            mechanicId: $mechanic->id
        );

        $this->assertEquals(50000, $itemJasa->subtotal);
        $this->assertEquals(20000, $itemJasa->commission_amount); // 40% dari 50.000

        // 5. Tambah Sparepart
        $itemPart = $service->addItem(
            woId: $wo->id,
            itemType: 'product',
            itemId: $productOli->id,
            qty: 1
        );

        $this->assertGreaterThan(0, $itemPart->subtotal);
        $this->assertEquals(0, $itemPart->commission_amount);

        // 6. Selesaikan Servis (Siap Bayar)
        $wo = $service->finishWorkOrder($wo->id);
        $this->assertEquals(WorkOrderStatus::ReadyForPayment, $wo->status);
        $this->assertNotNull($vehicle->fresh()->next_service_date);

        // 7. Uji Generate WhatsApp Reminder
        $waUrl = $service->generateWhatsAppReminderUrl($vehicle);
        $this->assertStringContainsString('wa.me/6281299998888', $waUrl);
    }

    public function test_android_api_workshop_flow(): void
    {
        $sa = User::where('email', 'sa.sudirman@posbengkel.com')->first();
        $mechanic = User::where('email', 'mekanik.budi@posbengkel.com')->first();
        $model = MotorModel::first();
        $jasGantiOli = Service::where('code', 'JAS-002')->first();

        $apiPlate = 'D ' . rand(1000, 9999) . ' ' . strtoupper(substr(uniqid(), -3));

        // Register Vehicle via API
        $resVehicle = $this->actingAs($sa, 'sanctum')
            ->postJson('/api/v1/workshop/vehicles', [
                'plate_number' => $apiPlate,
                'motor_model_id' => $model->id,
                'customer_name' => 'Andi Wijaya',
                'customer_phone' => '087711223344',
                'current_km' => 8000,
            ]);

        $resVehicle->assertStatus(201);
        $vehicleId = $resVehicle->json('data.id');

        // Search Vehicle via API
        $resSearch = $this->actingAs($sa, 'sanctum')
            ->getJson("/api/v1/workshop/vehicles/search?plate_number={$apiPlate}");
        $resSearch->assertStatus(200);

        // Create Work Order via API
        $resWo = $this->actingAs($sa, 'sanctum')
            ->postJson('/api/v1/workshop/work-orders', [
                'vehicle_id' => $vehicleId,
                'mechanic_id' => $mechanic->id,
                'current_km' => 8100,
                'complaints' => 'Ganti oli mesin',
            ]);

        $resWo->assertStatus(201);
        $woId = $resWo->json('data.id');

        // Add Item via API
        $resItem = $this->actingAs($mechanic, 'sanctum')
            ->postJson("/api/v1/workshop/work-orders/{$woId}/items", [
                'item_type' => 'service',
                'item_id' => $jasGantiOli->id,
                'qty' => 1,
            ]);

        $resItem->assertStatus(200);

        // Update status to in_progress then ready_for_payment
        $this->actingAs($mechanic, 'sanctum')
            ->patchJson("/api/v1/workshop/work-orders/{$woId}/status", ['status' => 'in_progress'])
            ->assertStatus(200);

        $this->actingAs($mechanic, 'sanctum')
            ->patchJson("/api/v1/workshop/work-orders/{$woId}/status", ['status' => 'ready_for_payment'])
            ->assertStatus(200);

        // Mechanic checks my-commissions via API
        $resCommissions = $this->actingAs($mechanic, 'sanctum')
            ->getJson('/api/v1/workshop/mechanics/my-commissions');

        $resCommissions->assertStatus(200)
            ->assertJsonPath('success', true);
        $this->assertGreaterThanOrEqual(5000, (float) $resCommissions->json('data.total_commission'));
    }
}
