<?php

declare(strict_types=1);

namespace ElliottLawson\EmailManagement\Tools;

use ElliottLawson\EmailManagement\Models\CategorizedMessage;
use Prism\Prism\Facades\Tool;
use Prism\Prism\Tool as PrismTool;

class GetEmailDigestTool
{
    public static function make(): PrismTool
    {
        return Tool::as('get_email_digest')
            ->for('Get a categorized digest of recent emails. Returns counts by category and recent subjects for actionable categories. Use this when the user asks about their email, wants a summary, or asks what needs attention.')
            ->withStringParameter('hours', 'Time range in hours to look back (default: 24)', required: false)
            ->using(function (string $hours = '24'): string {
                $userId = context('user_id');

                if (! $userId) {
                    return 'Unable to determine current user.';
                }

                $hoursInt = max(1, (int) $hours);
                $since = now()->subHours($hoursInt);

                $messages = CategorizedMessage::query()
                    ->where('user_id', $userId)
                    ->where('processed_at', '>=', $since)
                    ->orderByDesc('message_date')
                    ->get();

                if ($messages->isEmpty()) {
                    return json_encode([
                        'status' => 'empty',
                        'message' => "No categorized emails in the last {$hoursInt} hours. The triage process may not have run yet, or there are no new messages.",
                        'hours' => $hoursInt,
                    ]);
                }

                $grouped = $messages->groupBy('category');

                $summary = [
                    'hours' => $hoursInt,
                    'total' => $messages->count(),
                    'categories' => [],
                ];

                $actionableCategories = ['action_required', 'needs_reply', 'financial', 'calendar'];

                foreach ($grouped as $category => $items) {
                    $categoryData = [
                        'count' => $items->count(),
                    ];

                    // Include recent subjects for actionable categories
                    if (in_array($category, $actionableCategories)) {
                        $categoryData['recent'] = $items->take(5)->map(fn ($msg) => [
                            'subject' => $msg->subject,
                            'sender' => $msg->sender,
                            'date' => $msg->message_date?->toIso8601String(),
                        ])->values()->all();
                    }

                    $summary['categories'][$category] = $categoryData;
                }

                // Sort: actionable categories first
                uksort($summary['categories'], function ($a, $b) use ($actionableCategories) {
                    $aActionable = in_array($a, $actionableCategories);
                    $bActionable = in_array($b, $actionableCategories);

                    if ($aActionable && ! $bActionable) {
                        return -1;
                    }
                    if (! $aActionable && $bActionable) {
                        return 1;
                    }

                    return strcmp($a, $b);
                });

                return json_encode($summary, JSON_PRETTY_PRINT);
            });
    }
}
