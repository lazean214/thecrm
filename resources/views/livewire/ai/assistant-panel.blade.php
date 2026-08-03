<div x-data="{ isOpen: $wire.entangle('isOpen') }"
     x-show="isOpen"
     x-on:keydown.window.escape="isOpen = false"
     class="relative z-50"
     style="display: none;">

    <!-- Backdrop -->
    <div x-show="isOpen"
         x-transition:enter="transition-opacity ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-zinc-950/40 backdrop-blur-sm"
         x-on:click="isOpen = false"></div>

    <!-- Slide-over panel container -->
    <div class="fixed inset-y-0 right-0 flex max-w-full pl-10">
        <div x-show="isOpen"
             x-transition:enter="transform transition ease-in-out duration-300"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transform transition ease-in-out duration-300"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             class="w-screen max-w-md pointer-events-auto">

            <div class="flex flex-col h-full bg-white shadow-2xl dark:bg-zinc-900 border-l border-zinc-200 dark:border-zinc-800">
                <!-- Header -->
                <div class="px-6 py-5 bg-gradient-to-r from-indigo-600 via-indigo-700 to-violet-800 dark:from-indigo-950 dark:to-purple-950 text-white flex items-center justify-between border-b border-zinc-200 dark:border-zinc-800">
                    <div class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-indigo-200 animate-pulse">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 21L14.907 18m4.917-4.917-1.432-1.432a6.002 6.002 0 0 1-8.387-.001c-.111-.112-.224-.226-.335-.341a6.002 6.002 0 0 1 0-8.388l1.432-1.432m1.996 1.996 1.432 1.432m1.996 1.996 1.432 1.432M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm-3 1.5V3" />
                        </svg>
                        <div>
                            <h2 class="text-lg font-semibold tracking-tight">CRM ChatBot</h2>
                            <p class="text-xs text-indigo-200/90">Deterministic answers from your CRM data</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button"
                                wire:click="clearChat"
                                title="Clear conversation"
                                class="p-1.5 rounded-lg text-indigo-200 hover:text-white hover:bg-white/10 transition-colors cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                        </button>
                        <button type="button"
                                x-on:click="isOpen = false"
                                class="p-1.5 rounded-lg text-indigo-200 hover:text-white hover:bg-white/10 transition-colors cursor-pointer">
                            <span class="sr-only">Close panel</span>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Chat history area -->
                <div id="chat-messages-container" class="flex-1 overflow-y-auto px-6 py-6 space-y-4 bg-zinc-50 dark:bg-zinc-950 scroll-smooth">
                    @foreach ($messages as $message)
                        <div class="flex flex-col {{ $message['role'] === 'user' ? 'items-end' : 'items-start' }}">
                            <div class="max-w-[85%] rounded-2xl px-4 py-3 shadow-sm text-sm
                                {{ $message['role'] === 'user'
                                    ? 'bg-indigo-600 dark:bg-indigo-700 text-white rounded-tr-none'
                                    : 'bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-800 text-zinc-800 dark:text-zinc-100 rounded-tl-none'
                                }}">
                                
                                @if ($loop->last && $message['role'] === 'assistant' && !$isLoading)
                                    <!-- Typing simulation for the latest assistant response -->
                                    <div x-data="{
                                        text: '',
                                        fullText: @js($message['content']),
                                        init() {
                                            let index = 0;
                                            let interval = setInterval(() => {
                                                if (index < this.fullText.length) {
                                                    this.text += this.fullText[index];
                                                    index++;
                                                    let container = document.getElementById('chat-messages-container');
                                                    if (container) container.scrollTop = container.scrollHeight;
                                                } else {
                                                    clearInterval(interval);
                                                }
                                            }, 6);
                                        }
                                    }">
                                        <p class="leading-relaxed whitespace-pre-wrap" x-text="text"></p>
                                    </div>
                                @else
                                    <p class="leading-relaxed whitespace-pre-wrap">{{ $message['content'] }}</p>
                                @endif
                            </div>

                            <!-- View deals link (only for assistant messages) -->
                            @if ($message['role'] === 'assistant' && ! empty($message['dealsUrl']))
                                <div class="mt-2 max-w-[85%]">
                                    <a href="{{ $message['dealsUrl'] }}" target="_blank" rel="noopener"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-950/40 dark:hover:bg-indigo-900/60 dark:text-indigo-300 border border-indigo-100/50 dark:border-indigo-950 transition-colors cursor-pointer">
                                        View deals
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12l-7.5 7.5M21 12H3" />
                                        </svg>
                                    </a>
                                </div>
                            @endif

                            <!-- Suggestion chips (only for assistant messages) -->
                            @if ($message['role'] === 'assistant' && !empty($message['suggestions']))
                                <div class="mt-2 flex flex-wrap gap-1.5 max-w-[85%]">
                                    @foreach ($message['suggestions'] as $suggestion)
                                        <button type="button"
                                                wire:click="selectSuggestion(@js($suggestion))"
                                                class="px-3 py-1 rounded-full text-xs bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-950/40 dark:hover:bg-indigo-900/60 dark:text-indigo-300 border border-indigo-100/50 dark:border-indigo-950 transition-colors cursor-pointer">
                                            {{ $suggestion }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach

                    <!-- Loading Indicator -->
                    @if ($isLoading)
                        <div class="flex flex-col items-start">
                            <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-800 px-4 py-3 rounded-2xl rounded-tl-none shadow-sm">
                                <div class="flex items-center space-x-1.5">
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400 mr-1.5">Querying CRM data</span>
                                    <div class="w-1.5 h-1.5 bg-indigo-500 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                                    <div class="w-1.5 h-1.5 bg-indigo-500 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                                    <div class="w-1.5 h-1.5 bg-indigo-500 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                                </div>
                                <p class="text-[10px] text-zinc-400 dark:text-zinc-500 mt-1">Looking up your data</p>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Input form area -->
                <div class="p-4 bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-800">
                    <form wire:submit.prevent="sendMessage" class="flex gap-2">
                        <div class="relative flex-1">
                            <input type="text"
                                   wire:model="input"
                                   placeholder="Ask me a question about CRM..."
                                   class="w-full pl-4 pr-10 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-600 transition-shadow"
                                   @if($isLoading) disabled @endif>
                        </div>
                        <button type="submit"
                                class="inline-flex items-center justify-center p-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white dark:bg-indigo-700 dark:hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors cursor-pointer"
                                @if($isLoading || trim($input) === '') disabled @endif>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        // Automatically scroll to bottom when requested
        Livewire.on('scroll-chat-to-bottom', () => {
            setTimeout(() => {
                let container = document.getElementById('chat-messages-container');
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            }, 50);
        });
    });
</script>
