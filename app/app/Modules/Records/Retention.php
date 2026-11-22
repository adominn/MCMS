<?php

declare(strict_types=1);

namespace App\Modules\Records;

use App\Modules\Core\Settings;
use Illuminate\Support\Facades\DB;

// MD-15 retention (FR-retention, FR-hold). Only non-academic logs can be disposed of here; academic records, credentials and the audit trail are never listed (dr-nodelete, OI-07).
final class Retention
{
    /** category => [table, date column, setting key, label] */
    public const CATEGORIES = [
        'notifications' => ['notifications', 'created_at', 'retention.notifications_years', 'Notification log'],
        'report_runs' => ['report_runs', 'ran_at', 'retention.report_runs_years', 'Report run log'],
        'integration_calls' => ['integration_calls', 'created_at', 'retention.integration_years', 'Integration call log'],
    ];

    public function __construct(private Settings $settings, private AuditRecorder $audit) {}

    /** Appendix H4: Record Ownership Matrix covering all 12 record classes (pp. 116–117). */
    public static function recordOwnershipMatrix(): array
    {
        return [
            [
                'no' => 1,
                'class_en' => 'MC Curriculum & Approval Documents (Appendix A, Senate decisions, LO mapping)',
                'class_ms' => 'Dokumen Kurikulum & Kelulusan MC (Lampiran A, kelulusan Senat, pemetaan LO)',
                'owner' => 'P2 (PTj / Fakulti) & P3 (PBA / Senat)',
                'creator' => 'P1 (Penyelaras MC)',
                'retention' => 'Kekal sepanjang kitaran hayat versi aktif (Perpetual active lifecycle)',
                'access' => 'Dalaman Universiti (Internal institutional)',
            ],
            [
                'no' => 2,
                'class_en' => 'Offering Operational Readiness (Appendix B checklists, facilities, LMS setup)',
                'class_ms' => 'Kesiapsiagaan Operasi Penawaran (Lampiran B, semakan fasiliti, LMS)',
                'owner' => 'P2 (PTj / Fakulti)',
                'creator' => 'P1 (Penyelaras), P2 & P5 (CODL)',
                'retention' => '5 Tahun selepas kohort selesai (5 Years post-cohort)',
                'access' => 'Dalaman PTj (Faculty internal)',
            ],
            [
                'no' => 3,
                'class_en' => 'Cohort Enrolments & Learning Engagement (Learner list, profile, LMS activity logs)',
                'class_ms' => 'Rekod Kohort & Penglibatan Pembelajaran (Senarai peserta, profil, log aktiviti)',
                'owner' => 'P1 (Penyelaras) & P2 (PTj)',
                'creator' => 'P1 & Sistem LMS',
                'retention' => '5 Tahun selepas kohort selesai (5 Years post-cohort)',
                'access' => 'Terhad / PTj (Confidential / Faculty)',
            ],
            [
                'no' => 4,
                'class_en' => 'Assessment Instruments & Marked Scripts (Rubrics, question papers, marked scripts)',
                'class_ms' => 'Instrumen & Skrip / Bukti Penilaian (Rubrik, soalan, skrip bertanda, moderasi)',
                'owner' => 'P1 (Penyelaras) & P2 (PTj)',
                'creator' => 'P1 (Penyelaras MC)',
                'retention' => '3 Tahun selepas keputusan diumumkan (3 Years post-results)',
                'access' => 'Sulit Peperiksaan (Strictly Confidential / Exams)',
            ],
            [
                'no' => 5,
                'class_en' => 'Completion Verification Records (Verified results, completion confirmation, badges)',
                'class_ms' => 'Rekod Pengesahan Tamat (Keputusan disahkan, pengesahan tamat, lencana modul)',
                'owner' => 'P2 (PTj) & P6 (Unit Rekod)',
                'creator' => 'P1 (Penyelaras) & P2 (PTj)',
                'retention' => 'Kekal (Perpetual archive)',
                'access' => 'Terhad Universiti (Confidential)',
            ],
            [
                'no' => 6,
                'class_en' => 'Digital Credentials & Digital Badges (Signed VC/OB3 payloads, public verify hashes, revocation log)',
                'class_ms' => 'Kredensial Digital & Lencana (Kandungan VC/OB3 disahkan, pautan verifikasi, daftar pembatalan)',
                'owner' => 'P6 (Unit Rekod / Pengeluar Credential)',
                'creator' => 'P6 & Sistem MCMS (Ed25519)',
                'retention' => 'Kekal (Perpetual verification)',
                'access' => 'Awam Terpilih (Public verification via hash/code)',
            ],
            [
                'no' => 7,
                'class_en' => 'Academic Credit Records (ACR) (Official credit statements, Appendix F templates, verifier sign-offs)',
                'class_ms' => 'Rekod Kredit Akademik / ACR (Penyata kredit rasmi, Lampiran F, tandatangan pengesah)',
                'owner' => 'P6 (Unit Rekod / Penjaga Rekod)',
                'creator' => 'P1, P2 & P6',
                'retention' => 'Kekal selaras Cl. 12.8 (Perpetual institutional archive)',
                'access' => 'Terhad / Rasmi (Official Academic Record)',
            ],
            [
                'no' => 8,
                'class_en' => 'Academic Bank of Credits (ABC) Deposits (Consented deposit batches, transaction receipts, redemption states)',
                'class_ms' => 'Deposit Bank Kredit / ABC (Kelompok deposit berizin, resit transaksi, status penebusan tunggal)',
                'owner' => 'P7 (Pihak Depositor ABC Diberi Kuasa)',
                'creator' => 'P7 & Portal ABC Universiti',
                'retention' => 'Kekal / Selaras dasar ABC (Perpetual in ABC)',
                'access' => 'Terhad / Pelajar & Agensi Rasmi',
            ],
            [
                'no' => 9,
                'class_en' => 'Credit Transfer & Equivalence Decisions (Transfer applications, equivalence mapping, Senate approvals)',
                'class_ms' => 'Penentududukan & Pemindahan Kredit (Permohonan pemindahan, pemetaan kesetaraan, kelulusan Senat)',
                'owner' => 'P2 (PTj) & P3 (PBA / Senat)',
                'creator' => 'P8 (Pemohon) & Jawatankuasa Kredit PTj',
                'retention' => 'Sepanjang tempoh pengajian program + 5 tahun (Programme tenure + 5 years)',
                'access' => 'Terhad / Pejabat Akademik',
            ],
            [
                'no' => 10,
                'class_en' => 'CQI Reports & Monitoring (Appendix C dossiers, stakeholder feedback analysis, improvement action plans)',
                'class_ms' => 'Laporan CQI & Pemantauan (Lampiran C, analisis maklum balas pemegang taruh, pelan tindakan)',
                'owner' => 'P2 (PTj) & P4 (Pegawai QA)',
                'creator' => 'P1 (Penyelaras MC)',
                'retention' => '5 Tahun / 2 kitaran semakan (5 Years / 2 review cycles)',
                'access' => 'Dalaman Universiti (Institutional)',
            ],
            [
                'no' => 11,
                'class_en' => 'Quality Verification (QV) Packages (Appendix D dossiers, evidence portfolios, QVC panel decisions)',
                'class_ms' => 'Pakej Verifikasi Kualiti / QV (Lampiran D, portfolio bukti, keputusan panel QVC)',
                'owner' => 'P2 (PTj) & P4 (Jaminan Kualiti)',
                'creator' => 'P1, P2 & P4',
                'retention' => 'Sepanjang tempoh sah QV (3 tahun) + 5 tahun arkib',
                'access' => 'Terhad / Jaminan Kualiti',
            ],
            [
                'no' => 12,
                'class_en' => 'System Audit Trail & Security Logs (Append-only audit_events, hash-chain verification, discrepancy corrections)',
                'class_ms' => 'Log Audit Sistem & Jejak Integriti (audit_events rantaian kriptografi, pembetulan percanggahan)',
                'owner' => 'P5 (Sokongan Platform) & P6 (Unit Rekod)',
                'creator' => 'Sistem MCMS (Pencetus append-only)',
                'retention' => 'Kekal (Perpetual, append-only immutable)',
                'access' => 'Sulit / Juruaudit Bertauliah (Auditors & Administrators)',
            ],
        ];
    }

    /** JB-19: what has passed its period and is not held. @return list<array{category: string, label: string, years: int, cutoff: string, count: int, held: bool}> */
    public function candidates(): array
    {
        $out = [];
        foreach (self::CATEGORIES as $k => [$table, $col, $key, $label]) {
            $years = (int) $this->settings->get($key);
            $cutoff = today()->subYears($years)->toDateString();
            $out[] = ['category' => $k, 'label' => $label, 'years' => $years, 'cutoff' => $cutoff, 'count' => DB::table($table)->where($col, '<', $cutoff)->count(), 'held' => $this->held($k)];
        }

        return $out;
    }

    public function held(string $category): bool
    {
        return DB::table('retention_holds')->where('category', $category)->whereNull('lifted_at')->exists();
    }

    /** Disposal is approved, logged, and refused while a hold is on or before the period has passed. The disposal record is the only way a row leaves. */
    public function dispose(string $category, int $approverId): object
    {
        [$table, $col, $key] = self::CATEGORIES[$category] ?? abort(404);
        abort_if($this->held($category), 422, 'A hold is on this category, so nothing can be disposed of.');
        $years = (int) $this->settings->get($key);
        $cutoff = today()->subYears($years)->toDateString();

        return DB::transaction(function () use ($table, $col, $category, $cutoff, $years, $approverId) {
            $n = DB::table($table)->where($col, '<', $cutoff)->delete();
            $id = DB::table('disposal_records')->insertGetId(['category' => $category, 'cutoff' => $cutoff, 'row_count' => $n, 'retention_years' => $years, 'approved_by' => $approverId, 'approved_at' => now()]);
            $this->audit->record('disposal.approved', ['type' => 'DisposalRecord', 'id' => $id], after: ['category' => $category, 'rows' => $n, 'cutoff' => $cutoff], actorId: $approverId); // EV-61

            return DB::table('disposal_records')->find($id);
        });
    }
}
