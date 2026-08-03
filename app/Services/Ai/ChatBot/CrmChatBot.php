<?php

declare(strict_types=1);

namespace App\Services\Ai\ChatBot;

use App\Models\AiAssistantLog;
use App\Models\User;
use App\Services\Ai\ChatBot\Contracts\ChatIntent;
use App\Services\Ai\ChatBot\Intents\ContactLookupIntent;
use App\Services\Ai\ChatBot\Intents\ContactsActiveIntent;
use App\Services\Ai\ChatBot\Intents\ContactsInactiveIntent;
use App\Services\Ai\ChatBot\Intents\ContactsIntent;
use App\Services\Ai\ChatBot\Intents\DealsIntent;
use App\Services\Ai\ChatBot\Intents\DealsOverdueIntent;
use App\Services\Ai\ChatBot\Intents\DealsRecentIntent;
use App\Services\Ai\ChatBot\Intents\DealsStalledIntent;
use App\Services\Ai\ChatBot\Intents\DealsStatusIntent;
use App\Services\Ai\ChatBot\Intents\DealsSummaryIntent;
use App\Services\Ai\ChatBot\Intents\DealsTopIntent;
use App\Services\Ai\ChatBot\Intents\HelpIntent;
use App\Services\Ai\ChatBot\Intents\ReportsIntent;
use App\Services\Ai\ChatBot\Intents\TsvByCompanyIntent;
use App\Services\Ai\ChatBot\Intents\TsvByContactIntent;
use App\Services\Ai\ChatBot\Intents\TsvByDealIntent;
use App\Services\Ai\ChatBot\Intents\TsvDetailIntent;
use App\Services\Ai\ChatBot\Intents\TsvIntent;
use App\Services\Ai\ChatBot\Intents\TsvSummaryIntent;
use App\Services\Ai\ChatBot\Support\DateRangeParser;
use App\Services\Ai\ChatBot\Support\IntentResolver;
use App\Services\Ai\ChatBot\Support\MessageParser;

final class CrmChatBot
{
    private IntentResolver $resolver;

    private MessageParser $parser;

    /**
     * @var array<int, ChatIntent>
     */
    private array $intents;

    /**
     * @var array<int, ChatIntent>
     */
    private array $allIntents;

    public function __construct()
    {
        $this->resolver = new IntentResolver;
        $this->parser = new MessageParser(new DateRangeParser);

        $tsvLeaves = [
            new TsvSummaryIntent,
            new TsvByContactIntent,
            new TsvByCompanyIntent,
            new TsvByDealIntent,
            new TsvDetailIntent,
        ];

        $contactsLeaves = [
            new ContactsActiveIntent,
            new ContactsInactiveIntent,
        ];

        $this->intents = [
            new HelpIntent,
            new DealsIntent([
                new DealsSummaryIntent,
                new DealsStatusIntent,
                new DealsTopIntent,
                new DealsStalledIntent,
                new DealsOverdueIntent,
                new DealsRecentIntent,
            ]),
            new TsvIntent($tsvLeaves),
            new ContactsIntent($contactsLeaves),
            new ReportsIntent(array_merge($tsvLeaves, $contactsLeaves)),
            new ContactLookupIntent,
        ];

        $this->allIntents = [];

        foreach ($this->intents as $intent) {
            $this->allIntents[] = $intent;
            array_push($this->allIntents, ...$intent->children());
        }
    }

    /**
     * Route a message to the correct intent, handle follow-up questions and log
     * the exchange for audit purposes.
     */
    public function ask(string $message, User $user): BotReply
    {
        $started = microtime(true);
        $reply = null;

        try {
            $reply = $this->handle($message, $user);

            return $reply;
        } finally {
            $this->log($message, $user, $reply, $started);
        }
    }

    private function handle(string $message, User $user): BotReply
    {
        $state = BotState::fromSession($user);
        $params = $this->parser->params($message);

        // 1. Continue an intent waiting for a required parameter.
        if ($state->pendingIntent !== null) {
            $intent = $this->findIntent($state->pendingIntent);

            if ($intent !== null && $this->providedParams($intent, $params) !== []) {
                $merged = array_replace($state->params, $params);
                $reply = $intent->handle($merged, $user);

                if ($reply->isAsking()) {
                    $state->pendingIntent = $reply->intent ?? $intent->key();
                    $state->params = $merged;
                    $state->save($user);

                    return $this->withIntentKey($reply, $intent);
                }

                $state->clear($user);

                return $this->withIntentKey($reply, $intent);
            }

            $state->clear($user);
        }

        // 2. Resolve the best intent across the whole tree.
        $intent = $this->resolver->resolve($message, $this->allIntents)
            ?? $this->findIntent('help')
            ?? $this->intents[0];

        $reply = $intent->handle($params, $user);

        if ($reply->isAsking()) {
            $state->pendingIntent = $reply->intent ?? $intent->key();
            $state->params = $params;
            $state->save($user);
        }

        return $this->withIntentKey($reply, $intent);
    }

    private function withIntentKey(BotReply $reply, ChatIntent $intent): BotReply
    {
        return $reply->intent === null ? $reply->withIntent($intent->key()) : $reply;
    }

    private function findIntent(string $key): ?ChatIntent
    {
        foreach ($this->intents as $intent) {
            if ($intent->key() === $key) {
                return $intent;
            }

            foreach ($intent->children() as $child) {
                if ($child->key() === $key) {
                    return $child;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function providedParams(ChatIntent $intent, array $params): array
    {
        $required = $intent->requires();

        return array_filter(
            array_intersect_key($params, array_flip($required)),
            fn ($value) => $value !== null,
        );
    }

    private function log(string $message, User $user, ?BotReply $reply, float $started): void
    {
        try {
            AiAssistantLog::create([
                'user_id' => $user->getKey(),
                'tool' => $reply?->intent,
                'duration' => (int) ((microtime(true) - $started) * 1000),
                'question' => $message,
                'response' => json_encode([
                    'answer' => $reply?->answer,
                    'suggestions' => $reply?->suggestions,
                    'intent' => $reply?->intent,
                    'is_asking' => $reply?->isAsking(),
                ]),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
