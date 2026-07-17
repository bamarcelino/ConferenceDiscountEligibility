<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Panel\ScheduledConference\Pages;

use ConferenceDiscountEligibility\Enums\DuplicateStrategy;
use ConferenceDiscountEligibility\Services\Authorization;
use ConferenceDiscountEligibility\Services\CsvImportService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class DiscountCsvImport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';
    protected static ?int $navigationSort = 40;
    protected static string $view = 'ConferenceDiscountEligibility::pages.csv-import';

    /** @var array<string,mixed>|null */
    public ?array $data = [];
    /** @var array<string,mixed>|null */
    public ?array $report = null;

    public static function getNavigationGroup(): ?string { return __('ConferenceDiscountEligibility::messages.navigation_group'); }
    public static function getNavigationLabel(): string { return __('ConferenceDiscountEligibility::messages.csv_import'); }
    public function getTitle(): string { return __('ConferenceDiscountEligibility::messages.csv_import'); }
    public static function canAccess(): bool { return app(Authorization::class)->canManage(); }

    public function mount(): void
    {
        app(Authorization::class)->authorizeManage();
        $this->form->fill(['dry_run' => true, 'duplicate_strategy' => DuplicateStrategy::Error->value]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\FileUpload::make('file')
                ->label(__('ConferenceDiscountEligibility::messages.csv_file'))
                ->disk('local')->directory('conference-discount-eligibility/imports')->visibility('private')
                ->storeFileNamesIn('original_filename')->acceptedFileTypes(['text/csv','text/plain','application/vnd.ms-excel'])
                ->maxSize(20480)->required(),
            Forms\Components\Select::make('duplicate_strategy')->label(__('ConferenceDiscountEligibility::messages.duplicate_strategy'))->options([
                DuplicateStrategy::Error->value => __('ConferenceDiscountEligibility::messages.duplicate_error'),
                DuplicateStrategy::Update->value => __('ConferenceDiscountEligibility::messages.duplicate_update'),
                DuplicateStrategy::Ignore->value => __('ConferenceDiscountEligibility::messages.duplicate_ignore'),
            ])->required(),
            Forms\Components\Toggle::make('dry_run')->label(__('ConferenceDiscountEligibility::messages.dry_run'))->helperText(__('ConferenceDiscountEligibility::messages.dry_run_help'))->default(true),
        ])->statePath('data')->columns(1);
    }

    public function preview(CsvImportService $service): void
    {
        $this->process($service, true);
    }

    public function import(CsvImportService $service): void
    {
        $this->process($service, false);
    }

    private function process(CsvImportService $service, bool $forceDryRun): void
    {
        app(Authorization::class)->authorizeManage();
        $data = $this->form->getState();
        $storedPath = is_array($data['file'] ?? null) ? reset($data['file']) : ($data['file'] ?? null);
        $original = is_array($data['original_filename'] ?? null) ? reset($data['original_filename']) : ($data['original_filename'] ?? basename((string) $storedPath));
        if (! is_string($storedPath) || $storedPath === '') { return; }
        try {
            $dryRun = $forceDryRun || (bool) ($data['dry_run'] ?? false);
            $this->report = $service->process(
                Storage::disk('local')->path($storedPath),
                (string) $original,
                (int) app()->getCurrentScheduledConference()->getKey(),
                DuplicateStrategy::from((string) $data['duplicate_strategy']),
                $dryRun,
            );
            Notification::make()->success()->title($dryRun ? __('ConferenceDiscountEligibility::messages.preview_ready') : __('ConferenceDiscountEligibility::messages.import_complete'))->send();
            if (! $dryRun) { Storage::disk('local')->delete($storedPath); $this->form->fill(['dry_run' => true, 'duplicate_strategy' => DuplicateStrategy::Error->value]); }
        } catch (Throwable $exception) {
            Notification::make()->danger()->title(__('ConferenceDiscountEligibility::messages.import_failed'))->body($exception->getMessage())->persistent()->send();
        }
    }
}
