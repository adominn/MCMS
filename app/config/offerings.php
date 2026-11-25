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
                    'en' => 'Target learner group, eligibility requirements, and prerequisites (if any) are clearly stated',
                    'ms' => 'Kumpulan sasaran, syarat kelayakan dan prasyarat (jika ada) dinyatakan dengan jelas',
                ],
                'info_fee_refund' => [
                    'type' => 'assisted',
                    'legacy_keys' => ['b2_08', 'transparency'],
                    'en' => 'Fees, access period, and refund or cancellation terms (if applicable) are clearly stated',
                    'ms' => 'Yuran, tempoh akses serta terma bayaran balik atau pembatalan (jika berkenaan) dinyatakan dengan jelas',
                ],
                'info_support_contact' => [
                    'type' => 'assisted',
                    'legacy_keys' => ['b2_10', 'b3_07', 'transparency'],
                    'en' => 'Academic and technical support channels (helpdesk and coordinator contact) are identified and active',
                    'ms' => 'Saluran bantuan teknikal dan akademik (meja bantuan dan penyelaras) dikenal pasti dan aktif',
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
                    'legacy_keys' => ['b3_01', 'b3_02', 'materials_platform'],
                    'en' => 'All learning materials are available before launch and have passed the required academic/technical review',
                    'ms' => 'Semua bahan pembelajaran tersedia sebelum penawaran bermula dan telah melalui semakan akademik/teknikal yang diperlukan',
                ],
                'mat_platform_ready' => [
                    'type' => 'assisted',
                    'legacy_keys' => ['b3_02', 'b3_04', 'b5_03', 'materials_platform'],
                    'en' => 'Learning platform course shell and registration method have been tested and activity tracking is enabled',
                    'ms' => 'Platform pembelajaran dan kaedah pendaftaran telah diuji serta penjejakan aktiviti diaktifkan',
                ],
                'mat_access_tested' => [
                    'type' => 'manual',
                    'legacy_keys' => ['b3_03', 'b3_05', 'materials_platform'],
                    'en' => 'Instructor/facilitator and learner access rights on the platform have been set and verified',
                    'ms' => 'Hak akses pengajar/fasilitator dan peserta pada platform telah ditetapkan dan disahkan',
                ],
                'mat_guides_resources' => [
                    'type' => 'manual',
                    'legacy_keys' => ['b3_03', 'b3_04', 'b3_05', 'b3_08', 'materials_platform'],
                    'en' => 'Links, files, videos, activities, and digital resources are accessible, and learner accessibility needs have been considered',
                    'ms' => 'Pautan, fail, video, aktiviti dan sumber digital boleh diakses serta keperluan aksesibiliti peserta telah dipertimbangkan mengikut kesesuaian',
                ],
                'mat_device_specs' => [
                    'type' => 'assisted',
                    'legacy_keys' => ['b3_06', 'materials_platform'],
                    'en' => 'Software, device, or special access requirements have been communicated to learners',
                    'ms' => 'Keperluan perisian, peranti atau capaian khas telah dimaklumkan kepada peserta',
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
                    'en' => 'Assessment instruments are available and aligned with the learning outcomes',
                    'ms' => 'Instrumen pentaksiran tersedia dan dijajarkan dengan hasil pembelajaran',
                ],
                'assess_rubrics' => [
                    'type' => 'assisted',
                    'legacy_keys' => ['b4_02', 'assessment_integrity'],
                    'en' => 'Scoring rubrics/marking schemes or completion criteria are available',
                    'ms' => 'Rubrik/skema pemarkahan atau kriteria penyempurnaan tersedia',
                ],
                'assess_schedule' => [
                    'type' => 'manual',
                    'legacy_keys' => ['b4_03', 'assessment_integrity'],
                    'en' => 'Assessment submission methods and key deadlines are clearly stated to learners',
                    'ms' => 'Kaedah penyerahan pentaksiran dan tarikh penting dinyatakan kepada peserta',
                ],
                'assess_integrity' => [
                    'type' => 'assisted',
                    'legacy_keys' => ['b4_04', 'b4_05', 'assessment_integrity'],
                    'en' => 'Identity verification mechanisms and academic integrity controls are established according to assessment risk',
                    'ms' => 'Mekanisme pengesahan identiti dan kawalan integriti akademik ditetapkan mengikut tahap risiko pentaksiran',
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
                    'legacy_keys' => ['b7_01', 'b7_05', 'change_notification'],
                    'en' => 'Process is ready to notify affected learners of changes to content, schedule, assessment, access, or credit/credential implications',
                    'ms' => 'Proses pemakluman perubahan kandungan, jadual, pentaksiran, akses serta implikasi kredit/credential kepada peserta yang terjejas disediakan',
                ],
                'protect_contingency' => [
                    'type' => 'manual',
                    'legacy_keys' => ['b7_02', 'b7_04', 'change_notification'],
                    'en' => 'Contingency and learner transition mechanisms are defined for disruptions, suspension, termination, or replacement',
                    'ms' => 'Pelan kontingensi dan mekanisme peralihan peserta sedia ada ditentukan bagi gangguan, penggantungan, penamatan atau penggantian penawaran',
                ],
                'protect_rights_appeals' => [
                    'type' => 'assisted',
                    'legacy_keys' => ['b7_03', 'b7_04', 'change_notification'],
                    'en' => 'Offering/credential updates on QV changes, learner completion protection, and grade appeal channels are in place',
                    'ms' => 'Kemas kini maklumat penawaran/credential bagi perubahan status QV, perlindungan penamatan peserta dan saluran rayuan keputusan disediakan',
                ],
            ],
        ],
    ],

    'readiness' => [
        'info_eligibility' => 'Target learner group, eligibility requirements, and prerequisites (if any) are clearly stated',
        'info_fee_refund' => 'Fees, access period, and refund or cancellation terms (if applicable) are clearly stated',
        'info_support_contact' => 'Academic and technical support channels (helpdesk and coordinator contact) are identified and active',
        'mat_complete' => 'All learning materials are available before launch and have passed the required academic/technical review',
        'mat_platform_ready' => 'Learning platform course shell and registration method have been tested and activity tracking is enabled',
        'mat_access_tested' => 'Instructor/facilitator and learner access rights on the platform have been set and verified',
        'mat_guides_resources' => 'Links, files, videos, activities, and digital resources are accessible, and learner accessibility needs have been considered',
        'mat_device_specs' => 'Software, device, or special access requirements have been communicated to learners',
        'assess_instruments' => 'Assessment instruments are available and aligned with the learning outcomes',
        'assess_rubrics' => 'Scoring rubrics/marking schemes or completion criteria are available',
        'assess_schedule' => 'Assessment submission methods and key deadlines are clearly stated to learners',
        'assess_integrity' => 'Identity verification mechanisms and academic integrity controls are established according to assessment risk',
        'protect_changes' => 'Process is ready to notify affected learners of changes to content, schedule, assessment, access, or credit/credential implications',
        'protect_contingency' => 'Contingency and learner transition mechanisms are defined for disruptions, suspension, termination, or replacement',
        'protect_rights_appeals' => 'Offering/credential updates on QV changes, learner completion protection, and grade appeal channels are in place',
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
