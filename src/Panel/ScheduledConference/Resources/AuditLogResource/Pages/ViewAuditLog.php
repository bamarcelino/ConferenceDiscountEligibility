<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\AuditLogResource\Pages;

use ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\AuditLogResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewAuditLog extends ViewRecord
{
    protected static string $resource = AuditLogResource::class;
}
