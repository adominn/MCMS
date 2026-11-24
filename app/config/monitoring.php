<?php

// Guide Appendix C: Template Jaminan Kualiti, Pemantauan dan Penambahbaikan Kualiti Berterusan (CQI)
return [
    // Appendix C5: 8 Implementation Quality Domains
    'domains' => [
        'lo_alignment' => 'Content alignment with learning outcomes',
        'slt_adequacy' => 'SLT adequacy and learning workload',
        'materials_quality' => 'Quality of learning materials and activities',
        'assessment_validity' => 'Assessment validity and reliability',
        'integrity_identity' => 'Assessment integrity and identity verification',
        'platform_support' => 'Platform, access and participant support',
        'record_accuracy' => 'Record accuracy and credential issuance',
        'qv_status' => 'QV, credit, and MQF status if applicable',
    ],

    'domain_statuses' => [
        'memuaskan' => 'Satisfactory',
        'perlu_tindakan' => 'Needs action',
        'tb' => 'Not applicable',
    ],

    // Appendix C4: 4 Feedback & Stakeholder Sources
    'feedback_sources' => [
        'learners' => [
            'label' => 'Learners',
            'aspect' => 'Content, activities, assessment, platform, support',
        ],
        'instructors' => [
            'label' => 'Instructors / Facilitators',
            'aspect' => 'Design and delivery',
        ],
        'industry' => [
            'label' => 'Industry / Employers / Professionals',
            'aspect' => 'Relevance and competency',
        ],
        'system_data' => [
            'label' => 'System data / operational records',
            'aspect' => 'Access, participation, completion',
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
        'high' => 'High',
        'medium' => 'Medium',
        'low' => 'Low',
    ],

    'action_status' => [
        'not_started' => 'Not started',
        'in_progress' => 'In progress',
        'done' => 'Done (effectiveness pending)',
        'closed' => 'Closed',
        'carried' => 'Carried to next cycle',
    ],

    'effectiveness' => [
        'effective' => 'Effective',
        'partly' => 'Partly effective',
        'not_effective' => 'Not effective',
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
        'continue_no_change' => 'Continue without significant change',
        'continue_minor_cqi' => 'Continue with minor CQI',
        'content_review_needed' => 'Content/assessment review needed',
        'reapproval_needed' => 'Review and re-approval needed',
        'consider_suspension' => 'Consider suspension or termination',
    ],

    // Appendix C9: 8 Affected Components
    'c9_affected_components' => [
        'learning_outcomes' => 'Learning Outcomes',
        'slt_credits' => 'SLT / Credits',
        'mqf_qv_level' => 'MQF / QV Level',
        'core_content' => 'Core Content',
        'key_assessment' => 'Key Assessment',
        'delivery_mode' => 'Delivery Mode',
        'credentials' => 'Credential',
        'provider_partners' => 'Providers / Partners',
    ],

    // Appendix C10: 3 Sign-Off Roles
    'c10_roles' => [
        'coordinator' => [
            'code' => 'P1',
            'title' => 'MC Coordinator',
        ],
        'ptj_rep' => [
            'code' => 'P2',
            'title' => 'PTj / Faculty Representative',
        ],
        'qa_authority' => [
            'code' => 'P4',
            'title' => 'QA / University Authority',
        ],
    ],

    'transition' => [
        'completion_window' => 'Current participants finish within a completion window',
        'new_version' => 'Participants move to a new version',
        'equivalence' => 'Earlier achievement gets an equivalence decision',
    ],
];
