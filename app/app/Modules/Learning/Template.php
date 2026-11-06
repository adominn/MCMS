<?php

declare(strict_types=1);

namespace App\Modules\Learning;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// The published CSV templates for UCreds, version v3 (IR-19): UTF-8, header row, ISO 8601 dates.
final class Template
{
    public const VERSION = 'v3';
    public const ENROL = ['learner_id', 'full_name', 'enrolment_id', 'offering', 'cohort', 'start_date', 'end_date'];
    public const BADGES = ['learner_id', 'enrolment_id', 'module_no', 'badge_id', 'badge_url', 'badge_date'];

    public static function offeringRef(int $offeringId): string
    {
        return 'O-'.str_pad((string) $offeringId, 4, '0', STR_PAD_LEFT);
    }

    /** The mark column of a component: the typed key, or one made from its name. */
    public static function key(object $c): string
    {
        return $c->csv_key ?: (Str::slug($c->name_en ?: 'component '.$c->id, '_').'_mark');
    }

    public static function components(int $versionId)
    {
        return DB::table('assessment_components')->where('mc_version_id', $versionId)->orderBy('id')->get();
    }

    /** @return list<string> */
    public static function results(int $versionId): array
    {
        return array_merge(['learner_id', 'enrolment_id', 'offering', 'progress_percent', 'modules_completed'], self::components($versionId)->map(fn ($c) => self::key($c))->all());
    }
}
