<?php

namespace App\Filament\Widgets;

use Filament\Widgets\AccountWidget as BaseWidget;
use Illuminate\Contracts\View\View;

class CustomAccountWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return true;
    }

    public function render(): View
    {
        return view('filament.widgets.custom-account-widget');
    }
}
