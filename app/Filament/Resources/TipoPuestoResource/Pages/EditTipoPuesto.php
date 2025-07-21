<?php

namespace App\Filament\Resources\TipoPuestoResource\Pages;

use App\Filament\Resources\TipoPuestoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTipoPuesto extends EditRecord
{
    protected static string $resource = TipoPuestoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
