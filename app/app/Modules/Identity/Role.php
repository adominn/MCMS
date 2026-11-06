<?php

declare(strict_types=1);

namespace App\Modules\Identity;

// The kinds of user (D-28). Senate does not sign in; the public needs no account.
enum Role: string
{
    case Student = 'student';
    case Lecturer = 'lecturer';
    case Reviewer = 'reviewer';
    case DeputyDean = 'deputy_dean';
    case CodlAdmin = 'codl_admin';
    case IndustryExpert = 'industry_expert';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Student',
            self::Lecturer => 'Lecturer',
            self::Reviewer => 'Reviewer',
            self::DeputyDean => 'Deputy dean',
            self::CodlAdmin => 'CODL administrator',
            self::IndustryExpert => 'Industry expert',
        };
    }

    // Staff roles that approve, verify, issue, deposit or administer need a second step (SR-02).
    public function requiresMfa(): bool
    {
        return in_array($this, [self::DeputyDean, self::CodlAdmin], true);
    }
}
