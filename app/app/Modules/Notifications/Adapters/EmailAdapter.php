<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Adapters;

// IR-14: every external system sits behind an interface with a stub and a live implementation (IR-15, IR-16).
interface EmailAdapter
{
    /** Sends one message and returns the provider's reference. Throws when it fails. */
    public function send(string $to, string $subject, string $body): string;
}
