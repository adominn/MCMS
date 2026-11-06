<?php

declare(strict_types=1);

namespace App\Modules\Enrolment;

// IR-14: the payment service sits behind an interface. Release 1 uses a dummy service (D-14, A-03).
interface PaymentAdapter
{
    /** Charges the card and returns the provider reference. Throws PaymentDeclined when the payment fails. */
    public function charge(string $amount, string $cardNumber, string $reference): string;
}
