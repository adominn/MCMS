<?php

declare(strict_types=1);

namespace App\Modules\Curriculum;

// FR-26 to FR-30, FR-32: the three questions decide the category, with the reason in plain words. The result is only proposed (FR-31).
final class CategoryRules
{
    /** @return array{category:string, reason:string, qv_planned:bool} */
    public static function determine(string $origin, int $slt, bool $creditSought, int $minSamcHours): array
    {
        if ($origin === 'unbundled') {
            return ['category' => 'umc', 'qv_planned' => false, 'reason' => 'The modules come from a credit course of an accredited UTeM programme, so this can only be a UMC. It keeps its link to the parent course and carries that course\'s credit as a whole.'];
        }
        if ($slt < $minSamcHours) {
            return ['category' => 'mce', 'qv_planned' => false, 'reason' => "An own offering below $minSamcHours hours of learning time can only be an MCE. It is non-credit."
                .($creditSought ? " To seek credit or an MQF level you would need at least $minSamcHours hours, as a SAMC." : '')];
        }

        return ['category' => 'samc', 'qv_planned' => $creditSought, 'reason' => "An own offering of at least $minSamcHours hours can be a SAMC."
            .($creditSought ? ' Because you seek a credit value or MQF level, Quality Verification is planned. Credit is shown only after it succeeds.' : ' It carries no credit unless it is later Quality-Verified.')];
    }
}
