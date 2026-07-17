<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\DiscountPaymentReportResource\Pages;

use ConferenceDiscountEligibility\Models\ConferenceDiscountPaymentSnapshot;
use ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\DiscountPaymentReportResource;
use ConferenceDiscountEligibility\Services\PaymentDetailPresenter;
use ConferenceDiscountEligibility\Support\CsvSanitizer;
use ConferenceDiscountEligibility\Support\Money;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ListDiscountPayments extends ListRecords
{
    protected static string $resource = DiscountPaymentReportResource::class;

    protected function getHeaderActions(): array
    {
        return [Action::make('export')->label(__('ConferenceDiscountEligibility::messages.export_csv'))->icon('heroicon-o-arrow-down-tray')->action(fn (): StreamedResponse => $this->export())];
    }

    private function export(): StreamedResponse
    {
        $conferenceId = (int) app()->getCurrentScheduledConference()->getKey();
        return response()->streamDownload(function () use ($conferenceId): void {
            $out = fopen('php://output', 'wb');
            fputcsv($out, ['payment_id','invoice','user_id','email','currency','original_total','discount_percentage','discount_amount','final_total','reason','eligibility_type','identity_verification','status','payment_method','paypal_payment_id']);
            ConferenceDiscountPaymentSnapshot::query()->where('scheduled_conference_id', $conferenceId)->with(['payment','user'])->orderBy('id')->chunkById(200, function ($records) use ($out): void {
                foreach ($records as $record) {
                    fputcsv($out, array_map([CsvSanitizer::class, 'safeCell'], [
                        $record->payment_id, $record->payment?->invoice, $record->user_id, $record->user?->email, $record->currency,
                        Money::decimal((int) $record->original_total_minor, $record->currency), ((int) $record->discount_percentage_basis_points) / 100,
                        Money::decimal((int) $record->discount_amount_minor, $record->currency), Money::decimal((int) $record->final_total_minor, $record->currency),
                        $record->eligibility_reason, $record->eligibility_type, app(PaymentDetailPresenter::class)->identityEvidenceFromSnapshot($record), $record->payment?->isPaid() ? 'paid' : 'unpaid',
                        $record->payment?->payment_method, $record->payment?->getMeta('paypal_payment_id'),
                    ]));
                }
            });
            fclose($out);
        }, 'conference-discount-payments.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
