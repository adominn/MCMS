<?php

declare(strict_types=1);

namespace App\Modules\Offering;

// FR-93, FR-151: free text must not carry a claim that only a verified record or a separate decision can support.
final class ForbiddenClaims
{
    /** @return list<string> what was found, in words */
    public static function scan(?string $text): array
    {
        $found = [];
        foreach (config('offerings.forbidden') as $pattern => $what) {
            if ($text && preg_match($pattern, $text)) {
                $found[] = $what;
            }
        }

        return $found;
    }
}
