<?php

// Guide Appendix D: Senarai Evidens Quality Verification (QV) bagi Stand-Alone Micro-Credential (SAMC)
return [
    // Appendix D3 to D7 Checklist items (including backward-compatible high-level keys)
    'checklist' => [
        // Legacy keys for backward compatibility
        'academic_design' => 'Academic design: learning outcomes, mapping and SLT',
        'assessment_achievement' => 'Assessment and achievement: plan, cohort results and analysis',
        'delivery_support' => 'Delivery and participant support',
        'quality_cqi' => 'Quality assurance and CQI: feedback and improvement records',
        'credential' => 'Digital Credential: design and issuing',

        // D2: Kelayakan Asas SAMC untuk QV (6 items)
        'd2_1_standalone' => 'D2.1 SAMC dibangunkan sebagai penawaran berdiri sendiri (bukan kursus rasmi UTeM)',
        'd2_2_slt_min' => 'D2.2 Jumlah pembelajaran sekurang-kurangnya 40 jam SLT / notional learning time',
        'd2_3_registered_provider' => 'D2.3 Penyedia merupakan entiti berdaftar dan diberi kuasa menawarkan SAMC',
        'd2_4_completed_cohort' => 'D2.4 Sekurang-kurangnya satu (1) kohort peserta telah lengkap sebelum permohonan',
        'd2_5_cohort_evidence' => 'D2.5 Bukti penyertaan, pentaksiran dan pencapaian kohort lengkap tersedia',
        'd2_6_credit_justification' => 'D2.6 Cadangan kredit dan tahap MQF mempunyai justifikasi akademik sah',

        // D3: Evidens Reka Bentuk Akademik (8 items)
        'd3_1_purpose' => 'D3.1 Pernyataan tujuan, kumpulan sasaran dan prasyarat SAMC',
        'd3_2_outcomes' => 'D3.2 Hasil pembelajaran yang jelas, boleh dinilai dan sesuai tahap MQF dicadangkan',
        'd3_3_mapping' => 'D3.3 Pemetaan hasil pembelajaran kepada kandungan/topik',
        'd3_4_structure' => 'D3.4 Struktur kandungan dan urutan pembelajaran',
        'd3_5_activities' => 'D3.5 Aktiviti pembelajaran yang menyokong pencapaian hasil pembelajaran',
        'd3_6_slt_calc' => 'D3.6 Pengiraan SLT / notional learning time (pembelajaran dan pentaksiran)',
        'd3_7_credit_just' => 'D3.7 Justifikasi cadangan nilai kredit berdasarkan jumlah pembelajaran dan ketetapan QV',
        'd3_8_mqf_just' => 'D3.8 Justifikasi cadangan tahap MQF berdasarkan kompleksiti LO, kandungan dan pentaksiran',

        // D4: Evidens Pentaksiran dan Pencapaian (9 items)
        'd4_1_assessment_plan' => 'D4.1 Pelan pentaksiran yang memetakan setiap pentaksiran kepada hasil pembelajaran',
        'd4_2_instruments' => 'D4.2 Instrumen pentaksiran yang telah digunakan bagi kohort evidens',
        'd4_3_rubrics' => 'D4.3 Rubrik, skema pemarkahan atau kriteria penilaian',
        'd4_4_weights' => 'D4.4 Pemberatan pentaksiran dan standard lulus/penyempurnaan',
        'd4_5_samples' => 'D4.5 Sampel evidens kerja peserta yang sesuai dan boleh disahkan',
        'd4_6_results' => 'D4.6 Rekod keputusan / markah kohort yang lengkap',
        'd4_7_analysis' => 'D4.7 Analisis pencapaian hasil pembelajaran atau analisis prestasi yang berkaitan',
        'd4_8_integrity' => 'D4.8 Mekanisme pengesahan identiti dan integriti pentaksiran mengikut tahap risiko',
        'd4_9_verification' => 'D4.9 Proses pengesahan keputusan sebelum credential dianugerahkan',

        // D5: Evidens Pelaksanaan dan Sokongan Peserta (7 items)
        'd5_1_pre_info' => 'D5.1 Maklumat penawaran yang diberikan kepada peserta sebelum pendaftaran',
        'd5_2_materials' => 'D5.2 Bahan pembelajaran yang digunakan dalam kohort evidens',
        'd5_3_platform' => 'D5.3 Bukti platform / mod penyampaian dan akses peserta',
        'd5_4_engagement' => 'D5.4 Rekod penglibatan atau penyertaan peserta yang relevan',
        'd5_5_support' => 'D5.5 Mekanisme sokongan akademik dan teknikal',
        'd5_6_feedback' => 'D5.6 Maklum balas peserta dan/atau pihak berkepentingan',
        'd5_7_issues_cqi' => 'D5.7 Rekod isu pelaksanaan serta tindakan pembetulan atau CQI yang telah dibuat',

        // D6: Evidens Jaminan Kualiti dan CQI (9 items)
        'd6_1_pre_review' => 'D6.1 Rekod semakan akademik / teknikal sebelum penawaran',
        'd6_2_monitoring' => 'D6.2 Rekod pemantauan pelaksanaan kohort',
        'd6_3_lo_analysis' => 'D6.3 Analisis pencapaian hasil pembelajaran',
        'd6_4_feedback_analysis' => 'D6.4 Analisis maklum balas peserta / pihak berkepentingan',
        'd6_5_gaps_identified' => 'D6.5 Isu atau jurang kualiti yang dikenal pasti',
        'd6_6_cqi_plan' => 'D6.6 Pelan tindakan CQI dan pemilik tindakan',
        'd6_7_cqi_evidence' => 'D6.7 Bukti pelaksanaan tindakan CQI, jika telah selesai',
        'd6_8_effectiveness' => 'D6.8 Penilaian keberkesanan tindakan CQI, jika berkenaan',
        'd6_9_version_control' => 'D6.9 Kawalan versi bagi bahan, pentaksiran dan dokumen SAMC',

        // D7: Evidens Digital Credential (9 items)
        'd7_1_format' => 'D7.1 Reka bentuk / format Digital Credential tersedia',
        'd7_2_identity' => 'D7.2 Nama credential dan identiti penyedia dinyatakan',
        'd7_3_metadata_lo' => 'D7.3 Hasil pembelajaran dinyatakan atau boleh diakses melalui metadata credential',
        'd7_4_slt_stated' => 'D7.4 Learning time / SLT dinyatakan',
        'd7_5_credit_mqf_claim' => 'D7.5 Nilai kredit dan tahap MQF hanya dipaparkan berdasarkan status yang telah disahkan',
        'd7_6_qv_info' => 'D7.6 Maklumat QV dimasukkan selepas QV diperoleh dan mengikut keperluan QVC/MQA',
        'd7_7_standards' => 'D7.7 Hasil / standard pentaksiran yang berkaitan boleh dikenal pasti',
        'd7_8_verification_mech' => 'D7.8 Credential mempunyai mekanisme verifikasi atau pengesahan kesahihan',
        'd7_9_audit_trail' => 'D7.9 Proses pembetulan, penggantian atau pembatalan credential mempunyai audit trail',
    ],

    // Grouped sections for detailed UI and Print Views
    'sections' => [
        'D2' => [
            'code' => 'D2',
            'title' => 'Kelayakan Asas SAMC untuk QV',
            'title_en' => 'Basic SAMC Eligibility for QV',
            'items' => [
                'd2_1_standalone' => 'SAMC dibangunkan sebagai penawaran berdiri sendiri dan bukan komponen rasmi kursus/program UTeM yang diakreditasi.',
                'd2_2_slt_min' => 'Jumlah pembelajaran sekurang-kurangnya 40 jam Student Learning Time (SLT) / notional learning time.',
                'd2_3_registered_provider' => 'Penyedia merupakan entiti berdaftar dan diberi kuasa untuk menawarkan SAMC.',
                'd2_4_completed_cohort' => 'Sekurang-kurangnya satu (1) kohort peserta telah lengkap sebelum permohonan QV dikemukakan.',
                'd2_5_cohort_evidence' => 'Bukti penyertaan, pentaksiran dan pencapaian kohort lengkap tersedia.',
                'd2_6_credit_justification' => 'Cadangan kredit dan tahap MQF mempunyai justifikasi akademik dan tidak dipersembahkan sebagai disahkan sebelum QV.',
            ],
        ],
        'D3' => [
            'code' => 'D3',
            'title' => 'Evidens Reka Bentuk Akademik',
            'title_en' => 'Academic Design Evidence',
            'items' => [
                'd3_1_purpose' => 'Pernyataan tujuan, kumpulan sasaran dan prasyarat SAMC.',
                'd3_2_outcomes' => 'Hasil pembelajaran yang jelas, boleh dinilai dan sesuai dengan tahap pembelajaran yang dicadangkan.',
                'd3_3_mapping' => 'Pemetaan hasil pembelajaran kepada kandungan/topik.',
                'd3_4_structure' => 'Struktur kandungan dan urutan pembelajaran.',
                'd3_5_activities' => 'Aktiviti pembelajaran yang menyokong pencapaian hasil pembelajaran.',
                'd3_6_slt_calc' => 'Pengiraan SLT / notional learning time yang menunjukkan komponen pembelajaran dan pentaksiran.',
                'd3_7_credit_just' => 'Justifikasi cadangan nilai kredit berdasarkan jumlah pembelajaran dan ketetapan QV yang berkuat kuasa.',
                'd3_8_mqf_just' => 'Justifikasi cadangan tahap MQF berdasarkan kompleksiti hasil pembelajaran, kandungan dan pentaksiran.',
            ],
        ],
        'D4' => [
            'code' => 'D4',
            'title' => 'Evidens Pentaksiran dan Pencapaian',
            'title_en' => 'Assessment and Attainment Evidence',
            'items' => [
                'd4_1_assessment_plan' => 'Pelan pentaksiran yang memetakan setiap pentaksiran kepada hasil pembelajaran.',
                'd4_2_instruments' => 'Instrumen pentaksiran yang telah digunakan bagi kohort evidens.',
                'd4_3_rubrics' => 'Rubrik, skema pemarkahan atau kriteria penilaian.',
                'd4_4_weights' => 'Pemberatan pentaksiran dan standard lulus/penyempurnaan.',
                'd4_5_samples' => 'Sampel evidens kerja peserta yang sesuai dan boleh disahkan.',
                'd4_6_results' => 'Rekod keputusan/markah kohort yang lengkap.',
                'd4_7_analysis' => 'Analisis pencapaian hasil pembelajaran atau analisis prestasi yang berkaitan.',
                'd4_8_integrity' => 'Mekanisme pengesahan identiti dan integriti pentaksiran mengikut tahap risiko.',
                'd4_9_verification' => 'Proses pengesahan keputusan sebelum credential dianugerahkan.',
            ],
        ],
        'D5' => [
            'code' => 'D5',
            'title' => 'Evidens Pelaksanaan dan Sokongan Peserta',
            'title_en' => 'Delivery and Participant Support Evidence',
            'items' => [
                'd5_1_pre_info' => 'Maklumat penawaran yang diberikan kepada peserta sebelum pendaftaran.',
                'd5_2_materials' => 'Bahan pembelajaran yang digunakan dalam kohort evidens.',
                'd5_3_platform' => 'Bukti platform/mod penyampaian dan akses peserta.',
                'd5_4_engagement' => 'Rekod penglibatan atau penyertaan peserta yang relevan.',
                'd5_5_support' => 'Mekanisme sokongan akademik dan teknikal.',
                'd5_6_feedback' => 'Maklum balas peserta dan/atau pihak berkepentingan.',
                'd5_7_issues_cqi' => 'Rekod isu pelaksanaan serta tindakan pembetulan atau CQI yang telah dibuat.',
            ],
        ],
        'D6' => [
            'code' => 'D6',
            'title' => 'Evidens Jaminan Kualiti dan CQI',
            'title_en' => 'Quality Assurance and CQI Evidence',
            'items' => [
                'd6_1_pre_review' => 'Rekod semakan akademik/teknikal sebelum penawaran.',
                'd6_2_monitoring' => 'Rekod pemantauan pelaksanaan kohort.',
                'd6_3_lo_analysis' => 'Analisis pencapaian hasil pembelajaran.',
                'd6_4_feedback_analysis' => 'Analisis maklum balas peserta/pihak berkepentingan.',
                'd6_5_gaps_identified' => 'Isu atau jurang kualiti yang dikenal pasti.',
                'd6_6_cqi_plan' => 'Pelan tindakan CQI dan pemilik tindakan.',
                'd6_7_cqi_evidence' => 'Bukti pelaksanaan tindakan CQI, jika telah selesai.',
                'd6_8_effectiveness' => 'Penilaian keberkesanan tindakan CQI, jika berkenaan.',
                'd6_9_version_control' => 'Kawalan versi bagi bahan, pentaksiran dan dokumen SAMC.',
            ],
        ],
        'D7' => [
            'code' => 'D7',
            'title' => 'Evidens Digital Credential',
            'title_en' => 'Digital Credential Evidence',
            'items' => [
                'd7_1_format' => 'Reka bentuk/format Digital Credential tersedia.',
                'd7_2_identity' => 'Nama credential dan identiti penyedia dinyatakan.',
                'd7_3_metadata_lo' => 'Hasil pembelajaran dinyatakan atau boleh diakses melalui metadata/rekod credential.',
                'd7_4_slt_stated' => 'Learning time/SLT dinyatakan.',
                'd7_5_credit_mqf_claim' => 'Nilai kredit dan tahap MQF hanya dipaparkan berdasarkan status yang telah disahkan.',
                'd7_6_qv_info' => 'Maklumat QV dimasukkan selepas QV diperoleh dan mengikut keperluan QVC/MQA.',
                'd7_7_standards' => 'Hasil/standard pentaksiran yang berkaitan boleh dikenal pasti.',
                'd7_8_verification_mech' => 'Credential mempunyai mekanisme verifikasi atau pengesahan kesahihan.',
                'd7_9_audit_trail' => 'Proses pembetulan, penggantian atau pembatalan credential mempunyai audit trail.',
            ],
        ],
    ],

    // Appendix D8: 9 Material Change Review Components
    'components' => [
        'outcomes' => 'Hasil pembelajaran (Learning outcomes)',
        'slt' => 'SLT / nilai kredit (SLT and credits)',
        'mqf_level' => 'Tahap MQF (MQF level)',
        'core_content' => 'Kandungan teras (Core content)',
        'assessment' => 'Pentaksiran utama / standard lulus (Key assessment and passing standards)',
        'delivery' => 'Mod penyampaian (Delivery mode)',
        'provider_partner' => 'Penyedia / rakan kerjasama (Provider and partners)',
        'integrity_identity' => 'Pengesahan identiti / integriti pentaksiran (Identity verification and integrity)',
        'credential' => 'Digital Credential / maklumat QV (Credential and QV metadata)',
        'quality' => 'Quality assurance and CQI records',
    ],

    'review_actions' => [
        'none' => 'Tiada perubahan (No change)',
        'internal' => 'Semakan dalaman (Internal review)',
        'refer' => 'Rujuk QVC/MQA (Refer to QVC/MQA)',
        'na' => 'Tidak Berkaitan (TB)',
    ],

    // Appendix D9: Readiness Decision
    'readiness' => [
        'ready' => 'Sedia untuk dikemukakan bagi QV (Ready to submit for QV)',
        'ready_minor' => 'Sedia dengan pembetulan kecil (Ready with minor corrections)',
        'not_ready' => 'Belum sedia (Not ready)',
    ],

    // Appendix D10: 3 Sign-Off Roles
    'd10_roles' => [
        'coordinator' => [
            'code' => 'P1',
            'title' => 'Penyelaras SAMC (SAMC Coordinator)',
        ],
        'faculty_rep' => [
            'code' => 'P2',
            'title' => 'Wakil PTj / Fakulti (PTj / Faculty Representative)',
        ],
        'qa_authority' => [
            'code' => 'P4',
            'title' => 'Pihak Jaminan Kualiti / Urus Setia berkaitan (QA Party / Secretariat)',
        ],
    ],

    'status' => [
        'preparing' => 'Preparing',
        'ready' => 'Ready to submit',
        'submitted' => 'Submitted',
        'verified' => 'Quality-Verified',
        'expired' => 'Expired',
        'suspended' => 'Suspended',
        'withdrawn' => 'Withdrawn',
        'superseded' => 'Superseded',
    ],
];
