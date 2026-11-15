<?php

namespace App\Filament\Resources\StockOpnameResource\Pages;

use App\Enums\StockOpnameScope;
use App\Filament\Resources\StockOpnameResource;
use App\Services\StockOpnameService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateStockOpname extends CreateRecord
{
    protected static string $resource = StockOpnameResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $scopeType = is_string($data['scope_type'])
            ? StockOpnameScope::from($data['scope_type'])
            : $data['scope_type'];

        return app(StockOpnameService::class)->createSession(
            branchId: (int) $data['branch_id'],
            userId: Auth::id() ?? 1,
            scopeType: $scopeType,
            scopeValue: $data['scope_value'] ?? null,
            notes: $data['notes'] ?? null
        );
    }
}
