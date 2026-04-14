<?php

namespace App\Filament\Resources\Products\Resources\Documents;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Products\Resources\Documents\Pages\CreateDocument;
use App\Filament\Resources\Products\Resources\Documents\Pages\EditDocument;
use App\Filament\Resources\Products\Resources\Documents\Pages\ListDocuments;
use App\Filament\Resources\Products\Resources\Documents\Schemas\DocumentForm;
use App\Filament\Resources\Products\Resources\Documents\Tables\DocumentsTable;
use App\Models\Document;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $parentResource = ProductResource::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document';

    protected static ?string $navigationLabel = 'Documents';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return DocumentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocumentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocuments::route('/'),
            'create' => CreateDocument::route('/create'),
            'edit' => EditDocument::route('/{record}/edit'),
        ];
    }
}
