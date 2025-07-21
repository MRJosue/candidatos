<?php

namespace App\Filament\Resources\TipoPuestoResource\Pages;

use App\Filament\Resources\TipoPuestoResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;


class CreateTipoPuesto extends CreateRecord
{

    
    public function mutateFormDataBeforeCreate(array $data): array
    {
        $data['id_usuario'] = auth()->id();
        return $data;
    }
    protected static string $resource = TipoPuestoResource::class;
}
