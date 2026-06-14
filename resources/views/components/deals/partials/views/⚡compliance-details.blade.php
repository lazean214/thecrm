<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<section x-data="{ expanded: true }"
    class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-5 shadow-sm mt-4">
    <div class="flex items-center justify-between">
        <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Compliance</h2>
        <button @click="expanded = !expanded"
            class="group inline-flex items-center justify-center rounded-lg p-1.5 transition hover:bg-slate-100 dark:hover:bg-slate-700">
            <svg xmlns="http://www.w3.org/2000/svg"
                class="h-4 w-4 text-slate-400 transition-all duration-300 ease-in-out group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300"
                :class="{ 'rotate-180': expanded }" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7-7-7 7" />
            </svg>
        </button>
    </div>

    <div x-show="expanded" x-collapse.duration.300ms>
        {{-- Compliance fields grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">

            {{-- Date Sent --}}
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">
                    Date Sent <span class="text-[10px] font-normal normal-case italic text-slate-400">(Signable)</span>
                </label>
                <input type="date" wire:model="date_sent"
                    class="block w-full px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition" />
            </div>

            {{-- Date Signed --}}
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">
                    Date Signed <span class="text-[10px] font-normal normal-case italic text-slate-400">(Signable)</span>
                </label>
                <input type="date" wire:model="date_signed"
                    class="block w-full px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition" />
            </div>

            {{-- Who Signed --}}
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">
                    Who Signed <span class="text-[10px] font-normal normal-case italic text-slate-400">(Signable)</span>
                </label>
                <input type="text" wire:model="who_signed"
                    class="block w-full px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition" />
            </div>

            {{-- Signed Document --}}
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Signed Document</label>
                <input type="text" wire:model="signed_doc"
                    class="block w-full px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition" />
            </div>

            {{-- Starter Checklist --}}
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Starter Checklist Date</label>
                <input type="date" wire:model="starter_checklist_recieved_date"
                    class="block w-full px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition" />
            </div>

            {{-- Starter Form --}}
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Starter Form</label>
                <select wire:model="starter_form"
                    class="block w-full px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                    <option value="" disabled>Select Form</option>
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="C">C</option>
                </select>
            </div>

            {{-- Tax Code --}}
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Tax Code</label>
                <select wire:model="tax_code"
                    class="block w-full px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                    <option value="" disabled>Select Code</option>
                    <option value="1257L">1257L</option>
                    <option value="1257L1">1257L1</option>
                    <option value="BR">BR</option>
                </select>
            </div>

            {{-- Contract Received --}}
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Contract Received Date</label>
                <input type="date" wire:model="contract_recieved_date"
                    class="block w-full px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition" />
            </div>

            {{-- Right to Work Document --}}
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Right to Work Document</label>
                <select wire:model="photo_id_passport"
                    class="block w-full px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                    <option value="" disabled selected>Select Document</option>
                    <option value="UK Passport">UK Passport</option>
                    <option value="Foreign Passport">Foreign Passport</option>
                    <option value="Irish Passport">Irish Passport</option>
                    <option value="Driving License">Driving License</option>
                </select>
            </div>

            {{-- Proof of Address --}}
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Proof of Address</label>
                <select wire:model="proof_of_address"
                    class="block w-full px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                    <option value="" disabled selected>Select Option</option>
                    <option value="Yes">Yes</option>
                    <option value="No">No</option>
                </select>
            </div>

            {{-- Right to Work --}}
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Right to Work</label>
                <select wire:model="right_to_work"
                    class="block w-full px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                    <option value="" disabled selected>Select Option</option>
                    <option value="Yes">Yes</option>
                    <option value="No">No</option>
                </select>
            </div>
        </div>

        {{-- Document Uploads --}}
        <div class="border-t border-slate-100 dark:border-slate-700 mt-6 pt-5 space-y-5">
            {{-- Compliance Documents --}}
            <div>
                <label class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 block mb-2">Compliance Documents</label>
                <input type="file" wire:model="compliance_documents" multiple
                    class="block w-full px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition" />
                <div wire:loading wire:target="compliance_documents" class="text-xs text-indigo-500 mt-2">
                    Uploading compliance files...
                </div>
                @error('compliance_documents.*')
                    <span class="text-red-500 text-xs">{{ $message }}</span>
                @enderror
                @if ($compliance_documents)
                    <div class="mt-3 space-y-1">
                        @foreach ($compliance_documents as $file)
                            <div class="text-xs text-slate-500 dark:text-slate-400">📄 {{ $file->getClientOriginalName() }}</div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Contract Documents --}}
            <div>
                <label class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 block mb-2">Contract Documents</label>
                <input type="file" wire:model="contract_documents" multiple
                    class="block w-full px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition" />
                <div wire:loading wire:target="contract_documents" class="text-xs text-indigo-500 mt-2">
                    Uploading contract files...
                </div>
                @error('contract_documents.*')
                    <span class="text-red-500 text-xs">{{ $message }}</span>
                @enderror
                @if ($contract_documents)
                    <div class="mt-3 space-y-1">
                        @foreach ($contract_documents as $file)
                            <div class="text-xs text-slate-500 dark:text-slate-400">📄 {{ $file->getClientOriginalName() }}</div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
