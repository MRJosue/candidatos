<?php

namespace App\Filament\Resources\TipoPuestoResource\Pages;

use App\Filament\Resources\TipoPuestoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTipoPuestos extends ListRecords
{
    protected static string $resource = TipoPuestoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
