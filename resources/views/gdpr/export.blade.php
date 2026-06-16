<x-layouts::app :title="__('GDPR Export')">


    <div class="max-w-2xl mx-auto">
        <flux:card>
            <div class="p-8 text-center">


                <flux:heading size="xl" class="mb-2">GDPR Data Export</flux:heading>
                <flux:subheading class="mb-6">Request a copy of your personal data</flux:subheading>

                @if (session('status'))
                    <div class="mb-6 p-4 rounded-lg bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800">
                        <flux:heading size="sm" class="!text-green-800 dark:!text-green-200 mb-2">Export Requested</flux:heading>
                        <p class="text-sm text-green-700 dark:text-green-300">{{ session('status') }}</p>

                        @if (session('export_token'))
                            <div class="mt-4 pt-4 border-t border-green-200 dark:border-green-800">
                                <flux:button icon="arrow-down-tray" variant="ghost" size="sm"
                                    :href="route('gdpr.export.download', session('export_token'))"
                                    class="!text-green-800 dark:!text-green-200">
                                    Download My Data (JSON)
                                </flux:button>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="text-left space-y-4 mb-8">
                    <p>Under GDPR regulations, you have the right to request a copy of all personal data we hold about
                        you.</p>

                    <p>This export will include:</p>
                    <ul class="list-disc list-inside space-y-1 text-gray-600 dark:text-gray-400">
                        <li>Your profile information</li>
                        <li>Your contact details</li>
                        <li>Companies you're associated with</li>
                        <li>Deal history</li>
                        <li>Communication logs</li>
                    </ul>

                    <div class="bg-blue-50 dark:bg-blue-950 p-4 rounded-lg">
                        <p class="text-sm text-blue-800 dark:text-blue-200">
                            <strong>Note:</strong> Once requested, you will receive a download link via email.
                            The link will be valid for <strong>7 days</strong>.
                        </p>
                    </div>
                </div>

                <form method="POST" action="{{ route('gdpr.export.request') }}">
                    @csrf

                    <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-6 rounded-lg transition">
                        Request My Data Export
                    </button>
                </form>
            </div>
        </flux:card>
    </div>

</x-layouts::app>
