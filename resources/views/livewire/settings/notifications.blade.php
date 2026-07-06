<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Notification preferences') }}</flux:heading>

    <x-settings.layout :heading="__('Notifications')" :subheading="__('Choose which events you want to receive email notifications for.')">
        <form wire:submit="save" class="my-6 w-full space-y-6">
            @foreach ($this->events as $key => $label)
                <div class="flex items-center justify-between">
                    <flux:label>{{ $label }}</flux:label>
                    <flux:switch wire:model.live="preferences.{{ $key }}.email" />
                </div>
            @endforeach

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </x-settings.layout>
</section>
