@livewire(
    'conference-discount-coupon-redemption',
    ['paymentId' => $getRecord()->getKey()],
    key('cde-coupon-' . $getRecord()->getKey())
)
