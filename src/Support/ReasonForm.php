<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Support;

use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Database\Eloquent\Model;

final class ReasonForm
{
    /** @return array<int, Forms\Components\Component> */
    public static function fields(): array
    {
        $synchronize = static function (Get $get, Set $set): void {
            $set('reason', ReasonOptions::compose(
                $get('reason_code'),
                $get('custom_reason'),
                $get('reason_details'),
            ));
        };

        return [
            Forms\Components\Hidden::make('reason')
                ->required(),
            Forms\Components\Select::make('reason_code')
                ->label(__('ConferenceDiscountEligibility::messages.reason'))
                ->options(ReasonOptions::labels())
                ->required()
                ->live()
                ->dehydrated(false)
                ->afterStateHydrated(static function ($component, mixed $state, ?Model $record, Set $set): void {
                    if ($record === null) {
                        $component->state(ReasonOptions::INDIVIDUAL_APPROVAL);
                        $set('reason', ReasonOptions::compose(ReasonOptions::INDIVIDUAL_APPROVAL, null, null));
                        return;
                    }

                    $parsed = ReasonOptions::parse($record?->getAttribute('reason'));
                    $component->state($parsed['code']);
                })
                ->afterStateUpdated($synchronize),
            Forms\Components\TextInput::make('custom_reason')
                ->label(__('ConferenceDiscountEligibility::messages.custom_reason'))
                ->helperText(__('ConferenceDiscountEligibility::messages.custom_reason_help'))
                ->visible(static fn (Get $get): bool => $get('reason_code') === ReasonOptions::OTHER)
                ->required(static fn (Get $get): bool => $get('reason_code') === ReasonOptions::OTHER)
                ->maxLength(120)
                ->live(onBlur: true)
                ->dehydrated(false)
                ->afterStateHydrated(static fn ($component, mixed $state, ?Model $record) => $component->state(
                    $record === null ? null : ReasonOptions::parse($record->getAttribute('reason'))['custom'],
                ))
                ->afterStateUpdated($synchronize),
            Forms\Components\TextInput::make('reason_details')
                ->label(__('ConferenceDiscountEligibility::messages.reason_details'))
                ->helperText(__('ConferenceDiscountEligibility::messages.reason_details_help'))
                ->maxLength(120)
                ->live(onBlur: true)
                ->dehydrated(false)
                ->afterStateHydrated(static fn ($component, mixed $state, ?Model $record) => $component->state(
                    $record === null ? null : ReasonOptions::parse($record->getAttribute('reason'))['details'],
                ))
                ->afterStateUpdated($synchronize),
        ];
    }
}
