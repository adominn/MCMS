<?php

declare(strict_types=1);

namespace App\Modules\Achievement\Events;

// EV-31: the only trigger for issuing a certificate or Digital Credential (D-29). The credentials module listens to it.
final class CompletionConfirmed
{
    public function __construct(public int $enrolmentId, public int $confirmedBy) {}
}
