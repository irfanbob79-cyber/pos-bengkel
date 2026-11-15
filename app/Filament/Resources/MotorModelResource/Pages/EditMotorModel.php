<?php

namespace App\Filament\Resources\MotorModelResource\Pages;

use App\Filament\Resources\MotorModelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMotorModel extends EditRecord
{
    protected static string $resource = MotorModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
