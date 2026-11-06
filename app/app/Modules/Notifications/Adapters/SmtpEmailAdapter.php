<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Adapters;

use Illuminate\Support\Facades\Mail;

final class SmtpEmailAdapter implements EmailAdapter
{
    public function send(string $to, string $subject, string $body): string
    {
        Mail::raw($body, fn ($m) => $m->to($to)->subject($subject));

        return 'smtp-'.bin2hex(random_bytes(4));
    }
}
