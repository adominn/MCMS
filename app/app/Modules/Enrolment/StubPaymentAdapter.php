<?php

declare(strict_types=1);

namespace App\Modules\Enrolment;

// Records a simulated success or failure with a reference number (FR-101). Any card works except the one that declines.
final class StubPaymentAdapter implements PaymentAdapter
{
    public function charge(string $amount, string $cardNumber, string $reference): string
    {
        if ($cardNumber === config('enrolment.test_decline_card')) {
            throw new PaymentDeclined('The card was declined.');
        }

        return 'DUMMY-'.strtoupper(substr(md5($reference), 0, 8));
    }
}
