<?php

declare(strict_types=1);

namespace ElliottLawson\EmailManagement\Services;

use App\Services\ModelResolver;
use ElliottLawson\EmailManagement\DTOs\TriageResult;
use ElliottLawson\EmailManagement\Models\CategorizedMessage;
use ElliottLawson\EmailManagement\Models\ProcessingCursor;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Facades\Prism;

class TriageService
{
    private const BATCH_SIZE = 20;

    public function __construct(
        private MailBridgeClient $client,
        private ModelResolver $modelResolver,
    ) {}

    /**
     * Triage new messages for a user across all their mailboxes.
     */
    public function triageUser(int $userId): TriageResult
    {
        $mailboxes = $this->client->listMailboxes($userId);

        $totalProcessed = 0;
        $totalSkipped = 0;
        $categoryCounts = [];

        foreach ($mailboxes as $mailbox) {
            $mailboxId = $mailbox['id'] ?? $mailbox['mailboxId'] ?? null;

            if (! $mailboxId) {
                continue;
            }

            $result = $this->triageMailbox($userId, (string) $mailboxId);
            $totalProcessed += $result->messagesProcessed;
            $totalSkipped += $result->skipped;

            foreach ($result->categoryCounts as $category => $count) {
                $categoryCounts[$category] = ($categoryCounts[$category] ?? 0) + $count;
            }
        }

        return new TriageResult($totalProcessed, $categoryCounts, $totalSkipped);
    }

    /**
     * Triage new messages in a specific mailbox.
     */
    public function triageMailbox(int $userId, string $mailboxId): TriageResult
    {
        $cursor = ProcessingCursor::query()
            ->where('user_id', $userId)
            ->where('mailbox_id', $mailboxId)
            ->where('cursor_type', 'triage')
            ->first();

        $filters = [];
        if ($cursor?->cursor_value) {
            $filters['since'] = $cursor->cursor_value;
        }

        $messages = $this->client->listMessages($userId, $mailboxId, $filters);

        if (empty($messages)) {
            return new TriageResult(0, []);
        }

        // Filter out already-categorized messages
        $existingIds = CategorizedMessage::query()
            ->where('user_id', $userId)
            ->where('mailbox_id', $mailboxId)
            ->whereIn('message_id', collect($messages)->pluck('id')->filter()->all())
            ->pluck('message_id')
            ->all();

        $newMessages = collect($messages)->filter(
            fn (array $msg) => isset($msg['id']) && ! in_array($msg['id'], $existingIds)
        )->values()->all();

        $skipped = count($messages) - count($newMessages);

        if (empty($newMessages)) {
            return new TriageResult(0, [], $skipped);
        }

        // Process in batches
        $categoryCounts = [];
        $processed = 0;

        foreach (array_chunk($newMessages, self::BATCH_SIZE) as $batch) {
            $results = $this->categorizeBatch($batch);

            foreach ($results as $index => $categorization) {
                $message = $batch[$index];

                CategorizedMessage::updateOrCreate(
                    [
                        'user_id' => $userId,
                        'mailbox_id' => $mailboxId,
                        'message_id' => $message['id'],
                    ],
                    [
                        'category' => $categorization['category'],
                        'confidence' => $categorization['confidence'],
                        'subject' => $message['subject'] ?? '',
                        'sender' => $message['from'] ?? $message['sender'] ?? '',
                        'message_date' => $message['date'] ?? $message['receivedAt'] ?? now(),
                        'processed_at' => now(),
                    ]
                );

                $category = $categorization['category'];
                $categoryCounts[$category] = ($categoryCounts[$category] ?? 0) + 1;
                $processed++;
            }
        }

        // Update cursor to latest message date
        $latestDate = collect($newMessages)
            ->pluck('date')
            ->filter()
            ->sort()
            ->last();

        if ($latestDate) {
            ProcessingCursor::updateOrCreate(
                [
                    'user_id' => $userId,
                    'mailbox_id' => $mailboxId,
                    'cursor_type' => 'triage',
                ],
                [
                    'cursor_value' => $latestDate,
                    'last_processed_at' => now(),
                ]
            );
        }

        return new TriageResult($processed, $categoryCounts, $skipped);
    }

    /**
     * Categorize a batch of messages using the LLM.
     *
     * @param  array<int, array<string, mixed>>  $messages
     * @return array<int, array{category: string, confidence: float}>
     */
    public function categorizeBatch(array $messages): array
    {
        $categories = config('email-management.categories', []);
        $minConfidence = config('email-management.thresholds.categorization_min_confidence', 0.7);

        $emailSummaries = [];
        foreach ($messages as $i => $msg) {
            $subject = $msg['subject'] ?? '(no subject)';
            $sender = $msg['from'] ?? $msg['sender'] ?? '(unknown)';
            $snippet = $msg['snippet'] ?? $msg['preview'] ?? '';

            $emailSummaries[] = "[{$i}] From: {$sender} | Subject: {$subject} | Preview: {$snippet}";
        }

        $categoriesList = implode(', ', $categories);
        $emailBlock = implode("\n", $emailSummaries);

        $prompt = <<<PROMPT
        Categorize each email into exactly one category.

        Categories: {$categoriesList}

        Emails:
        {$emailBlock}

        Respond with one JSON array. Each element: {"index": <int>, "category": "<category>", "confidence": <0.0-1.0>}
        Only output the JSON array, no other text.
        PROMPT;

        try {
            $modelString = config('email-management.categorization_model', 'anthropic:claude-haiku-4-5-20251001');
            [$provider, $model] = $this->modelResolver->parse($modelString);

            $response = Prism::text()
                ->using($provider, $model)
                ->withMaxTokens(4096)
                ->withPrompt($prompt)
                ->asText();

            $parsed = json_decode($response->text, true);

            if (! is_array($parsed)) {
                // Try to extract JSON from the response
                if (preg_match('/\[.*\]/s', $response->text, $matches)) {
                    $parsed = json_decode($matches[0], true);
                }
            }

            if (! is_array($parsed)) {
                Log::warning('TriageService: Failed to parse LLM response', [
                    'response' => $response->text,
                ]);

                return $this->fallbackCategorization($messages);
            }

            // Map indexed results back, applying confidence threshold
            $results = [];
            foreach ($messages as $i => $msg) {
                $match = collect($parsed)->firstWhere('index', $i);

                if ($match && in_array($match['category'], $categories) && ($match['confidence'] ?? 0) >= $minConfidence) {
                    $results[$i] = [
                        'category' => $match['category'],
                        'confidence' => (float) $match['confidence'],
                    ];
                } else {
                    $results[$i] = [
                        'category' => 'informational',
                        'confidence' => 0.5,
                    ];
                }
            }

            return $results;
        } catch (\Throwable $e) {
            Log::error('TriageService: LLM categorization failed', ['error' => $e->getMessage()]);

            return $this->fallbackCategorization($messages);
        }
    }

    /**
     * Fallback categorization when LLM is unavailable.
     *
     * @param  array<int, array<string, mixed>>  $messages
     * @return array<int, array{category: string, confidence: float}>
     */
    private function fallbackCategorization(array $messages): array
    {
        $results = [];
        foreach ($messages as $i => $msg) {
            $results[$i] = [
                'category' => 'informational',
                'confidence' => 0.3,
            ];
        }

        return $results;
    }
}
