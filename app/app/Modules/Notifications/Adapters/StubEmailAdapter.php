<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Adapters;

final class StubEmailAdapter implements EmailAdapter
{
    public function send(string $to, string $subject, string $body): string
    {
        file_put_contents(storage_path('logs/stub-mail.log'), '['.now()->toIso8601String()."] To: $to | $subject\n$body\n\n", FILE_APPEND);

        return 'stub-'.bin2hex(random_bytes(4));
    }
}
