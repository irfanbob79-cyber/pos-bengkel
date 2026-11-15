<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class FilamentMasterDataTest extends TestCase
{
    public function test_super_admin_can_access_filament_dashboard_and_resources(): void
    {
        $admin = User::where('email', 'admin@posbengkel.com')->first();
        $this->assertNotNull($admin);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertStatus(200);

        $this->actingAs($admin)
            ->get('/admin/branches')
            ->assertStatus(200);

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertStatus(200);

        $this->actingAs($admin)
            ->get('/admin/price-tiers')
            ->assertStatus(200);

        $this->actingAs($admin)
            ->get('/admin/products')
            ->assertStatus(200);

        $this->actingAs($admin)
            ->get('/admin/motor-models')
            ->assertStatus(200);

        $this->actingAs($admin)
            ->get('/admin/services')
            ->assertStatus(200);
    }
}
