<?php

namespace App\Filament\Resources\Products\RelationManagers\Documents;

use App\Enums\DocumentType;
use App\Models\Document;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (DocumentType|string|null $state): string => $state instanceof DocumentType ? $state->label() : (DocumentType::tryFrom((string) $state)?->label() ?? (string) $state))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('productComponent.name')
                    ->label('Component')
                    ->placeholder('—')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('files')
                    ->label('Files')
                    ->getStateUsing(fn (Document $record): string => $record->getMedia(Document::FILE_COLLECTION)
                        ->pluck('file_name')
                        ->implode(', ') ?: 'No files uploaded')
                    ->wrap(),
                TextColumn::make('files_count')
                    ->label('File count')
                    ->getStateUsing(fn (Document $record): int => $record->getMedia(Document::FILE_COLLECTION)->count())
                    ->sortable(false),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No documents found')
            ->emptyStateDescription('Add a compliance document for this product.')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
