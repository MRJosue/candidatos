<?php

namespace App\Filament\Resources\CandidatoResource\Pages;

use App\Filament\Resources\CandidatoResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCandidato extends CreateRecord
{
    protected static string $resource = CandidatoResource::class;

    public function mutateFormDataBeforeCreate(array $data): array
    {
        $data['id_usuario'] = auth()->id();
        return $data;
    }
}
