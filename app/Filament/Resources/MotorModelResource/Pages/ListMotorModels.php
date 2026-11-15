<?php

namespace App\Filament\Resources\MotorModelResource\Pages;

use App\Filament\Resources\MotorModelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMotorModels extends ListRecords
{
    protected static string $resource = MotorModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
