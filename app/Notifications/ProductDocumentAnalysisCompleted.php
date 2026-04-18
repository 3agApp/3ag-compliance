<?php

namespace App\Notifications;

use App\Models\DocumentAnalysisRun;
use App\Models\Product;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProductDocumentAnalysisCompleted extends Notification
{
    use Queueable;

    public function __construct(
        public Product $product,
        public DocumentAnalysisRun $run,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Document analysis completed')
            ->body($this->body())
            ->icon('heroicon-o-sparkles')
            ->status('success')
            ->getDatabaseMessage();
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'product_id' => $this->product->getKey(),
            'run_id' => $this->run->getKey(),
            'overall_score' => $this->run->result['overall_score'] ?? null,
            'detected_components' => $this->run->detected_components ?? [],
        ];
    }

    private function body(): string
    {
        $score = $this->run->result['overall_score'] ?? 0;
        $components = $this->run->detected_components ?? [];

        $body = sprintf('"%s" scored %d/100.', $this->product->name, $score);

        if (filled($components)) {
            $body .= ' Created components: '.implode(', ', $components).'.';
        }

        return $body;
    }
}
