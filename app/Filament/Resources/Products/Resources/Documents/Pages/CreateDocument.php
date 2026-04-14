<?php

namespace App\Filament\Resources\Products\Resources\Documents\Pages;

use App\Filament\Resources\Products\Resources\Documents\DocumentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['organization_id'] = $this->getParentRecord()->organization_id;
        $data['product_id'] = $this->getParentRecord()->getKey();

        return $data;
    }
}
