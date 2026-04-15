<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Filament\Resources\Products\RelationManagers\Documents\DocumentForm;
use App\Filament\Resources\Products\RelationManagers\Documents\DocumentsTable;
use App\Models\Product;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Documents';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Product;
    }

    public function form(Schema $schema): Schema
    {
        return DocumentForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return DocumentsTable::configure($table)
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(fn (array $data): array => [
                        ...$data,
                        'organization_id' => $this->getOwnerRecord()->organization_id,
                    ]),
            ]);
    }
}
