<?php

declare(strict_types=1);

namespace App\Services\Ai\ChatBot\Support;

use App\Services\Ai\ChatBot\Contracts\ChatIntent;

final class IntentResolver
{
    /**
     * Score every intent against the message using keyword => weight pairs and
     * return the best match, or null when nothing scores above zero.
     *
     * @param  array<int, ChatIntent>  $intents
     */
    public function resolve(string $message, array $intents): ?ChatIntent
    {
        $best = null;
        $bestScore = 0;

        foreach ($intents as $intent) {
            $score = $this->score($message, $intent);

            if ($score > $bestScore) {
                $best = $intent;
                $bestScore = $score;
            }
        }

        return $best;
    }

    private function score(string $message, ChatIntent $intent): int
    {
        $score = 0;

        foreach ($intent->keywords() as $keyword => $weight) {
            if ($this->matches($message, (string) $keyword)) {
                $score += $weight;
            }
        }

        return $score;
    }

    private function matches(string $message, string $keyword): bool
    {
        $keyword = trim($keyword);

        if ($keyword === '') {
            return false;
        }

        $needle = preg_quote($keyword, '/');

        return (bool) preg_match('/\b'.$needle.'\b/iu', $message);
    }
}
