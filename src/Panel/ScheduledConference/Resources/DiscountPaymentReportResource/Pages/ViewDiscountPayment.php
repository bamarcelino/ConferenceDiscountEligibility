<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\DiscountPaymentReportResource\Pages;

use ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\DiscountPaymentReportResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewDiscountPayment extends ViewRecord
{
    protected static string $resource = DiscountPaymentReportResource::class;
}
