<div
    class="space-y-4"
    x-on:cde-coupon-updated.window="window.location.reload()"
>
    @if ($reservation && $reservation->coupon)
        <div class="rounded-lg border border-success-200 bg-success-50 p-4 text-sm dark:border-success-700 dark:bg-success-950/30">
            <div class="font-medium text-success-700 dark:text-success-300">
                {{ __('ConferenceDiscountEligibility::messages.coupon_applied_label') }}
            </div>
            <dl class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('ConferenceDiscountEligibility::messages.coupon_code') }}</dt>
                    <dd class="font-mono text-gray-950 dark:text-white">{{ $reservation->coupon->code_hint }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('ConferenceDiscountEligibility::messages.discount') }}</dt>
                    <dd class="text-gray-950 dark:text-white">{{ \ConferenceDiscountEligibility\Support\Percentage::format((int) $reservation->coupon->percentage_basis_points) }}%</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('ConferenceDiscountEligibility::messages.status') }}</dt>
                    <dd class="text-gray-950 dark:text-white">{{ __('ConferenceDiscountEligibility::messages.coupon_status_' . $reservation->status) }}</dd>
                </div>
                @if ($snapshot)
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('ConferenceDiscountEligibility::messages.final_total') }}</dt>
                        <dd class="font-semibold text-gray-950 dark:text-white">
                            {{ money((float) \ConferenceDiscountEligibility\Support\Money::decimal((int) $snapshot->final_total_minor, $snapshot->currency), $snapshot->currency, true)->formatWithoutZeroes() }}
                        </dd>
                    </div>
                @endif
            </dl>
        </div>
    @endif

    @if ($canModify)
        <form wire:submit="apply" class="space-y-3">
            <div>
                <label for="cde-coupon-code-{{ $paymentId }}" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">
                    {{ __('ConferenceDiscountEligibility::messages.coupon_code') }}
                </label>
                <x-filament::input.wrapper :valid="! $errors->has('code')">
                    <x-filament::input
                        id="cde-coupon-code-{{ $paymentId }}"
                        type="text"
                        wire:model="code"
                        autocomplete="off"
                        maxlength="64"
                        :placeholder="__('ConferenceDiscountEligibility::messages.coupon_code_placeholder')"
                    />
                </x-filament::input.wrapper>
                @error('code')
                    <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-wrap gap-2">
                <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="apply">
                    {{ __('ConferenceDiscountEligibility::messages.apply_coupon') }}
                </x-filament::button>
                @if ($reservation && $reservation->status === 'reserved')
                    <x-filament::button
                        type="button"
                        color="gray"
                        wire:click="remove"
                        wire:loading.attr="disabled"
                        wire:target="remove"
                    >
                        {{ __('ConferenceDiscountEligibility::messages.remove_coupon') }}
                    </x-filament::button>
                @endif
            </div>
        </form>
        <p class="text-xs text-gray-500 dark:text-gray-400">
            {{ __('ConferenceDiscountEligibility::messages.coupon_payment_warning') }}
        </p>
    @elseif (! $enabled)
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('ConferenceDiscountEligibility::messages.coupon_redemption_disabled') }}
        </p>
    @elseif (! $reservation)
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('ConferenceDiscountEligibility::messages.coupon_payment_locked') }}
        </p>
    @endif
</div>
