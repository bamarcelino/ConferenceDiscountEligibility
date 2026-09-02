<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Support;

use Filament\Forms;

final class ReasonForm
{
    /** @return array<int, Forms\Components\Component> */
    public static function fields(): array
    {
        return [
            Forms\Components\TextInput::make('reason')
                ->label(__('ConferenceDiscountEligibility::messages.reason'))
                ->helperText(__('ConferenceDiscountEligibility::messages.reason_help'))
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
        ];
    }
}
