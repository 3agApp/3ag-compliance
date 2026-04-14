<?php

namespace App\Filament\Resources\Products\Resources\Documents\Schemas;

use App\Enums\DocumentType;
use App\Models\Document;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Document details')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('type')
                            ->options(DocumentType::options())
                            ->native(false)
                            ->required(),
                        SpatieMediaLibraryFileUpload::make('files')
                            ->label('Files')
                            ->collection(Document::FILE_COLLECTION)
                            ->multiple()
                            ->reorderable()
                            ->downloadable()
                            ->openable()
                            ->helperText('You can upload and reorder multiple files for the same document type.')
                            ->required(),
                    ]),
            ]);
    }
}
