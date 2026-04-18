<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\AdminProductResource;
use App\Models\Product;
use App\Services\Documents\ProductDocumentAnalysisService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use LogicException;
use Throwable;

class EditAdminProduct extends EditRecord
{
    protected static string $resource = AdminProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
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
                        $run = $analysisService->start($this->record, auth()->user());

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
            Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record instanceof Product && $this->record->canBeApprovedByAdmin())
                ->action(function (): void {
                    $this->handleReviewDecision(
                        action: fn (Product $product): bool => $product->approveByAdmin(),
                        successTitle: 'Product approved',
                    );
                }),
            Action::make('requestClarification')
                ->label('Ask for clarification')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('warning')
                ->form([
                    Textarea::make('clarification_note')
                        ->label('Note for the distributor')
                        ->placeholder('Explain what needs to be corrected or provided…')
                        ->required()
                        ->rows(4)
                        ->maxLength(2000),
                ])
                ->visible(fn (): bool => $this->record instanceof Product && $this->record->canHaveClarificationRequestedByAdmin())
                ->action(function (array $data): void {
                    $this->handleReviewDecision(
                        action: fn (Product $product): bool => $product->requestClarificationByAdmin($data['clarification_note'] ?? null),
                        successTitle: 'Clarification requested',
                    );
                }),
            Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record instanceof Product && $this->record->canBeRejectedByAdmin())
                ->action(function (): void {
                    $this->handleReviewDecision(
                        action: fn (Product $product): bool => $product->rejectByAdmin(),
                        successTitle: 'Product rejected',
                    );
                }),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }

    /**
     * @param  callable(Product): bool  $action
     */
    private function handleReviewDecision(callable $action, string $successTitle): void
    {
        if (! ($this->record instanceof Product)) {
            return;
        }

        if (! $action($this->record)) {
            Notification::make()
                ->warning()
                ->title('Product review status could not be updated')
                ->body('Only products that are currently under review can be approved, rejected, or sent back for clarification.')
                ->send();

            return;
        }

        Notification::make()
            ->success()
            ->title($successTitle)
            ->send();

        $this->refreshFormData(['status']);
    }
}
