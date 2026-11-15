<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class QuickActionWidget extends Widget
{
    protected static string $view = 'filament.widgets.quick-action-widget';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
}
