<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Observers;

use App\Models\User;
use ConferenceDiscountEligibility\Services\EmailEntitlementLinker;
use Illuminate\Support\Facades\Log;
use Throwable;

final class UserObserver
{
    public function __construct(private readonly EmailEntitlementLinker $linker) {}

    public function saved(User $user): void
    {
        try {
            $this->linker->link($user);
        } catch (Throwable $exception) {
            Log::warning('Pending conference discount email entitlement could not be linked during user save.', [
                'user_id' => $user->getKey(),
                'exception' => $exception,
            ]);
        }
    }
}
