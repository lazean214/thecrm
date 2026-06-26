<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use App\Models\Deal;
use App\Models\ActivityLog;
use App\Services\DealEmailService;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public ?int $dealId = null;

    public string $type = 'tasks';
    public string $activityName = '';
    public string $message = '';
    public string $userEmail = '';
    public ?int $emailTemplateId = 2;

    public function mount(?int $dealId = null): void
    {
        $this->dealId = $dealId;
        $this->userEmail = Deal::find($dealId)?->user()->first()?->email ?? '';
    }

    #[Computed]
    public function activities()
    {
        return ActivityLog::where('deal_id', $this->dealId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function submit(): void
{
    $this->validate([
        'type'            => 'required|string|in:tasks,email,todo',
        'activityName'    => 'required|string|max:255',
        'message'         => 'nullable|string',
        'userEmail'       => 'required|email',
        'emailTemplateId' => 'required|integer|exists:email_templates,id',
    ]);

    $status = in_array($this->type, ['tasks', 'todo']) ? 'pending' : null;

    // 1. Create the new Activity Log item
    $newActivity = ActivityLog::create([
        'deal_id'       => $this->dealId,
        'type'          => $this->type,
        'activity_name' => $this->activityName,
        'message'       => $this->message,
        'user_email'    => Auth::user()?->email ?? 'anonymous@system.com',
        'status'        => $status,
    ]);

    // 2. Locate the Deal and its assigned Owner
    $deal = Deal::with('user')->find($this->dealId);
    
    if ($deal && $deal->user) {
        // 🧪 TEMPORARY: Bypassed self-notification checks so you can test locally
        // if ($deal->user->email !== Auth::user()?->email) {
            
        $deal->user->notify(new \App\Notifications\DealActivityNotification($newActivity));
            
        // }
    }

    // 3. Handle email template actions if it was an email item
    if ($this->type === 'email') {
        $deal = $deal ?? Deal::find($this->dealId);

        DealEmailService::send(
            deal: $deal,
            templateId: $this->emailTemplateId,
            to: $this->userEmail,
            customSubject: $this->activityName ?: null,
            customBody: $this->message ?: null,
        );
    }

    // 5. Reset inputs
    $this->reset(['activityName', 'message']);
    unset($this->activities);
}

    public function toggleStatus(int $id): void
    {
        $activity = ActivityLog::findOrFail($id);

        if (! in_array($activity->type, ['tasks', 'todo'])) {
            return;
        }

        $activity->update([
            'status' => $activity->status === 'completed' ? 'pending' : 'completed',
        ]);

        unset($this->activities);
    }
};
?>

<div>
    <div class="bg-white p-4 rounded-lg shadow mb-4 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100">
        <form wire:submit.prevent="submit">
            <div class="flex items-center justify-between gap-4 mb-4">
                <select wire:model.live="type" class="border border-gray-300 rounded px-3 py-2">
                    <option value="tasks" class="block w-full pl-4 pr-3 py-2 text-sm bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition">Tasks</option>
                    <option value="email" class="block w-full pl-4 pr-3 py-2 text-sm bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition">Email</option>
                    <option value="todo" class="block w-full pl-4 pr-3 py-2 text-sm bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition">To Do</option>
                </select>

                <input
                    wire:model="activityName"
                    type="text"
                    class="block w-full pl-4 pr-3 py-2 text-sm bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition"
                    placeholder="{{ $type === 'email' ? 'Subject' : 'Activity Name' }}"
                />
            </div>

            <div class="flex gap-4 mb-4">
                    <input
                        wire:model="userEmail"
                        type="email"
                        class="block w-full pl-4 pr-3 py-2 text-sm bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition"
                        placeholder="Recipient email"
                    />
                    <select wire:model="emailTemplateId" class="block w-full pl-4 pr-3 py-2 text-sm bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition">
                        <option value="">Select template…</option>
                        @foreach(\App\Models\EmailTemplate::all() as $template)
                            <option value="{{ $template->id }}">{{ $template->name }}</option>
                        @endforeach
                    </select>
                </div>

            <textarea
                wire:model="message"
                class="block w-full pl-4 pr-3 py-2 text-sm bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition mb-4"
                placeholder="{{ $type === 'email' ? 'Email body (leave blank to use template)' : 'Description' }}"
            ></textarea>

            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="submit"
                class="inline-flex items-center gap-2 rounded bg-green-500 text-xs px-4 py-2 text-white disabled:cursor-not-allowed disabled:opacity-70">

                <span wire:loading.remove wire:target="submit">
                    {{ $type === 'email' ? 'Send Email' : 'Send Activity' }}
                </span>

                <span wire:loading.flex wire:target="submit" class="items-center gap-2">
                    <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    Sending...
                </span>

            </button>
        </form>
    </div>

    <div class="bg-white p-4 rounded-lg shadow dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100">
        <h2 class="text-xl font-bold mb-4 dark:text-slate-100">Activity Feed</h2>

        <div>
            @forelse ($this->activities as $activity)
                <div class="border-b border-gray-200 py-3 dark:border-slate-700">
                    <div class="flex items-start gap-3">

                        {{-- Status toggle for tasks/todos --}}
                        @if (in_array($activity->type, ['tasks', 'todo']))
                            <button
                                wire:click="toggleStatus({{ $activity->id }})"
                                wire:loading.attr="disabled"
                                wire:target="toggleStatus({{ $activity->id }})"
                                title="{{ $activity->status === 'completed' ? 'Mark as pending' : 'Mark as completed' }}"
                                class="mt-0.5 flex-shrink-0 h-5 w-5 rounded-full border-2 transition
                                    {{ $activity->status === 'completed'
                                        ? 'border-emerald-500 bg-emerald-500'
                                        : 'border-slate-400 bg-white dark:bg-slate-800 hover:border-emerald-400' }}">
                                @if ($activity->status === 'completed')
                                    <svg class="h-full w-full text-white" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 00-1.414 0L8 12.586 4.707 9.293a1 1 0 00-1.414 1.414l4 4a1 1 0 001.414 0l8-8a1 1 0 000-1.414z" clip-rule="evenodd" />
                                    </svg>
                                @endif
                            </button>
                        @else
                            <div class="mt-0.5 flex-shrink-0 h-5 w-5 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center">
                                <svg class="h-3 w-3 text-indigo-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                                </svg>
                            </div>
                        @endif

                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                {{-- Type badge --}}
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium
                                    @if ($activity->type === 'tasks') bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300
                                    @elseif ($activity->type === 'todo') bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300
                                    @else bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300
                                    @endif">
                                    {{ $activity->type === 'tasks' ? 'Task' : ($activity->type === 'todo' ? 'To Do' : 'Email') }}
                                </span>

                                {{-- Activity name --}}
                                <span class="text-sm font-medium text-slate-800 dark:text-slate-100
                                    {{ $activity->status === 'completed' ? 'line-through text-slate-400 dark:text-slate-500' : '' }}">
                                    {{ $activity->activity_name }}
                                </span>

                                {{-- Status badge for tasks/todos --}}
                                @if ($activity->status)
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium
                                        {{ $activity->status === 'completed'
                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
                                            : 'bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400' }}">
                                        {{ ucfirst($activity->status) }}
                                    </span>
                                @endif
                            </div>

                            @if ($activity->user_email)
                                <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                    {{ $activity->user_email }}
                                </div>
                            @endif

                            @if ($activity->message)
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ $activity->message }}</p>
                            @endif

                            <div class="mt-1 text-xs text-slate-400 dark:text-slate-500">
                                {{ $activity->created_at->diffForHumans() }}
                            </div>
                        </div>
                    </div>

                    <div class="ml-8 mt-2">
                        <livewire:activities.task.comment :activity-id="$activity->id" />
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-400">No activity yet.</p>
            @endforelse
        </div>
    </div>
</div>