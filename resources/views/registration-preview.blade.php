@php
    $conference = app()->getCurrentScheduledConference();
    $user = auth()->user();
    $preview = ($conference && $user)
        ? app(\ConferenceDiscountEligibility\Services\RegistrationPreviewService::class)->build((int) $conference->getKey(), $user)
        : ['eligible' => false, 'rows' => []];
@endphp

@if (($preview['eligible'] ?? false) && ! empty($preview['rows']))
    <x-filament::section class="mb-6" icon="heroicon-o-receipt-percent">
        <x-slot name="heading">{{ __('ConferenceDiscountEligibility::messages.approved_discount') }}</x-slot>
        <x-slot name="description">{{ __('ConferenceDiscountEligibility::messages.approved_discount_message') }}</x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                <tr class="border-b text-left">
                    <th class="p-2">{{ __('ConferenceDiscountEligibility::messages.registration_category') }}</th>
                    <th class="p-2">{{ __('ConferenceDiscountEligibility::messages.standard_fee') }}</th>
                    <th class="p-2">{{ __('ConferenceDiscountEligibility::messages.eligibility') }}</th>
                    <th class="p-2">{{ __('ConferenceDiscountEligibility::messages.discount') }}</th>
                    <th class="p-2">{{ __('ConferenceDiscountEligibility::messages.discount_amount') }}</th>
                    <th class="p-2">{{ __('ConferenceDiscountEligibility::messages.final_registration_fee') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($preview['rows'] as $row)
                    <tr class="border-b last:border-0">
                        <td class="p-2">{{ $row['category'] }}</td>
                        <td class="p-2">{{ money((float) $row['standard'], $row['currency'], true)->formatWithoutZeroes() }}</td>
                        <td class="p-2">{{ $row['reason'] ?: '—' }}</td>
                        <td class="p-2">{{ $row['discount_percentage'] }}%</td>
                        <td class="p-2">-{{ money((float) $row['discount'], $row['currency'], true)->formatWithoutZeroes() }}</td>
                        <td class="p-2 font-semibold">{{ money((float) $row['final'], $row['currency'], true)->formatWithoutZeroes() }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <p class="mt-4 text-sm text-gray-600 dark:text-gray-300">
            {{ __('ConferenceDiscountEligibility::messages.add_on_preview_note') }}
        </p>
    </x-filament::section>
@endif
