<x-filament-panels::page>
    <div class="space-y-6">
        <form class="space-y-6">
            {{ $this->form }}
            <div class="flex gap-3">
                <x-filament::button type="button" wire:click="preview" color="gray" icon="heroicon-o-magnifying-glass">
                    {{ __('ConferenceDiscountEligibility::messages.preview') }}
                </x-filament::button>
                <x-filament::button type="button" wire:click="import" icon="heroicon-o-arrow-up-tray">
                    {{ __('ConferenceDiscountEligibility::messages.run_import') }}
                </x-filament::button>
            </div>
        </form>

        @if ($report)
            <x-filament::section>
                <x-slot name="heading">{{ __('ConferenceDiscountEligibility::messages.import_report') }}</x-slot>
                <div class="grid grid-cols-2 gap-4 md:grid-cols-6">
                    @foreach (($report['counts'] ?? []) as $key => $value)
                        <div><div class="text-sm text-gray-500">{{ __('ConferenceDiscountEligibility::messages.' . $key) }}</div><div class="text-xl font-semibold">{{ $value }}</div></div>
                    @endforeach
                </div>
                <div class="mt-6 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr><th class="p-2 text-left">{{ __('ConferenceDiscountEligibility::messages.line') }}</th><th class="p-2 text-left">{{ __('ConferenceDiscountEligibility::messages.email') }}</th><th class="p-2 text-left">{{ __('ConferenceDiscountEligibility::messages.status') }}</th><th class="p-2 text-left">{{ __('ConferenceDiscountEligibility::messages.errors') }}</th></tr></thead>
                        <tbody>
                        @foreach (array_slice($report['rows'] ?? [], 0, 500) as $row)
                            <tr class="border-t"><td class="p-2">{{ $row['line'] ?? '' }}</td><td class="p-2">{{ $row['original_email'] ?? $row['email'] ?? '' }}</td><td class="p-2">{{ $row['status'] ?? '' }}</td><td class="p-2">{{ implode(', ', $row['errors'] ?? []) }}</td></tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
