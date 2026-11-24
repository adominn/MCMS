<?php

return [
    // Offering operational readiness: 12 automated system checks (ReadinessChecker) + 15 grouped confirmations across 4 sections (D-64, D-65).
    'readiness_sections' => [
        'course_info' => [
            'title_en' => 'Course Information & Support',
            'title_ms' => 'Maklumat Kursus & Sokongan',
            'desc_en' => 'Verify applicant-facing eligibility, fee terms, and active support contact details.',
            'desc_ms' => 'Sahkan syarat kelayakan, terma yuran serta butiran hubungan sokongan yang aktif.',
            'items' => [
                'info_eligibility' => [
                    'type' => 'assisted',
                    'legacy_keys' => ['b2_03', 'transparency'],
                    'en' => 'Eligibility requirements and prerequisites are accurate',
                    'ms' => 'Syarat kelayakan dan prasyarat penyertaan dinyatakan dengan tepat',
                ],
                'info_fee_refund' => [
                    'type' => 'assisted',
                    'legacy_keys' => ['b2_08', 'transparency'],
                    'en' => 'Fee structure and refund or cancellation terms are clearly stated',
                    'ms' => 'Struktur yuran serta polisi bayaran balik atau pembatalan dinyatakan dengan jelas',
                ],
                'info_support_contact' => [
                    'type' => 'assisted',
                    'legacy_keys' => ['b2_10', 'b3_07', 'transparency'],
                    'en' => 'Coordinator and technical helpdesk contact details are active and current',
                    'ms' => 'Maklumat hubungan penyelaras dan bantuan teknikal adalah aktif dan terkini',
                ],
            ],
        ],
        'materials_platform' => [
            'title_en' => 'Learning Materials & Platform',
            'title_ms' => 'Bahan Pembelajaran & Platform',
            'desc_en' => 'Confirm LMS readiness, course content completeness, orientation resources, and learner access.',
            'desc_ms' => 'Sahkan kesediaan LMS, kelengkapan bahan kursus, panduan orientasi serta akses peserta.',
            'items' => [
                'mat_complete' => [
                    'type' => 'manual',
                    'legacy_keys' => ['b3_01', 'materials_platform'],
                    'en' => 'Learning materials are complete and aligned with learning outcomes',
                    'ms' => 'Bahan pembelajaran lengkap dan sejajar dengan hasil pembelajaran',
                ],
                'mat_platform_ready' => [
                    'type' => 'assisted',
                    'legacy_keys' => ['b3_02', 'b5_03', 'materials_platform'],
                    'en' => 'Learning platform course shell is published and activity tracking is enabled',
                    'ms' => 'Ruang kursus pada platform pembelajaran telah diterbitkan dan penjejakan aktiviti diaktifkan',
                ],
                'mat_access_tested' => [
                    'type' => 'manual',
                    'legacy_keys' => ['b3_03', 'materials_platform'],
                    'en' => 'Learner and instructor platform access has been tested and verified',
                    'ms' => 'Akses peserta dan pengajar pada platform telah diuji dan disahkan',
                ],
                'mat_guides_resources' => [
                    'type' => 'manual',
                    'legacy_keys' => ['b3_04', 'b3_05', 'materials_platform'],
                    'en' => 'Orientation guide and supplementary learning resources are accessible',
                    'ms' => 'Panduan orientasi dan sumber pembelajaran tambahan sedia diakses',
                ],
                'mat_device_specs' => [
                    'type' => 'assisted',
                    'legacy_keys' => ['b3_06', 'materials_platform'],
                    'en' => 'Minimum technical and device requirements are communicated to learners',
                    'ms' => 'Spesifikasi peranti dan keperluan teknikal minimum dimaklumkan kepada peserta',
                ],
            ],
        ],
        'assessment_integrity' => [
            'title_en' => 'Assessment & Academic Integrity',
            'title_ms' => 'Pentaksiran & Integriti Akademik',
            'desc_en' => 'Confirm assessment instruments, rubrics, submission deadlines, and integrity safeguards.',
            'desc_ms' => 'Sahkan instrumen pentaksiran, rubrik pemarkahan, jadual penghantaran serta kawalan integriti.',
            'items' => [
                'assess_instruments' => [
                    'type' => 'assisted',
                    'legacy_keys' => ['b4_01', 'assessment_integrity'],
                    'en' => 'Assessment papers, tasks, and quizzes are finalised for this cohort',
                    'ms' => 'Instrumen pentaksiran, tugasan dan kuiz dimuktamadkan bagi kohort ini',
                ],
                'assess_rubrics' => [
                    'type' => 'assisted',
                    'legacy_keys' => ['b4_02', 'assessment_integrity'],
                    'en' => 'Scoring rubrics are complete and aligned with learning outcomes',
                    'ms' => 'Rubrik pemarkahan lengkap dan selaras dengan hasil pembelajaran',
                ],
                'assess_schedule' => [
                    'type' => 'manual',
                    'legacy_keys' => ['b4_03', 'assessment_integrity'],
                    'en' => 'Assessment schedule and submission deadlines are published to learners',
                    'ms' => 'Jadual pentaksiran dan tarikh akhir penghantaran dimaklumkan kepada peserta',
                ],
                'assess_integrity' => [
                    'type' => 'assisted',
                    'legacy_keys' => ['b4_04', 'b4_05', 'assessment_integrity'],
                    'en' => 'Identity verification and academic integrity controls are set up for delivery',
                    'ms' => 'Kawalan pengesahan identiti dan integriti akademik disediakan bagi pelaksanaan',
                ],
            ],
        ],
        'learner_protection' => [
            'title_en' => 'Learner Protection & Contingency',
            'title_ms' => 'Perlindungan Peserta & Kontingensi',
            'desc_en' => 'Confirm change communication procedures, contingency plans, and learner protection channels.',
            'desc_ms' => 'Sahkan prosedur pemakluman perubahan, pelan kontingensi serta saluran perlindungan peserta.',
            'items' => [
                'protect_changes' => [
                    'type' => 'assisted',
                    'legacy_keys' => ['b7_01', 'change_notification'],
                    'en' => 'Process is ready to notify learners promptly of schedule or content changes',
                    'ms' => 'Proses makluman segera kepada peserta bagi perubahan jadual atau kandungan disediakan',
                ],
                'protect_contingency' => [
                    'type' => 'manual',
                    'legacy_keys' => ['b7_02', 'change_notification'],
                    'en' => 'Contingency plan is prepared for delivery disruptions or facilitator absence',
                    'ms' => 'Pelan kontingensi disediakan sekiranya berlaku gangguan penyampaian atau ketiadaan pengajar',
                ],
                'protect_rights_appeals' => [
                    'type' => 'assisted',
                    'legacy_keys' => ['b7_03', 'b7_04', 'change_notification'],
                    'en' => 'Learner completion protection and grade appeal channels are in place',
                    'ms' => 'Perlindungan hak penamatan peserta dan saluran rayuan keputusan disediakan',
                ],
            ],
        ],
    ],

    'readiness' => [
        'info_eligibility' => 'Eligibility requirements and prerequisites are accurate',
        'info_fee_refund' => 'Fee structure and refund or cancellation terms are clearly stated',
        'info_support_contact' => 'Coordinator and technical helpdesk contact details are active and current',
        'mat_complete' => 'Learning materials are complete and aligned with learning outcomes',
        'mat_platform_ready' => 'Learning platform course shell is published and activity tracking is enabled',
        'mat_access_tested' => 'Learner and instructor platform access has been tested and verified',
        'mat_guides_resources' => 'Orientation guide and supplementary learning resources are accessible',
        'mat_device_specs' => 'Minimum technical and device requirements are communicated to learners',
        'assess_instruments' => 'Assessment papers, tasks, and quizzes are finalised for this cohort',
        'assess_rubrics' => 'Scoring rubrics are complete and aligned with learning outcomes',
        'assess_schedule' => 'Assessment schedule and submission deadlines are published to learners',
        'assess_integrity' => 'Identity verification and academic integrity controls are set up for delivery',
        'protect_changes' => 'Process is ready to notify learners promptly of schedule or content changes',
        'protect_contingency' => 'Contingency plan is prepared for delivery disruptions or facilitator absence',
        'protect_rights_appeals' => 'Learner completion protection and grade appeal channels are in place',
    ],
    'outcomes' => ['ready' => 'Ready', 'ready_conditions' => 'Ready with conditions', 'not_ready' => 'Not ready'],
    'status' => ['not_ready' => 'Not ready', 'ready' => 'Ready', 'open' => 'Open', 'running' => 'Running', 'ended' => 'Ended', 'held' => 'On hold', 'cancelled' => 'Cancelled'],

    // Free text must not promise what only a separate decision can give (FR-93, BR-16).
    'forbidden' => [
        '/\bguarantee[sd]?\b.{0,40}\b(credit|transfer|admission|degree|qualification|exemption)/i' => 'a guarantee of credit, transfer, admission or a qualification',
        '/\b(jaminan|menjamin)\b.{0,40}\b(pemindahan\s+kredit|kredit|kemasukan|ijazah|kelayakan|pengecualian)/i' => 'jaminan pemindahan kredit, kemasukan atau kelayakan (guarantee of credit, transfer or qualification)',
        '/\b(will|shall)\s+(be\s+)?(transferred|counted|recogni[sz]ed|accepted)\b/i' => 'a promise that credit will transfer or be recognised',
        '/\b(akan|pasti)\s+(dipindahkan|diiktiraf|diterima|dikira)\b/i' => 'janji pemindahan atau pengiktirafan kredit (promise of credit transfer or recognition)',
        '/\bautomatic(ally)?\b.{0,30}\b(credit|transfer|degree|admission|exemption)/i' => 'automatic credit, transfer, admission or a degree',
        '/\b(automatik|secara\s+automatik)\b.{0,30}\b(kredit|pemindahan|pindahan|ijazah|kemasukan|pengecualian)/i' => 'kredit, pemindahan atau ijazah automatik (automatic credit, transfer or degree)',
        '/\b(kredit|pemindahan|pindahan|ijazah|kemasukan|pengecualian)\s+(secara\s+)?automatik\b/i' => 'kredit, pemindahan atau ijazah automatik (automatic credit, transfer or degree)',
        '/\bleads?\s+to\s+(a|an|your)\s+(degree|diploma|qualification)/i' => 'a promise that it leads to a degree',
        '/\b(membawa|menuju|melayakkan)\s+kepada\s+(suatu\s+)?(ijazah|diploma|kelayakan)\b/i' => 'janji membawa kepada ijazah (promise that it leads to a degree)',
        '/\b(\d+|two|three|four|five|six|several|multiple|a number of)\s+micro-?credentials?\b.{0,40}\b(lead|leads|equal|equals|make|makes|count|counts)\b.{0,30}\b(degree|diploma)/i' => 'a number of micro-credentials adding up to a degree',
        '/\b(\d+|dua|tiga|empat|lima|enam|beberapa)\s+micro-?credentials?\b.{0,40}\b(menjadikan|membawa|bersamaan|dikira|membentuk)\b.{0,30}\b(ijazah|diploma)/i' => 'sejumlah micro-credential membentuk ijazah (micro-credentials adding up to a degree)',
        '/(\b\d+(\.\d+)?\s+credits?\b|\bmqf\s+level\s*\d|\bquality[- ]verified\b)/i' => 'a credit value, MQF level or Quality Verification claim (these appear only through the generated statement)',
        '/(\b\d+(\.\d+)?\s+kredit\b|\btahap\s+mqf\s*\d|\bterverifikasi\s+kualiti\b|\bdisahkan\s+kualiti\b)/i' => 'tuntutan nilai kredit, tahap MQF atau pengesahan kualiti (credit, MQF, or QV claim)',
    ],

    'labels' => [
        'catalogue' => ['en' => 'Catalogue', 'ms' => 'Katalog'], 'home' => ['en' => 'Home', 'ms' => 'Utama'], 'how' => ['en' => 'How recognition works', 'ms' => 'Cara pengiktirafan berfungsi'],
        'pathways' => ['en' => 'Pathways to a degree', 'ms' => 'Laluan ke ijazah'], 'search' => ['en' => 'Search', 'ms' => 'Cari'], 'filters' => ['en' => 'Filters', 'ms' => 'Penapis'],
        'category' => ['en' => 'Category', 'ms' => 'Kategori'], 'ptj' => ['en' => 'Faculty or centre', 'ms' => 'Fakulti atau pusat'], 'mode' => ['en' => 'Delivery mode', 'ms' => 'Mod penyampaian'],
        'language' => ['en' => 'Language', 'ms' => 'Bahasa'], 'starts' => ['en' => 'Starts after', 'ms' => 'Bermula selepas'], 'fee' => ['en' => 'Fee', 'ms' => 'Yuran'], 'free' => ['en' => 'Free only', 'ms' => 'Percuma sahaja'],
        'max_fee' => ['en' => 'Highest fee (RM)', 'ms' => 'Yuran tertinggi (RM)'], 'field' => ['en' => 'Field', 'ms' => 'Bidang'], 'outcomes' => ['en' => 'Learning outcomes', 'ms' => 'Hasil pembelajaran'],
        'modules' => ['en' => 'Modules', 'ms' => 'Modul'], 'slt' => ['en' => 'Learning time (SLT)', 'ms' => 'Masa pembelajaran (SLT)'], 'prereq' => ['en' => 'Prerequisites', 'ms' => 'Prasyarat'],
        'access' => ['en' => 'Access period', 'ms' => 'Tempoh akses'], 'assessment' => ['en' => 'Assessment', 'ms' => 'Pentaksiran'], 'standard' => ['en' => 'Pass standard', 'ms' => 'Standard lulus'],
        'completion' => ['en' => 'Completion conditions', 'ms' => 'Syarat penamatan'], 'support' => ['en' => 'Learning support', 'ms' => 'Sokongan pembelajaran'], 'credential' => ['en' => 'Credential you receive', 'ms' => 'Kredensial yang diterima'],
        'recognition' => ['en' => 'What this does and does not give you', 'ms' => 'Apa yang diberi dan tidak diberi'], 'opens' => ['en' => 'Registration opens', 'ms' => 'Pendaftaran dibuka'],
        'open' => ['en' => 'Open for registration', 'ms' => 'Dibuka untuk pendaftaran'], 'closed' => ['en' => 'Registration closed', 'ms' => 'Pendaftaran ditutup'], 'none' => ['en' => 'No offerings match.', 'ms' => 'Tiada tawaran yang sepadan.'],
        'start' => ['en' => 'Starts', 'ms' => 'Bermula'], 'end' => ['en' => 'Ends', 'ms' => 'Tamat'], 'capacity' => ['en' => 'Places', 'ms' => 'Tempat'], 'staged' => ['en' => 'Completion in stages', 'ms' => 'Penamatan berperingkat'],
        'yes' => ['en' => 'Allowed', 'ms' => 'Dibenarkan'], 'no' => ['en' => 'Not allowed: all modules together', 'ms' => 'Tidak dibenarkan: semua modul bersama'], 'register' => ['en' => 'Register', 'ms' => 'Daftar'],
    ],
];
