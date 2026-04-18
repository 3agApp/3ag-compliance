<?php

namespace App\Ai;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

class ProductDocumentBatchAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'TEXT'
You review product compliance documents in small batches.

Inspect the attached files for each listed document_id and return exactly one structured result per document.

Rules:
- score must be an integer from 0 to 100
- rating must be one of compliant, warning, non_compliant, or inconclusive
- findings must be concise factual observations, not speculation
- if a document is unreadable or unclear, return inconclusive and explain why
- never omit a listed document_id
TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'batch_summary' => $schema->string()->required(),
            'documents' => $schema->array()->required()->items(
                $schema->object([
                    'document_id' => $schema->integer()->required(),
                    'score' => $schema->integer()->min(0)->max(100)->required(),
                    'rating' => $schema->string()->required(),
                    'summary' => $schema->string()->required(),
                    'findings' => $schema->array()->required()->items(
                        $schema->string()
                    ),
                ])
            ),
        ];
    }
}
