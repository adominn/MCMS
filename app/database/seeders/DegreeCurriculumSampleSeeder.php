<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DegreeCurriculumSampleSeeder extends Seeder
{
    public function run(): void
    {
        $bits = DB::table('programmes')->where('code', 'BITS')->first();
        if (! $bits) {
            return;
        }
        $bitd = DB::table('programmes')->where('code', 'BITD')->first();
        if (! $bitd) {
            $bitdId = DB::table('programmes')->insertGetId([
                'code' => 'BITD',
                'name' => 'Bachelor of Computer Science (Database Management) with Honours',
                'name_ms' => 'Sarjana Muda Sains Komputer (Pengurusan Pangkalan Data) dengan Kepujian',
                'mqf_level' => 6,
                'level_type' => 'bachelor',
                'mode' => 'conventional',
                'accreditation_status' => 'accredited',
                'total_credits' => 122,
                'ptj_id' => $bits->ptj_id,
                'transfer_limit_percent' => 50,
                'non_transferable' => '["BITU 3973","BITU 3983","BITU 3926"]',
                'curriculum_version' => '2025/2026',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $bitd = DB::table('programmes')->find($bitdId);
        }

        // Ensure illustrative parent courses exist (reusing existing BITS / BITD parent courses where present)
        $courses = [
            // Shared University Compulsory (W)
            ['MPU 21072', 'Penghayatan Etika dan Peradaban', 'Penghayatan Etika dan Peradaban', 2, 2, 0, false, false, 'graded'],
            ['BLHW 1762', 'Philosophy and Current Issues', 'Falsafah dan Isu Semasa', 2, 2, 0, false, false, 'graded'],
            ['BLLW 1142', 'English for Academic Purposes', 'Bahasa Inggeris untuk Tujuan Akademik', 2, 2, 0, false, false, 'graded'],
            ['BLLW 2152', 'Academic Writing', 'Penulisan Akademik', 2, 2, 0, false, false, 'graded'],
            ['BLLW 3162', 'English for Professional Interaction', 'Bahasa Inggeris untuk Interaksi Profesional', 2, 2, 0, false, false, 'graded'],
            ['BLHW 2792', 'Integrity and Anti-Corruption', 'Kursus Integriti dan Anti Rasuah', 2, 2, 0, false, false, 'graded'],
            ['BTMW 4012', 'Technology Entrepreneurship', 'Keusahawanan Teknologi', 2, 2, 0, false, false, 'graded'],
            ['BLLW 1202', 'Third Language', 'Bahasa Ketiga', 2, 2, 0, true, false, 'graded'],
            ['BKKW 1011', 'Co-Curriculum I', 'Ko-Kurikulum I', 1, 0, 3, true, false, 'graded'],
            ['BKKW 2011', 'Co-Curriculum II', 'Ko-Kurikulum II', 1, 0, 3, true, false, 'graded'],

            // Shared & Core Computing (P)
            ['BITS 1213', 'Programming with Python & Java', 'Pengaturcaraan dengan Python & Java', 3, 2, 2, false, false, 'graded'],
            ['BITS 1123', 'Data Structures', 'Struktur Data', 3, 2, 2, false, false, 'graded'],
            ['BITS 1124', 'Algorithms', 'Algoritma', 3, 2, 2, false, false, 'graded'],
            ['BITS 1125', 'Databases', 'Pangkalan Data', 3, 2, 2, false, false, 'graded'],
            ['BITS 1313', 'Relational Database Systems', 'Sistem Pangkalan Data Hubungan', 3, 2, 2, false, false, 'graded'],
            ['BITS 2313', 'Computer Networks and Distributed Systems', 'Rangkaian Komputer dan Sistem Teragih', 3, 2, 2, false, false, 'graded'],
            ['BITS 3423', 'Cloud and Microservices Architecture', 'Seni Bina Awan dan Perkhidmatan Mikro', 3, 2, 2, false, false, 'graded'],
            ['BITC 1113', 'Computer Organization and Architecture', 'Organisasi dan Seni Bina Komputer', 3, 2, 2, false, false, 'graded'],
            ['BITC 1213', 'Operating Systems', 'Sistem Pengendalian', 3, 2, 2, false, false, 'graded'],
            ['BITC 1233', 'Discrete Structures', 'Struktur Diskret', 3, 2, 2, false, false, 'graded'],
            ['BITC 2113', 'System Analysis and Design', 'Analisis dan Reka Bentuk Sistem', 3, 2, 2, false, false, 'graded'],
            ['BITC 2213', 'Human Computer Interaction', 'Interaksi Manusia Komputer', 3, 2, 2, false, false, 'graded'],
            ['BITC 2313', 'Artificial Intelligence', 'Kecerdasan Buatan', 3, 2, 2, false, false, 'graded'],
            ['BITC 3113', 'Information Security', 'Keselamatan Maklumat', 3, 2, 2, false, false, 'graded'],
            ['BITC 3213', 'Professional Practices in Computing', 'Amalan Profesional dalam Pengkomputeran', 3, 2, 2, false, false, 'graded'],

            // Software Development Specialization (BITS - K)
            ['BITS 2213', 'Software Requirement Engineering', 'Kejuruteraan Keperluan Perisian', 3, 2, 2, false, false, 'graded'],
            ['BITS 2223', 'Software Architecture and Design', 'Seni Bina dan Reka Bentuk Perisian', 3, 2, 2, false, false, 'graded'],
            ['BITS 2413', 'Web Application Engineering', 'Kejuruteraan Aplikasi Web', 3, 2, 2, false, false, 'graded'],
            ['BITS 3113', 'Software Verification and Validation', 'Pengesahan dan Validasi Perisian', 3, 2, 2, false, false, 'graded'],
            ['BITS 3213', 'Software Project Management', 'Pengurusan Projek Perisian', 3, 2, 2, false, false, 'graded'],
            ['BITS 3313', 'Mobile Application Development', 'Pembangunan Aplikasi Mudah Alih', 3, 2, 2, false, false, 'graded'],
            ['BITS 3413', 'DevOps and Continuous Delivery', 'DevOps dan Penghantaran Berterusan', 3, 2, 2, false, false, 'graded'],
            ['BITS 3513', 'Software Quality Assurance', 'Jaminan Kualiti Perisian', 3, 2, 2, false, false, 'graded'],

            // Database Management Specialization (BITD - K)
            ['BITD 1113', 'Programming Fundamentals', 'Asas Pengaturcaraan', 3, 2, 2, false, false, 'graded'],
            ['BITD 1123', 'Object-Oriented Programming', 'Pengaturcaraan Berorientasikan Objek', 3, 2, 2, false, false, 'graded'],
            ['BITD 1233', 'Database Systems', 'Sistem Pangkalan Data', 3, 2, 2, false, false, 'graded'],
            ['BITD 2213', 'Database Administration', 'Pentadbiran Pangkalan Data', 3, 2, 2, false, false, 'graded'],
            ['BITD 2223', 'Data Warehousing and Mining', 'Pergudangan dan Perlombongan Data', 3, 2, 2, false, false, 'graded'],
            ['BITD 3213', 'Advanced Database Management', 'Pengurusan Pangkalan Data Lanjutan', 3, 2, 2, false, false, 'graded'],
            ['BITD 3313', 'Applied Predictive Analytics', 'Analitik Ramalan Gunaan', 3, 2, 2, false, false, 'graded'],
            ['BITD 3413', 'Big Data Architecture', 'Seni Bina Data Raya', 3, 2, 2, false, false, 'graded'],
            ['BITD 3513', 'Database Security and Governance', 'Keselamatan dan Tadbir Urus Pangkalan Data', 3, 2, 2, false, false, 'graded'],

            // Shared Workshop, Capstone, Industrial Training & Electives
            ['BITU 2913', 'Workshop I', 'Bengkel I', 3, 1, 2, false, false, 'graded'],
            ['BITU 3923', 'Workshop II', 'Bengkel II', 3, 1, 2, false, false, 'graded'],
            ['BITU 3973', 'Final Year Project I', 'Projek Tahun Akhir I', 3, 1, 2, false, false, 'graded'],
            ['BITU 3983', 'Final Year Project II', 'Projek Tahun Akhir II', 3, 1, 2, false, false, 'graded'],
            ['BITU 3926', 'Industrial Training', 'Latihan Industri', 6, 0, 0, false, false, 'attend_pass'],
            ['BITU 3946', 'Industrial Training Report', 'Laporan Latihan Industri', 6, 0, 0, false, false, 'graded'],
            ['BITE 3013', 'Free Elective I', 'Elektif Bebas I', 3, 2, 2, true, false, 'graded'],
            ['BITE 3023', 'Free Elective II', 'Elektif Bebas II', 3, 2, 2, true, false, 'graded'],
            ['BITE 3032', 'Special Topics Seminar', 'Seminar Topik Khas', 2, 2, 0, false, false, 'graded'],
        ];

        $ids = [];
        foreach ($courses as [$code, $nameEn, $nameMs, $cr, $lect, $lab, $placeholder, $cert, $grading]) {
            $existing = DB::table('parent_courses')->where('code', $code)->first();
            if ($existing) {
                DB::table('parent_courses')->where('id', $existing->id)->update([
                    'name_ms' => $existing->name_ms ?: $nameMs,
                    'lecture_hours' => $existing->lecture_hours ?? $lect,
                    'lab_hours' => $existing->lab_hours ?? $lab,
                    'is_placeholder' => $placeholder,
                    'is_certification' => $cert,
                    'grading_mode' => $grading,
                    'status' => $existing->status ?: 'active',
                    'updated_at' => now(),
                ]);
                $ids[$code] = $existing->id;
            } else {
                $ids[$code] = DB::table('parent_courses')->insertGetId([
                    'code' => $code,
                    'name' => $nameEn,
                    'name_ms' => $nameMs,
                    'credit' => $cr,
                    'slt_hours' => $cr * 40,
                    'lecture_hours' => $lect,
                    'lab_hours' => $lab,
                    'clos' => 'Demonstrate core competencies and applied skills for '.$nameEn.'.',
                    'synopsis_en' => 'Covers foundational concepts, design principles, and hands-on practice in '.$nameEn.'.',
                    'synopsis_ms' => 'Merangkumi konsep asas, prinsip reka bentuk dan amalan praktikal bagi '.$nameMs.'.',
                    'is_placeholder' => $placeholder,
                    'is_certification' => $cert,
                    'grading_mode' => $grading,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Update programme metadata
        DB::table('programmes')->where('id', $bits->id)->update([
            'name_ms' => 'Sarjana Muda Sains Komputer (Pembangunan Perisian) dengan Kepujian',
            'level_type' => 'bachelor',
            'min_semesters' => 7,
            'max_semesters' => 11,
        ]);
        DB::table('programmes')->where('id', $bitd->id)->update([
            'name_ms' => 'Sarjana Muda Sains Komputer (Pengurusan Pangkalan Data) dengan Kepujian',
            'level_type' => 'bachelor',
            'min_semesters' => 7,
            'max_semesters' => 11,
        ]);

        // Seed BITS 2025/2026 (120 credits)
        $bitsVerId = $this->upsertVersion($bits->id, '2025/2026', 120, ['W' => 18, 'P' => 69, 'K' => 27, 'E' => 6]);
        $bitsPoolId = $this->upsertElectivePool($bitsVerId, 'free_elective', 'Free Elective Pool', 'Senarai Elektif Bebas', 2, 3.0, [
            $ids['BITD 2223'], $ids['BITD 3313'], $ids['BITD 3413'],
        ]);

        $bitsPlacements = [
            // Year 1 Sem 1 (16 cr)
            [1, 1, 'regular', 'W', 'required', null, 'BLHW 1762', 2],
            [1, 1, 'regular', 'W', 'required', null, 'BLLW 1142', 2],
            [1, 1, 'regular', 'P', 'required', null, 'BITS 1213', 3],
            [1, 1, 'regular', 'P', 'required', null, 'BITC 1113', 3],
            [1, 1, 'regular', 'P', 'required', null, 'BITS 1125', 3],
            [1, 1, 'regular', 'P', 'required', null, 'BITC 1233', 3],
            // Year 1 Sem 2 (18 cr)
            [1, 2, 'regular', 'W', 'required', null, 'BLLW 1202', 2],
            [1, 2, 'regular', 'W', 'required', null, 'BKKW 1011', 1],
            [1, 2, 'regular', 'P', 'required', null, 'BITS 1123', 3],
            [1, 2, 'regular', 'P', 'required', null, 'BITC 1213', 3],
            [1, 2, 'regular', 'P', 'required', null, 'BITC 2113', 3],
            [1, 2, 'regular', 'P', 'required', null, 'BITC 2313', 3],
            [1, 2, 'regular', 'P', 'required', null, 'BITS 1313', 3],
            // Year 2 Sem 1 (18 cr)
            [2, 1, 'regular', 'W', 'required', null, 'BLLW 2152', 2],
            [2, 1, 'regular', 'W', 'required', null, 'MPU 21072', 2],
            [2, 1, 'regular', 'W', 'required', null, 'BKKW 2011', 1],
            [2, 1, 'regular', 'P', 'required', null, 'BITS 1124', 3],
            [2, 1, 'regular', 'P', 'required', null, 'BITC 2213', 3],
            [2, 1, 'regular', 'K', 'required', null, 'BITS 2213', 3],
            [2, 1, 'regular', 'K', 'required', null, 'BITU 2913', 3],
            [2, 1, 'regular', 'P', 'required', null, 'BITS 2313', 3],
            // Year 2 Sem 2 (17 cr -> adjust to 120 total: 16+18+20+17+17+17+3+12 = 120)
            [2, 2, 'regular', 'W', 'required', null, 'BLHW 2792', 2],
            [2, 2, 'regular', 'P', 'required', null, 'BITC 3113', 3],
            [2, 2, 'regular', 'K', 'required', null, 'BITS 2223', 3],
            [2, 2, 'regular', 'K', 'required', null, 'BITS 2413', 3],
            [2, 2, 'regular', 'K', 'required', null, 'BITS 3113', 3],
            [2, 2, 'regular', 'E', 'elective_slot', $bitsPoolId, 'BITE 3013', 3],
            // Year 3 Sem 1 (17 cr)
            [3, 1, 'regular', 'W', 'required', null, 'BLLW 3162', 2],
            [3, 1, 'regular', 'K', 'required', null, 'BITS 3213', 3],
            [3, 1, 'regular', 'K', 'required', null, 'BITS 3313', 3],
            [3, 1, 'regular', 'K', 'required', null, 'BITS 3413', 3],
            [3, 1, 'regular', 'K', 'required', null, 'BITU 3923', 3],
            [3, 1, 'regular', 'E', 'elective_slot', $bitsPoolId, 'BITE 3023', 3],
            // Year 3 Sem 2 (17 cr)
            [3, 2, 'regular', 'W', 'required', null, 'BTMW 4012', 2],
            [3, 2, 'regular', 'P', 'required', null, 'BITC 3213', 3],
            [3, 2, 'regular', 'P', 'required', null, 'BITS 3423', 3],
            [3, 2, 'regular', 'K', 'required', null, 'BITS 3513', 3],
            [3, 2, 'regular', 'P', 'required', null, 'BITU 3973', 3],
            [3, 2, 'regular', 'P', 'required', null, 'BITD 1123', 3],
            // Year 3 Short Semester (3 cr)
            [3, 3, 'short', 'P', 'required', null, 'BITU 3983', 3],
            // Year 4 Sem 1 (12 cr)
            [4, 1, 'regular', 'P', 'required', null, 'BITU 3926', 6],
            [4, 1, 'regular', 'P', 'required', null, 'BITU 3946', 6],
        ];
        $this->syncPlacements($bitsVerId, $bitsPlacements, $ids);

        // Seed BITD 2025/2026 (122 credits)
        $bitdVerId = $this->upsertVersion($bitd->id, '2025/2026', 122, ['W' => 18, 'P' => 71, 'K' => 27, 'E' => 6]);
        $bitdPoolId = $this->upsertElectivePool($bitdVerId, 'free_elective', 'Free Elective Pool', 'Senarai Elektif Bebas', 2, 3.0, [
            $ids['BITS 2413'], $ids['BITS 3313'], $ids['BITS 3423'],
        ]);

        $bitdPlacements = [
            // Year 1 Sem 1 (16 cr)
            [1, 1, 'regular', 'W', 'required', null, 'BLHW 1762', 2],
            [1, 1, 'regular', 'W', 'required', null, 'BLLW 1142', 2],
            [1, 1, 'regular', 'P', 'required', null, 'BITD 1113', 3],
            [1, 1, 'regular', 'P', 'required', null, 'BITC 1113', 3],
            [1, 1, 'regular', 'P', 'required', null, 'BITD 1233', 3],
            [1, 1, 'regular', 'P', 'required', null, 'BITC 1233', 3],
            // Year 1 Sem 2 (18 cr)
            [1, 2, 'regular', 'W', 'required', null, 'BLLW 1202', 2],
            [1, 2, 'regular', 'W', 'required', null, 'BKKW 1011', 1],
            [1, 2, 'regular', 'P', 'required', null, 'BITS 1123', 3],
            [1, 2, 'regular', 'P', 'required', null, 'BITC 1213', 3],
            [1, 2, 'regular', 'P', 'required', null, 'BITC 2113', 3],
            [1, 2, 'regular', 'P', 'required', null, 'BITC 2313', 3],
            [1, 2, 'regular', 'P', 'required', null, 'BITD 1123', 3],
            // Year 2 Sem 1 (20 cr)
            [2, 1, 'regular', 'W', 'required', null, 'BLLW 2152', 2],
            [2, 1, 'regular', 'W', 'required', null, 'MPU 21072', 2],
            [2, 1, 'regular', 'W', 'required', null, 'BKKW 2011', 1],
            [2, 1, 'regular', 'P', 'required', null, 'BITS 1124', 3],
            [2, 1, 'regular', 'P', 'required', null, 'BITC 2213', 3],
            [2, 1, 'regular', 'K', 'required', null, 'BITD 2213', 3],
            [2, 1, 'regular', 'K', 'required', null, 'BITU 2913', 3],
            [2, 1, 'regular', 'P', 'required', null, 'BITS 2313', 3],
            // Year 2 Sem 2 (17 cr)
            [2, 2, 'regular', 'W', 'required', null, 'BLHW 2792', 2],
            [2, 2, 'regular', 'P', 'required', null, 'BITC 3113', 3],
            [2, 2, 'regular', 'K', 'required', null, 'BITD 2223', 3],
            [2, 2, 'regular', 'K', 'required', null, 'BITD 3213', 3],
            [2, 2, 'regular', 'K', 'required', null, 'BITD 3313', 3],
            [2, 2, 'regular', 'E', 'elective_slot', $bitdPoolId, 'BITE 3013', 3],
            // Year 3 Sem 1 (17 cr)
            [3, 1, 'regular', 'W', 'required', null, 'BLLW 3162', 2],
            [3, 1, 'regular', 'K', 'required', null, 'BITD 3413', 3],
            [3, 1, 'regular', 'K', 'required', null, 'BITD 3513', 3],
            [3, 1, 'regular', 'K', 'required', null, 'BITS 3213', 3],
            [3, 1, 'regular', 'K', 'required', null, 'BITU 3923', 3],
            [3, 1, 'regular', 'E', 'elective_slot', $bitdPoolId, 'BITE 3023', 3],
            // Year 3 Sem 2 (19 cr -> 16+18+20+17+17+19+3+12 = 122)
            [3, 2, 'regular', 'W', 'required', null, 'BTMW 4012', 2],
            [3, 2, 'regular', 'P', 'required', null, 'BITC 3213', 3],
            [3, 2, 'regular', 'P', 'required', null, 'BITS 1313', 3],
            [3, 2, 'regular', 'K', 'required', null, 'BITS 3513', 3],
            [3, 2, 'regular', 'P', 'required', null, 'BITU 3973', 3],
            [3, 2, 'regular', 'P', 'required', null, 'BITS 2213', 3],
            [3, 2, 'regular', 'P', 'required', null, 'BITE 3032', 2],
            // Year 3 Short Semester (3 cr)
            [3, 3, 'short', 'P', 'required', null, 'BITU 3983', 3],
            // Year 4 Sem 1 (12 cr)
            [4, 1, 'regular', 'P', 'required', null, 'BITU 3926', 6],
            [4, 1, 'regular', 'P', 'required', null, 'BITU 3946', 6],
        ];
        $this->syncPlacements($bitdVerId, $bitdPlacements, $ids);

        // Prerequisites (global chains)
        $prereqs = [
            ['BLLW 2152', 'BLLW 1142', 'course'],
            ['BLLW 3162', 'BLLW 2152', 'course'],
            ['BITS 1123', 'BITS 1213', 'course'],
            ['BITS 1124', 'BITS 1123', 'course'],
            ['BITS 3423', 'BITS 2313', 'course'],
            ['BITD 2213', 'BITD 1233', 'course'],
            ['BITD 3213', 'BITD 2213', 'course'],
            ['BITU 2913', 'BITS 1213', 'course'],
            ['BITU 3923', 'BITU 2913', 'course'],
            ['BITU 3973', 'BITU 3923', 'course'],
            ['BITU 3983', 'BITU 3973', 'course'],
            ['BITU 3926', 'BITU 3983', 'course'],
            ['BITU 3946', 'BITU 3983', 'course'],
        ];
        foreach ($prereqs as [$courseCode, $reqCode, $type]) {
            DB::table('course_prerequisites')->updateOrInsert(
                [
                    'parent_course_id' => $ids[$courseCode],
                    'prerequisite_course_id' => $ids[$reqCode],
                    'programme_version_id' => null,
                ],
                [
                    'rule_type' => $type,
                    'logic_group' => 'AND',
                    'min_grade' => 'C',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Sample Course Equivalences (covering exact, partial, conditional, no_transfer, to_confirm)
        $equivalences = [
            [
                'reference' => 'EQ-2026-0001',
                'source_course_id' => $ids['BITS 1125'],
                'source_programme_id' => $bits->id,
                'target_course_id' => $ids['BITD 1233'],
                'target_programme_id' => $bitd->id,
                'equivalence_type' => 'exact',
                'credits_counted' => 3.0,
                'direction' => 'bidirectional',
                'min_grade' => 'C',
                'validity_years' => 10,
                'lo_match_percent' => 95,
                'condition_note_en' => 'Full learning outcome and credit equivalence between Databases and Database Systems.',
                'condition_note_ms' => 'Kesetaraan penuh hasil pembelajaran dan kredit antara Pangkalan Data dan Sistem Pangkalan Data.',
                'status' => 'approved',
                'approval_ref' => 'SENATE/2026/EQ-01',
            ],
            [
                'reference' => 'EQ-2026-0002',
                'source_course_id' => $ids['BITD 1113'],
                'source_programme_id' => $bitd->id,
                'target_course_id' => $ids['BITS 1213'],
                'target_programme_id' => $bits->id,
                'equivalence_type' => 'exact',
                'credits_counted' => 3.0,
                'direction' => 'bidirectional',
                'min_grade' => 'C',
                'validity_years' => 10,
                'lo_match_percent' => 90,
                'condition_note_en' => 'Equivalent introductory programming course.',
                'condition_note_ms' => 'Kursus pengaturcaraan pengenalan yang setara.',
                'status' => 'approved',
                'approval_ref' => 'SENATE/2026/EQ-02',
            ],
            [
                'reference' => 'EQ-2026-0003',
                'source_course_id' => $ids['BITD 2213'],
                'source_programme_id' => $bitd->id,
                'target_course_id' => $ids['BITS 3423'],
                'target_programme_id' => $bits->id,
                'equivalence_type' => 'conditional',
                'credits_counted' => 3.0,
                'direction' => 'one_way',
                'min_grade' => 'B-',
                'validity_years' => 10,
                'lo_match_percent' => 80,
                'condition_note_en' => 'Requires minimum grade B- and completion of a cloud deployment lab assessment.',
                'condition_note_ms' => 'Memerlukan gred minimum B- dan penyempurnaan pentaksiran makmal penggunaan awan.',
                'status' => 'approved',
                'approval_ref' => 'SENATE/2026/EQ-03',
            ],
            [
                'reference' => 'EQ-2026-0004',
                'source_course_id' => $ids['BITD 2223'],
                'source_programme_id' => $bitd->id,
                'target_course_id' => $ids['BITS 2413'],
                'target_programme_id' => $bits->id,
                'equivalence_type' => 'partial',
                'credits_counted' => 2.0,
                'direction' => 'one_way',
                'min_grade' => 'C',
                'validity_years' => 10,
                'lo_match_percent' => 65,
                'condition_note_en' => 'Partial credit (2 of 3 credits); learner must complete 1-credit bridging module.',
                'condition_note_ms' => 'Kredit sebahagian (2 daripada 3 kredit); pelajar perlu melengkapkan modul penyambung 1 kredit.',
                'status' => 'dd_endorsed',
                'approval_ref' => null,
            ],
            [
                'reference' => 'EQ-2026-0005',
                'source_course_id' => $ids['BITU 3983'],
                'source_programme_id' => $bits->id,
                'target_course_id' => $ids['BITU 3983'],
                'target_programme_id' => $bitd->id,
                'equivalence_type' => 'no_transfer',
                'credits_counted' => 0.0,
                'direction' => 'bidirectional',
                'min_grade' => null,
                'validity_years' => 10,
                'lo_match_percent' => 0,
                'condition_note_en' => 'Final Year Project is a non-transferable capstone requirement and must be completed within the receiving programme.',
                'condition_note_ms' => 'Projek Tahun Akhir tidak boleh dipindahkan dan mesti disempurnakan dalam program penerima.',
                'status' => 'approved',
                'approval_ref' => 'SENATE/2026/EQ-05',
            ],
            [
                'reference' => 'EQ-2026-0006',
                'source_course_id' => $ids['BITD 3313'],
                'source_programme_id' => $bitd->id,
                'target_course_id' => $ids['BITS 3113'],
                'target_programme_id' => $bits->id,
                'equivalence_type' => 'to_confirm',
                'credits_counted' => 0.0,
                'direction' => 'one_way',
                'min_grade' => 'C',
                'validity_years' => 10,
                'lo_match_percent' => null,
                'condition_note_en' => 'Pending formal curriculum evaluation by Faculty, CODL, and Senate.',
                'condition_note_ms' => 'Menunggu penilaian kurikulum rasmi oleh Fakulti, CODL dan Senat.',
                'status' => 'to_confirm',
                'approval_ref' => null,
            ],
        ];

        foreach ($equivalences as $eq) {
            DB::table('course_equivalences')->updateOrInsert(
                ['reference' => $eq['reference']],
                $eq + [
                    'effective_from' => $eq['status'] === 'approved' ? now()->toDateString() : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function upsertVersion(int $programmeId, string $session, int $totalCredits, array $summary): int
    {
        $existing = DB::table('programme_versions')
            ->where('programme_id', $programmeId)
            ->where('academic_session', $session)
            ->first();

        if ($existing) {
            return (int) $existing->id;
        }

        return (int) DB::table('programme_versions')->insertGetId([
            'programme_id' => $programmeId,
            'academic_session' => $session,
            'version_label' => 'Session '.$session,
            'total_credits' => $totalCredits,
            'component_summary' => json_encode($summary),
            'status' => 'active',
            'effective_from' => now()->toDateString(),
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function upsertElectivePool(int $versionId, string $code, string $nameEn, string $nameMs, int $reqCount, float $credits, array $courseIds): int
    {
        $existing = DB::table('elective_pools')
            ->where('programme_version_id', $versionId)
            ->where('code', $code)
            ->first();

        $poolId = $existing ? (int) $existing->id : (int) DB::table('elective_pools')->insertGetId([
            'programme_version_id' => $versionId,
            'code' => $code,
            'name_en' => $nameEn,
            'name_ms' => $nameMs,
            'required_count' => $reqCount,
            'credits_per_course' => $credits,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($courseIds as $cid) {
            DB::table('elective_pool_courses')->updateOrInsert(
                ['elective_pool_id' => $poolId, 'parent_course_id' => $cid],
                ['is_available' => true]
            );
        }

        return $poolId;
    }

    private function syncPlacements(int $versionId, array $rows, array $ids): void
    {
        if (DB::table('programme_course_placements')->where('programme_version_id', $versionId)->exists()) {
            return;
        }

        foreach ($rows as $idx => [$year, $sem, $semType, $comp, $reqType, $poolId, $code, $credits]) {
            DB::table('programme_course_placements')->insert([
                'programme_version_id' => $versionId,
                'parent_course_id' => $ids[$code],
                'year_no' => $year,
                'semester_no' => $sem,
                'semester_type' => $semType,
                'sort_order' => ($idx + 1) * 10,
                'component_code' => $comp,
                'requirement_type' => $reqType,
                'elective_pool_id' => $poolId,
                'credits_override' => $credits,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
