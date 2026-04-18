<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use App\Services\Documents\ProductDocumentAnalysisService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use LogicException;
use Throwable;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = ProductResource::mutateFormData($data);
        $data['status'] = $this->record->status?->value;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('submitForReview')
                ->label('Submit for review')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record instanceof Product
                    && $this->currentUserCanSubmitProducts()
                    && $this->record->canBeSubmittedForReview())
                ->action(function (): void {
                    if (! ($this->record instanceof Product)) {
                        return;
                    }

                    if (! $this->record->submitForReview()) {
                        Notification::make()
                            ->warning()
                            ->title('Product cannot be submitted')
                            ->body('Only products with 100% completeness that are not already under review or approved can be submitted.')
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->success()
                        ->title('Product submitted for review')
                        ->send();

                    $this->refreshFormData(['status', 'completeness_score']);
                }),
            Action::make('analyzeDocuments')
                ->label('Analyse documents')
                ->icon('heroicon-o-sparkles')
                ->color('info')
                ->disabled(fn (): bool => $this->record instanceof Product && $this->record->documentAnalysisRuns()->active()->exists())
                ->action(function (ProductDocumentAnalysisService $analysisService): void {
                    if (! ($this->record instanceof Product)) {
                        return;
                    }

                    try {
                        $run = $analysisService->start($this->record, Filament::auth()->user());

                        Notification::make()
                            ->success()
                            ->title('Document analysis started')
                            ->body("Processing {$run->total_documents} documents in {$run->total_batches} batches.")
                            ->send();
                    } catch (LogicException $exception) {
                        Notification::make()
                            ->warning()
                            ->title('Document analysis not started')
                            ->body($exception->getMessage())
                            ->send();
                    } catch (Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->danger()
                            ->title('Document analysis failed to start')
                            ->body('An unexpected error occurred while starting document analysis.')
                            ->send();
                    }
                }),
            DeleteAction::make(),
        ];
    }

    private function currentUserCanSubmitProducts(): bool
    {
        $tenant = Filament::getTenant();
        $user = Filament::auth()->user();

        return $tenant !== null
            && $user !== null
            && ($user->getRoleForDistributor($tenant)?->canSubmitProducts() ?? false);
    }
}
