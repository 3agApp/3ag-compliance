<?php

namespace App\Filament\Resources\Products\RelationManagers\Documents;

use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\Product;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Schemas\Schema;

class DocumentForm
{
    /**
     * @return array<int, string>
     */
    public static function acceptedFileTypes(): array
    {
        return [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/webp',
        ];
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label('Type')
                    ->options(DocumentType::options())
                    ->native(false)
                    ->required()
                    ->columnSpanFull(),
                Select::make('product_component_id')
                    ->label('Component')
                    ->options(fn ($livewire, ?Document $record): array => static::componentOptions($livewire, $record))
                    ->placeholder('No component linked')
                    ->native(false)
                    ->searchable()
                    ->preload()
                    ->helperText('Link this document to a detected or manually created component.')
                    ->columnSpanFull(),
                SpatieMediaLibraryFileUpload::make('files')
                    ->label('Files')
                    ->collection(Document::FILE_COLLECTION)
                    ->acceptedFileTypes(static::acceptedFileTypes())
                    ->maxSize((int) ceil(((int) config('media-library.max_file_size', 10 * 1024 * 1024)) / 1024))
                    ->multiple()
                    ->reorderable()
                    ->downloadable()
                    ->openable()
                    ->panelLayout('grid')
                    ->helperText('Upload PDF, JPG, PNG, or WEBP files up to 10 MB each. You can reorder multiple files for the same document type.')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array<int, string>
     */
    protected static function componentOptions(mixed $livewire, ?Document $record): array
    {
        $ownerRecord = method_exists($livewire, 'getOwnerRecord')
            ? $livewire->getOwnerRecord()
            : $record?->product;

        if (! $ownerRecord instanceof Product) {
            return [];
        }

        return $ownerRecord->components()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
