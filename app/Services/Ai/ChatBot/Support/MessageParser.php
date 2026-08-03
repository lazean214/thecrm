<?php

declare(strict_types=1);

namespace App\Services\Ai\ChatBot\Support;

final class MessageParser
{
    public function __construct(private readonly DateRangeParser $dateRangeParser) {}

    /**
     * Extract structured parameters from a free-form message.
     *
     * @return array<string, mixed>
     */
    public function params(string $message): array
    {
        $text = mb_strtolower(trim($message));

        return [
            'date_range' => $this->dateRangeParser->detect($message),
            'currency' => $this->currency($text),
            'deal_status' => $this->dealStatus($text),
            'limit' => $this->limit($text),
            'query' => $this->query($message),
        ];
    }

    private function currency(string $text): string
    {
        if (preg_match('/(?:usd|dollars?|\$)/', $text)) {
            return 'usd';
        }

        if (preg_match('/(?:eur|euros?|€)/', $text)) {
            return 'eur';
        }

        return 'gbp';
    }

    private function dealStatus(string $text): ?string
    {
        if (preg_match('/\b(?:lost|losing)\b/', $text)) {
            return 'lost';
        }

        if (preg_match('/\b(?:active|open|won)\b/', $text)) {
            return 'active';
        }

        return null;
    }

    private function limit(string $text): ?int
    {
        if (preg_match('/(?:top|recent|latest)\s+(\d{1,2})\b/', $text, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/\b(\d{1,2})\s+(?:top|recent|latest)\b/', $text, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function query(string $message): ?string
    {
        $patterns = [
            "/\bwho(?:'s| is)\s+(.+)/i",
            '/(?:find|search|look\s+up|look\s+for)\s+(?:the\s+)?(?:contact|person)\s+(?:named|called|for|by|with)?\s+(.+)/i',
            '/(?:find|search|look\s+up|look\s+for)\s+(?:a\s+)?(?:contact|person)\s+(.+)/i',
            '/(?:find|search|look\s+up|look\s+for)\s+(.+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                $term = trim($matches[1], " \t\n.,;:!?");
                $term = preg_replace('/\b(?:please|thanks|thank you|now|for me)\b.*$/i', '', $term);
                $term = trim((string) $term);

                if (mb_strlen($term) >= 2) {
                    return $term;
                }
            }
        }

        $trimmed = trim($message);

        if (mb_strlen($trimmed) >= 2
            && mb_strlen($trimmed) <= 60
            && ! preg_match('/^(?:what|which|who|where|show|tell|can|are|any|how|do|does|is|i|please|thanks|find|search|list|get)\b/i', $trimmed)) {
            return $trimmed;
        }

        return null;
    }
}
