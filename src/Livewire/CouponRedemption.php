<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Livewire;

use App\Models\Payment;
use ConferenceDiscountEligibility\Services\CouponRedemptionService;
use ConferenceDiscountEligibility\Services\PaymentDetailPresenter;
use ConferenceDiscountEligibility\Services\SettingsRepository;
use ConferenceDiscountEligibility\Support\CouponCode;
use ConferenceDiscountEligibility\Support\PaymentSafety;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\RateLimiter;
use InvalidArgumentException;
use Livewire\Component;

final class CouponRedemption extends Component
{
    public int $paymentId;
    public string $code = '';

    public function mount(int $paymentId): void
    {
        $this->paymentId = $paymentId;
        $this->authorizedPayment();
    }

    public function apply(): void
    {
        $payment = $this->authorizedPayment();
        $coupons = app(CouponRedemptionService::class);
        $key = $this->rateLimitKey();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            Notification::make()
                ->danger()
                ->title(__('ConferenceDiscountEligibility::messages.coupon_too_many_attempts'))
                ->body(__('ConferenceDiscountEligibility::messages.coupon_try_again_in', [
                    'seconds' => RateLimiter::availableIn($key),
                ]))
                ->send();

            return;
        }

        try {
            $normalized = CouponCode::normalize($this->code);
        } catch (InvalidArgumentException) {
            RateLimiter::hit($key, 300);
            $this->addError('code', __('ConferenceDiscountEligibility::messages.coupon_invalid_format'));

            return;
        }

        $result = $coupons->apply($payment, $normalized);
        if ($result->status === 'rejected') {
            RateLimiter::hit($key, 300);
        } else {
            RateLimiter::clear($key);
        }

        $notification = Notification::make()->title(__("ConferenceDiscountEligibility::messages.{$result->message}"));
        match ($result->status) {
            'applied', 'already_applied', 'completed' => $notification->success(),
            'not_selected', 'locked' => $notification->warning(),
            default => $notification->danger(),
        };
        $notification->send();

        if ($result->couponSelected) {
            $this->reset('code');
        }
        if ($result->changed) {
            $this->dispatch('cde-coupon-updated');
        }
    }

    public function remove(): void
    {
        $payment = $this->authorizedPayment();
        $coupons = app(CouponRedemptionService::class);
        $result = $coupons->remove($payment);
        $notification = Notification::make()->title(__("ConferenceDiscountEligibility::messages.{$result->message}"));
        $result->changed ? $notification->success() : $notification->warning();
        $notification->send();

        if ($result->changed) {
            $this->dispatch('cde-coupon-updated');
        }
    }

    public function render()
    {
        $payment = $this->authorizedPayment();
        $coupons = app(CouponRedemptionService::class);
        $presenter = app(PaymentDetailPresenter::class);
        $settings = app(SettingsRepository::class);
        $reservation = $coupons->reservationForPayment($payment);
        $enabled = $settings->couponRedemptionEnabled((int) $payment->scheduled_conference_id);

        return view('ConferenceDiscountEligibility::livewire.coupon-redemption', [
            'payment' => $payment,
            'reservation' => $reservation,
            'snapshot' => $presenter->snapshot($payment),
            'enabled' => $enabled,
            'canModify' => $enabled && PaymentSafety::canRecalculate($payment),
        ]);
    }

    private function authorizedPayment(): Payment
    {
        $payment = Payment::query()
            ->with(['user', 'fee'])
            ->findOrFail($this->paymentId);
        $conferenceId = (int) app()->getCurrentScheduledConference()?->getKey();

        abort_unless($conferenceId > 0 && (int) $payment->scheduled_conference_id === $conferenceId, 404);
        abort_unless(auth()->user()?->can('view', $payment) === true, 403);

        return $payment;
    }

    private function rateLimitKey(): string
    {
        $ip = app()->bound('request') ? (string) request()->ip() : 'unknown';

        return 'cde-coupon:' . (int) auth()->id() . ':' . $this->paymentId . ':' . hash('sha256', $ip);
    }
}
