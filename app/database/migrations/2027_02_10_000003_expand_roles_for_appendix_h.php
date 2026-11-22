<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Seed or update the 8 Guide Appendix H personas
        $personas = [
            [
                'name' => 'Prof. Dr. Zulkifli Ahmad',
                'email' => 'zulkifli.ahmad@utem.example.test',
                'role' => 'academic_authority',
                'code' => null,
                'locale' => 'en',
                'posEn' => 'Senate Academic Representative / PBA (P3)',
                'posMs' => 'Wakil Senat / Pihak Berkuasa Akademik (P3)',
                'reason' => 'Appendix H P3 Academic Authority',
            ],
            [
                'name' => 'Dr. Norazlina Mohd',
                'email' => 'norazlina.mohd@utem.example.test',
                'role' => 'qa_officer',
                'code' => null,
                'locale' => 'en',
                'posEn' => 'Quality Assurance Officer, PKA (P4)',
                'posMs' => 'Pegawai Jaminan Kualiti, PKA (P4)',
                'reason' => 'Appendix H P4 Quality Assurance',
            ],
            [
                'name' => 'Hasnah Abu Bakar',
                'email' => 'hasnah.bakar@utem.example.test',
                'role' => 'records_custodian',
                'code' => null,
                'locale' => 'en',
                'posEn' => 'Senior Assistant Registrar, Records Unit (P6)',
                'posMs' => 'Penolong Pendaftar Kanan, Unit Rekod (P6)',
                'reason' => 'Appendix H P6 Records Custodian',
            ],
            [
                'name' => 'Nurul Izzah',
                'email' => 'nurul.izzah@utem.example.test',
                'role' => 'abc_depositor',
                'code' => null,
                'locale' => 'en',
                'posEn' => 'Authorized Records & ABC Depositor Officer (P6/P7)',
                'posMs' => 'Pegawai Rekod & Depositor ABC Diberi Kuasa (P6/P7)',
                'reason' => 'Appendix H P7 ABC Depositor',
            ],
        ];

        foreach ($personas as $p) {
            $user = User::firstOrCreate(
                ['email' => $p['email']],
                [
                    'name' => $p['name'],
                    'upn' => $p['email'],
                    'password' => Hash::make('password'),
                    'locale' => $p['locale'],
                    'position' => $p['posEn'],
                    'position_ms' => $p['posMs'],
                    'status' => 'active',
                ]
            );

            // Ensure position is set
            if (! $user->position) {
                $user->forceFill(['position' => $p['posEn'], 'position_ms' => $p['posMs']])->save();
            }

            // Ensure role assignment exists
            $user->roleAssignments()->firstOrCreate(
                ['role' => $p['role']],
                [
                    'scope_type' => 'university',
                    'reason' => $p['reason'],
                    'valid_from' => '2026-01-01',
                ]
            );
        }

        // Also ensure Nurul Izzah has records_custodian role if needed for compatibility
        $nurul = User::where('email', 'nurul.izzah@utem.example.test')->first();
        if ($nurul) {
            $nurul->roleAssignments()->firstOrCreate(
                ['role' => 'records_custodian'],
                [
                    'scope_type' => 'university',
                    'reason' => 'Appendix H P6 dual-assignment for demonstration',
                    'valid_from' => '2026-01-01',
                ]
            );
        }
    }

    public function down(): void
    {
        // Safe down: do not delete users or roles in rollback
    }
};
