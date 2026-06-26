<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public int $activityId;

    public string $comment = '';
    public string $status  = 'open'; // Default assigned to prevent validation errors

    public bool  $showForm       = false;

    // Reply state
    public ?int  $replyingToId   = null;
    public string $replyComment  = '';

    // Edit state
    public ?int  $editingId      = null;
    public string $editComment   = '';
    public string $editStatus    = '';

    public function mount(int $activityId): void
    {
        $this->activityId = $activityId;
        $this->status     = ActivityLog::find($activityId)?->status ?? 'open';
    }

    #[Computed]
    public function activity(): ActivityLog
    {
        return ActivityLog::findOrFail($this->activityId);
    }

    #[Computed]
    public function comments()
    {
        return ActivityLog::where('parent_id', $this->activityId)
            ->where('type', 'comment')
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function addComment(): void
{
    $this->validate([
        'comment' => 'required|string|max:2000',
        // ✅ Added 'pending' to the allowed statuses array
        'status'  => 'required|string|in:pending,open,in_progress,done,cancelled',
    ]);

    // Update parent activity status
    ActivityLog::where('id', $this->activityId)
        ->update(['status' => $this->status]);

    $newComment = ActivityLog::create([
        'deal_id'       => $this->activity->deal_id,
        'parent_id'     => $this->activityId,
        'type'          => 'comment',
        'activity_name' => 'Comment',
        'message'       => $this->comment,
        'user_email'    => Auth::user()?->email ?? 'anonymous@system.com',
        'status'        => $this->status,
    ]);

    // Find the deal and its owner
    $deal = \App\Models\Deal::with('user')->find($this->activity->deal_id);

    if ($deal && $deal->user) {
        // 🧪 TEMPORARY: Comment out the self-notification check for local testing
        // if ($deal->user->email !== Auth::user()?->email) {
        
        $deal->user->notify(new \App\Notifications\DealCommentedNotification($newComment));
        
        // }
    }

    $this->reset('comment');
    $this->showForm = false;
    unset($this->activity, $this->comments);
}

    public function startReply(int $commentId): void
    {
        $this->replyingToId = $commentId;
        $this->replyComment = '';
    }

    public function cancelReply(): void
    {
        $this->replyingToId = null;
        $this->replyComment = '';
    }

    public function submitReply(): void
    {
        $this->validate([
            'replyComment' => 'required|string|max:2000',
        ]);

        $parent = ActivityLog::findOrFail($this->replyingToId);

        // ✅ Assigned to a precise instance variable
        $newReply = ActivityLog::create([
            'deal_id'       => $this->activity->deal_id,
            'parent_id'     => $this->replyingToId,
            'type'          => 'comment',
            'activity_name' => 'Reply',
            'message'       => $this->replyComment,
            'user_email'    => Auth::user()?->email ?? 'anonymous@system.com',
            'status'        => $parent->status,
        ]);

        // Find the original author of the comment being replied to
        $originalCommenter = \App\Models\User::where('email', $parent->user_email)->first();

        if ($originalCommenter) {
            // 🧪 TEMPORARY: Comment out the self-notification check for local testing
            // if ($originalCommenter->email !== Auth::user()?->email) {
            
            $originalCommenter->notify(new \App\Notifications\DealCommentedNotification($newReply));
            
            // }
        }

        $this->replyingToId = null;
        $this->replyComment = '';
        unset($this->comments);
    }

    public function startEdit(int $commentId): void
    {
        $comment           = ActivityLog::findOrFail($commentId);
        $this->editingId   = $commentId;
        $this->editComment = $comment->message;
        $this->editStatus  = $comment->status ?? $this->status;
    }

    public function cancelEdit(): void
    {
        $this->editingId   = null;
        $this->editComment = '';
        $this->editStatus  = '';
    }

    public function saveEdit(): void
    {
        $this->validate([
            'editComment' => 'required|string|max:2000',
            'editStatus'  => 'nullable|string|in:open,in_progress,done,cancelled',
        ]);

        ActivityLog::where('id', $this->editingId)
            ->update([
                'message' => $this->editComment,
                'status'  => $this->editStatus ?: null,
            ]);

        $this->editingId   = null;
        $this->editComment = '';
        $this->editStatus  = '';
        unset($this->comments);
    }

    public function deleteComment(int $commentId): void
    {
        ActivityLog::where('parent_id', $commentId)->delete();
        ActivityLog::where('id', $commentId)->delete();
        unset($this->comments);
    }
};
?>

<div class="bg-white rounded-lg shadow p-4 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100">

    {{-- Status badge on parent activity --}}
    <div class="flex items-center justify-between mb-4">
        <h3 class="font-semibold text-gray-700 dark:text-slate-100">Comments</h3>
        <span @class([
            'text-xs font-medium px-2 py-1 rounded-full',
            'bg-gray-100 text-gray-600'   => $this->activity->status === 'open',
            'bg-blue-100 text-blue-700'   => $this->activity->status === 'in_progress',
            'bg-green-100 text-green-700' => $this->activity->status === 'done',
            'bg-red-100 text-red-700'     => $this->activity->status === 'cancelled',
        ])>
            {{ str_replace('_', ' ', ucfirst($this->activity->status ?? 'open')) }}
        </span>
    </div>

    {{-- Comment list --}}
    <div class="space-y-4 mb-6">
        @forelse ($this->comments as $comment)
            <div class="flex flex-col gap-1" wire:key="comment-box-{{ $comment->id }}">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-bold text-gray-600">
                        {{ strtoupper(substr($comment->user_email, 0, 1)) }}
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-sm font-medium text-gray-800 dark:text-slate-200">{{ $comment->user_email }}</span>
                            <span class="text-xs text-gray-400">{{ $comment->created_at->diffForHumans() }}</span>
                            @if ($comment->status)
                                <span @class([
                                    'text-xs px-2 py-0.5 rounded-full',
                                    'bg-gray-100 text-gray-600'   => $comment->status === 'open',
                                    'bg-blue-100 text-blue-700'   => $comment->status === 'in_progress',
                                    'bg-green-100 text-green-700' => $comment->status === 'done',
                                    'bg-red-100 text-red-700'     => $comment->status === 'cancelled',
                                ])>
                                    {{ str_replace('_', ' ', ucfirst($comment->status)) }}
                                </span>
                            @endif
                        </div>

                        {{-- Edit mode --}}
                        @if ($editingId === $comment->id)
                            <div class="space-y-2" @click.stop>
                                <textarea
                                    wire:model="editComment"
                                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm text-black"
                                    rows="2"
                                ></textarea>
                                <select wire:model="status" class="border border-gray-300 rounded px-3 py-2 text-sm text-black">
                                    <option value="pending">Pending</option> <option value="open">Open</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="done">Done</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                                <div class="flex gap-2">
                                    <button wire:click="saveEdit" class="text-xs bg-green-500 text-white px-3 py-1 rounded">Save</button>
                                    <button wire:click="cancelEdit" class="text-xs text-gray-500 px-3 py-1 rounded border">Cancel</button>
                                </div>
                            </div>
                        @else
                            <p class="text-sm text-gray-700 dark:text-slate-300">{{ $comment->message }}</p>
                            <div class="flex gap-3 mt-1">
                                <button wire:click="startReply({{ $comment->id }})" class="text-xs text-blue-500 hover:underline">Reply</button>
                                @if (Auth::user()?->email === $comment->user_email)
                                    <button wire:click="startEdit({{ $comment->id }})" class="text-xs text-gray-400 hover:underline">Edit</button>
                                    <button wire:click="deleteComment({{ $comment->id }})" wire:confirm="Delete this comment and its replies?" class="text-xs text-red-400 hover:underline">Delete</button>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Replies --}}
                @php
                    $replies = \App\Models\ActivityLog::where('parent_id', $comment->id)->orderBy('created_at')->get();
                @endphp
                @if ($replies->isNotEmpty())
                    <div class="ml-11 space-y-3 mt-2">
                        @foreach ($replies as $reply)
                            <div class="flex items-start gap-2 border-l-2 border-gray-200 pl-3" wire:key="reply-item-{{ $reply->id }}">
                                <div class="flex-shrink-0 w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-500">
                                    {{ strtoupper(substr($reply->user_email, 0, 1)) }}
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-0.5">
                                        <span class="text-xs font-medium text-gray-700 dark:text-slate-300">{{ $reply->user_email }}</span>
                                        <span class="text-xs text-gray-400">{{ $reply->created_at->diffForHumans() }}</span>
                                    </div>
                                    <p class="text-sm text-gray-700 dark:text-slate-400">{{ $reply->message }}</p>
                                    @if (Auth::user()?->email === $reply->user_email)
                                        <button wire:click="deleteComment({{ $reply->id }})" wire:confirm="Delete this reply?" class="text-xs text-red-400 hover:underline mt-1">Delete</button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Inline reply box --}}
                @if ($replyingToId === $comment->id)
                    <div class="ml-11 mt-2" @click.stop>
                        <textarea
                            wire:model="replyComment"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm text-black"
                            rows="2"
                            placeholder="Write a reply…"
                        ></textarea>
                        <div class="flex gap-2 mt-1">
                            <button wire:click="submitReply" class="text-xs bg-blue-500 text-white px-3 py-1 rounded">Post Reply</button>
                            <button wire:click="cancelReply" class="text-xs text-gray-500 px-3 py-1 rounded border">Cancel</button>
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <p class="text-sm text-gray-400">No comments yet.</p>
        @endforelse
    </div>

    {{-- Add comment --}}
    <div class="border-t border-gray-100 pt-4 dark:border-slate-700">
        @if (!$showForm)
            <button
                type="button"
                wire:click="$set('showForm', true)"
                class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add comment
            </button>
        @else
            <div @click.stop class="space-y-2"> {{-- ✅ Form wrapping container to isolate focus clicks --}}
                <textarea
                    wire:model="comment"
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm text-black"
                    rows="3"
                    placeholder="Add a comment…"
                    autofocus
                ></textarea>
                
                <div class="flex items-center justify-between">
                    <select wire:model="status" class="border border-gray-300 rounded px-3 py-2 text-sm text-black">
                        <option value="open">Open</option>
                        <option value="in_progress">In Progress</option>
                        <option value="done">Done</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <div class="flex gap-2">
                        <button
                            type="button"
                            wire:click="$set('showForm', false)"
                            class="text-sm text-gray-400 px-3 py-2 rounded border hover:bg-gray-50 dark:hover:bg-slate-700"
                        >Cancel</button>
                        <button 
                            type="button"
                            wire:click="addComment" 
                            class="bg-green-500 text-white text-sm px-4 py-2 rounded shadow hover:bg-green-600"
                        >
                            Comment
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>

</div>