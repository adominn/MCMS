<?php

declare(strict_types=1);

namespace App\Modules\Identity;

// The kinds of user per Guide Appendix H2 (P1–P8).
enum Role: string
{
    case Student = 'student';                       // P8: Peserta / Pelajar
    case Lecturer = 'lecturer';                     // P1: Penyelaras MC / Pemilik Akademik
    case Reviewer = 'reviewer';                     // Academic Reviewer
    case DeputyDean = 'deputy_dean';                // P2: PTj / Fakulti (Legacy)
    case PtjAuthority = 'ptj_authority';            // P2: PTj / Fakulti (Appendix H)
    case AcademicAuthority = 'academic_authority';  // P3: Pihak Berkuasa Akademik (Senat / JKKP)
    case QaOfficer = 'qa_officer';                  // P4: Jaminan Kualiti (QA)
    case CodlAdmin = 'codl_admin';                  // P5: Sokongan Platform / Operasi / Teknikal
    case RecordsCustodian = 'records_custodian';    // P6: Unit Rekod / Pengeluar Credential
    case AbcDepositor = 'abc_depositor';            // P7: Pihak Depositor ABC Diberi Kuasa
    case RecordsDepositor = 'records_depositor';    // P6/P7 (Legacy)
    case IndustryExpert = 'industry_expert';        // External Industry Subject-Matter Expert

    public function label(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $isMs = str_starts_with($locale, 'ms');

        return match ($this) {
            self::Student => $isMs ? 'Pelajar / Peserta (P8)' : 'Student / Participant (P8)',
            self::Lecturer => $isMs ? 'Penyelaras MC / Pensyarah (P1)' : 'MC Coordinator / Lecturer (P1)',
            self::Reviewer => $isMs ? 'Pemeriksa Kandungan Akademik' : 'Academic Reviewer',
            self::DeputyDean, self::PtjAuthority => $isMs ? 'Pihak Berkuasa PTj / Fakulti (P2)' : 'PTj / Faculty Authority (P2)',
            self::AcademicAuthority => $isMs ? 'Pihak Berkuasa Akademik / Senat (P3)' : 'Academic Authority / Senate (P3)',
            self::QaOfficer => $isMs ? 'Pegawai Jaminan Kualiti / QA (P4)' : 'Quality Assurance Officer (P4)',
            self::CodlAdmin => $isMs ? 'Pentadbir Platform CODL (P5)' : 'CODL Platform Administrator (P5)',
            self::RecordsCustodian => $isMs ? 'Unit Rekod / Pengeluar Kredensial (P6)' : 'Records & Credential Custodian (P6)',
            self::AbcDepositor => $isMs ? 'Pihak Depositor ABC Diberi Kuasa (P7)' : 'Authorized ABC Depositor (P7)',
            self::RecordsDepositor => $isMs ? 'Pegawai Rekod & ABC (P6/P7)' : 'Records & ABC Officer (P6/P7)',
            self::IndustryExpert => $isMs ? 'Pakar Bidang Industri' : 'Industry Subject-Matter Expert',
        };
    }

    /** Guide Appendix H2 role code (P1 to P8). */
    public function guideCode(): ?string
    {
        return match ($this) {
            self::Lecturer => 'P1',
            self::DeputyDean, self::PtjAuthority => 'P2',
            self::AcademicAuthority => 'P3',
            self::QaOfficer => 'P4',
            self::CodlAdmin => 'P5',
            self::RecordsCustodian => 'P6',
            self::AbcDepositor => 'P7',
            self::Student => 'P8',
            default => null,
        };
    }

    // Testing mode: MFA disabled for direct test sign-in
    public function requiresMfa(): bool
    {
        return false;
    }

    /** Primary landing route name for this role. */
    public function homeRouteName(): string
    {
        return match ($this) {
            self::CodlAdmin => 'users',
            self::IndustryExpert, self::Reviewer => 'reviews',
            self::RecordsCustodian => 'credentials.admin',
            self::AbcDepositor => 'deposits',
            self::AcademicAuthority => 'approvals',
            self::QaOfficer => 'monitoring',
            default => 'tasks',
        };
    }

    public function isStaff(): bool
    {
        return $this !== self::Student;
    }
}
