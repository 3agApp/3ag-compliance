<?php

namespace App\Filament\Resources\Products\RelationManagers\Documents;

use App\Enums\DocumentType;
use App\Models\Document;
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
}
