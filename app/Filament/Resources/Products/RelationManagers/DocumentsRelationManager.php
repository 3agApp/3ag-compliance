<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Filament\Resources\Products\Resources\Documents\DocumentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $relatedResource = DocumentResource::class;

    protected static ?string $title = 'Documents';

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
