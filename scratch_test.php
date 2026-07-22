<?php

use App\Models\User;
use App\Services\Ai\CrmAssistantAgent;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$user = User::first();
echo 'User: '.$user->name.' (ID: '.$user->id.', Admin: '.($user->isAdmin() ? 'Yes' : 'No').', Sales: '.($user->isSalesTeam() ? 'Yes' : 'No').")\n";

$context = [
    'user' => [
        'id' => $user->id,
        'role' => $user->isAdmin() ? 'admin' : ($user->isSalesTeam() ? 'sales' : ($user->isComplianceTeam() ? 'compliance' : 'user')),
        'team_ids' => $user->teams->pluck('id')->toArray(),
    ],
    'current_datetime' => now()->toIso8601String(),
    'available_tools' => [
        [
            'name' => 'overdue_followups',
            'description' => 'Retrieve active deals that need follow-up based on stage-specific rules (doc sent > 24h, doc signed > 2d, compliant with no activity/comments > 3d, ready for payment > 7d).',
        ],
        [
            'name' => 'stalled_deals',
            'description' => 'Identify deals that have remained in their current stage for longer than X days. Arguments: days (integer, default 7).',
        ],
    ],
];

$systemPrompt = "You are the CRM AI assistant.\n".
    "Use only the provided tools.\n".
    "Never invent data.\n".
    "If a tool is required, return only the JSON tool request.\n".
    'If the tool result is provided, answer in 3 sentences max.';

$agent = new CrmAssistantAgent($systemPrompt, $context);
$response = $agent->prompt('list stalled');
echo "Turn 1 response:\n";
print_r($response->toArray());
echo 'Turn 1 text: '.$response->text."\n";
