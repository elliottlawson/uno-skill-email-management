<?php

declare(strict_types=1);

namespace ElliottLawson\EmailManagement\DTOs;

class TriageResult
{
    /**
     * @param  int  $messagesProcessed  Total messages processed in this run
     * @param  array<string, int>  $categoryCounts  Count per category
     * @param  int  $skipped  Messages skipped (already categorized or below threshold)
     */
    public function __construct(
        public readonly int $messagesProcessed,
        public readonly array $categoryCounts,
        public readonly int $skipped = 0,
    ) {}

    public function summary(): string
    {
        if ($this->messagesProcessed === 0) {
            return 'No new messages to triage.';
        }

        $parts = [];
        foreach ($this->categoryCounts as $category => $count) {
            $parts[] = "{$category}: {$count}";
        }

        $summary = "Triaged {$this->messagesProcessed} messages — ".implode(', ', $parts);

        if ($this->skipped > 0) {
            $summary .= " (skipped {$this->skipped})";
        }

        return $summary;
    }
}
