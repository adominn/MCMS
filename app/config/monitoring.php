<?php

// Guide Appendix C: Template Jaminan Kualiti, Pemantauan dan Penambahbaikan Kualiti Berterusan (CQI)
return [
    // Appendix C5: 8 Implementation Quality Domains
    'domains' => [
        'lo_alignment' => 'Keselarasan kandungan dengan hasil pembelajaran (Content alignment with learning outcomes)',
        'slt_adequacy' => 'Kecukupan SLT dan beban pembelajaran (SLT adequacy and learning workload)',
        'materials_quality' => 'Kualiti bahan dan aktiviti pembelajaran (Quality of learning materials and activities)',
        'assessment_validity' => 'Kesahan dan kebolehpercayaan pentaksiran (Assessment validity and reliability)',
        'integrity_identity' => 'Integriti pentaksiran / pengesahan identiti (Assessment integrity and identity verification)',
        'platform_support' => 'Platform, akses dan sokongan peserta (Platform, access and participant support)',
        'record_accuracy' => 'Ketepatan rekod dan pengeluaran credential (Record accuracy and credential issuance)',
        'qv_status' => 'Status QV / kredit / MQF jika berkenaan (QV, credit, and MQF status if applicable)',
    ],

    'domain_statuses' => [
        'memuaskan' => 'Memuaskan (Satisfactory)',
        'perlu_tindakan' => 'Perlu Tindakan (Needs Action)',
        'tb' => 'Tidak Berkaitan (Not Applicable)',
    ],

    // Appendix C4: 4 Feedback & Stakeholder Sources
    'feedback_sources' => [
        'learners' => [
            'label' => 'Peserta (Learners)',
            'aspect' => 'Kandungan, aktiviti, pentaksiran, platform, sokongan',
        ],
        'instructors' => [
            'label' => 'Pengajar / Fasilitator (Instructors / Facilitators)',
            'aspect' => 'Reka bentuk dan pelaksanaan',
        ],
        'industry' => [
            'label' => 'Industri / Majikan / Profesional (Industry / Employers / Professionals)',
            'aspect' => 'Relevansi dan kompetensi',
        ],
        'system_data' => [
            'label' => 'Data sistem / rekod operasi (System data / operational records)',
            'aspect' => 'Akses, penyertaan, penyempurnaan',
        ],
    ],

    'feedback_areas' => [
        'content' => 'Content',
        'activities' => 'Learning activities',
        'assessment' => 'Assessment',
        'platform' => 'Platform',
        'support' => 'Support',
        'relevance' => 'Relevance',
    ],

    // The feedback form groups what a participant can rate (1 to 5)
    'feedback_groups' => [
        'Content' => [
            'content' => 'Content overall',
            'clarity' => 'Clarity of the learning outcomes',
            'depth' => 'Depth and level of the content',
        ],
        'Learning activities' => [
            'activities' => 'Learning activities overall',
            'practice' => 'Practice, labs and examples',
        ],
        'Assessment' => [
            'assessment' => 'Assessment overall',
            'fairness' => 'Fairness of the assessment',
            'feedback_quality' => 'Feedback on your work',
        ],
        'Platform' => [
            'platform' => 'Platform overall',
            'ease' => 'Ease of finding and using the materials',
            'reliability' => 'Reliability of the platform',
        ],
        'Support' => [
            'support' => 'Support overall',
            'lecturer' => 'Lecturer responsiveness',
            'admin' => 'CODL and administration',
        ],
        'Relevance' => [
            'relevance' => 'Relevance overall',
            'workload' => 'Workload for the time given',
            'recommend' => 'You would recommend it to others',
        ],
    ],

    'priority' => [
        'high' => 'High (Tinggi)',
        'medium' => 'Medium (Sederhana)',
        'low' => 'Low (Rendah)',
    ],

    'action_status' => [
        'not_started' => 'Not started (Belum)',
        'in_progress' => 'In progress (Proses)',
        'done' => 'Done (Selesai - effectiveness pending)',
        'closed' => 'Closed (Ditutup)',
        'carried' => 'Carried to the next cycle (Dibawa ke kitaran seterusnya)',
    ],

    'effectiveness' => [
        'effective' => 'Effective (Berkesan)',
        'partly' => 'Partly effective (Sebahagian)',
        'not_effective' => 'Not effective (Tidak Berkesan)',
    ],

    'confirmations' => [
        'prepared' => 'The record was prepared from the evidence of this cohort',
        'evidence' => 'The evidence for each issue and action is attached or referenced',
        'owners' => 'Every action has an owner, a due date and a success indicator',
    ],

    'review_outcome' => [
        'continue' => 'Continue without significant change',
        'minor_cqi' => 'Continue with minor CQI',
        'content_review' => 'Content or assessment review needed',
        'reapproval' => 'Review and re-approval needed',
        'suspension' => 'Consider suspension or termination',
    ],

    // Appendix C9: 5 Review Decisions
    'c9_decisions' => [
        'continue_no_change' => 'Teruskan tanpa perubahan signifikan (Continue without significant change)',
        'continue_minor_cqi' => 'Teruskan dengan CQI kecil (Continue with minor CQI)',
        'content_review_needed' => 'Semakan kandungan/pentaksiran diperlukan (Content/assessment review needed)',
        'reapproval_needed' => 'Perlu semakan/kelulusan semula (Review and re-approval needed)',
        'consider_suspension' => 'Pertimbang penggantungan/penamatan (Consider suspension or termination)',
    ],

    // Appendix C9: 8 Affected Components
    'c9_affected_components' => [
        'learning_outcomes' => 'Hasil Pembelajaran (Learning Outcomes)',
        'slt_credits' => 'SLT / Kredit (SLT / Credits)',
        'mqf_qv_level' => 'Tahap MQF / QV (MQF / QV Level)',
        'core_content' => 'Kandungan Teras (Core Content)',
        'key_assessment' => 'Pentaksiran Utama (Key Assessment)',
        'delivery_mode' => 'Mod Penyampaian (Delivery Mode)',
        'credentials' => 'Credential',
        'provider_partners' => 'Penyedia / Rakan Kerjasama (Providers / Partners)',
    ],

    // Appendix C10: 3 Sign-Off Roles
    'c10_roles' => [
        'coordinator' => [
            'code' => 'P1',
            'title' => 'Penyelaras Micro-Credential (MC Coordinator)',
        ],
        'ptj_rep' => [
            'code' => 'P2',
            'title' => 'Wakil PTj / Fakulti (PTj / Faculty Representative)',
        ],
        'qa_authority' => [
            'code' => 'P4',
            'title' => 'Pihak Jaminan Kualiti / Berkuasa jika berkenaan (QA / University Authority)',
        ],
    ],

    'transition' => [
        'completion_window' => 'Current participants finish within a completion window',
        'new_version' => 'Participants move to a new version',
        'equivalence' => 'Earlier achievement gets an equivalence decision',
    ],
];
