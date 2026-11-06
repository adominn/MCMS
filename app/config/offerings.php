<?php

return [
    // Guide Appendix B2 to B7 (FR-85)
    'readiness' => [
        'transparency' => 'Transparency of information',
        'materials_platform' => 'Learning materials and platform',
        'assessment_integrity' => 'Assessment and integrity',
        'records' => 'Participant and achievement records',
        'completion_credential' => 'Completion and credential process',
        'change_notification' => 'Change notification',
    ],
    'outcomes' => ['ready' => 'Ready', 'ready_conditions' => 'Ready with conditions', 'not_ready' => 'Not ready'],
    'status' => ['not_ready' => 'Not ready', 'ready' => 'Ready', 'open' => 'Open', 'running' => 'Running', 'ended' => 'Ended', 'held' => 'On hold', 'cancelled' => 'Cancelled'],

    // Free text must not promise what only a separate decision can give (FR-93, BR-16).
    'forbidden' => [
        '/\bguarantee[sd]?\b.{0,40}\b(credit|transfer|admission|degree|qualification|exemption)/i' => 'a guarantee of credit, transfer, admission or a qualification',
        '/\b(will|shall)\s+(be\s+)?(transferred|counted|recogni[sz]ed|accepted)\b/i' => 'a promise that credit will transfer or be recognised',
        '/\bautomatic(ally)?\b.{0,30}\b(credit|transfer|degree|admission|exemption)/i' => 'automatic credit, transfer, admission or a degree',
        '/\bleads?\s+to\s+(a|an|your)\s+(degree|diploma|qualification)/i' => 'a promise that it leads to a degree',
        '/\b(\d+|two|three|four|five|six|several|multiple|a number of)\s+micro-?credentials?\b.{0,40}\b(lead|leads|equal|equals|make|makes|count|counts)\b.{0,30}\b(degree|diploma)/i' => 'a number of micro-credentials adding up to a degree',
        '/(\b\d+(\.\d+)?\s+credits?\b|\bmqf\s+level\s*\d|\bquality[- ]verified\b)/i' => 'a credit value, MQF level or Quality Verification claim (these appear only through the generated statement)',
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
