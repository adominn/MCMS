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

        // Basic SAMC Eligibility for QV (6 items)
        'd2_1_standalone' => 'SAMC developed as a stand-alone offering (not an official UTeM course)',
        'd2_2_slt_min' => 'Total learning time is at least 40 hours SLT / notional learning time',
        'd2_3_registered_provider' => 'Provider is a registered and authorised entity to offer SAMC',
        'd2_4_completed_cohort' => 'At least one (1) participant cohort has completed before application',
        'd2_5_cohort_evidence' => 'Complete cohort participation, assessment, and attainment evidence is available',
        'd2_6_credit_justification' => 'Proposed credits and MQF level have valid academic justification',

        // Academic Design Evidence (8 items)
        'd3_1_purpose' => 'Statement of SAMC purpose, target group, and prerequisites',
        'd3_2_outcomes' => 'Clear, measurable learning outcomes appropriate to the proposed MQF level',
        'd3_3_mapping' => 'Mapping of learning outcomes to content and topics',
        'd3_4_structure' => 'Content structure and learning sequence',
        'd3_5_activities' => 'Learning activities supporting achievement of learning outcomes',
        'd3_6_slt_calc' => 'SLT / notional learning time calculation (learning and assessment)',
        'd3_7_credit_just' => 'Justification of proposed credit value based on total learning time and QV requirements',
        'd3_8_mqf_just' => 'Justification of proposed MQF level based on complexity of LO, content, and assessment',

        // Assessment and Attainment Evidence (9 items)
        'd4_1_assessment_plan' => 'Assessment plan mapping each assessment to learning outcomes',
        'd4_2_instruments' => 'Assessment instruments used for the evidence cohort',
        'd4_3_rubrics' => 'Rubrics, marking schemes, or evaluation criteria',
        'd4_4_weights' => 'Assessment weightings and passing / completion standards',
        'd4_5_samples' => 'Appropriate and verifiable samples of participant work evidence',
        'd4_6_results' => 'Complete cohort results / marks records',
        'd4_7_analysis' => 'Learning outcome attainment analysis or relevant performance analysis',
        'd4_8_integrity' => 'Identity verification and assessment integrity mechanism by risk level',
        'd4_9_verification' => 'Results verification process prior to credential award',

        // Delivery and Participant Support Evidence (7 items)
        'd5_1_pre_info' => 'Offering information provided to participants prior to registration',
        'd5_2_materials' => 'Learning materials used in the evidence cohort',
        'd5_3_platform' => 'Evidence of delivery platform / mode and participant access',
        'd5_4_engagement' => 'Relevant participant engagement or participation records',
        'd5_5_support' => 'Academic and technical support mechanisms',
        'd5_6_feedback' => 'Participant and/or stakeholder feedback',
        'd5_7_issues_cqi' => 'Delivery issue records and corrective or CQI actions taken',

        // Quality Assurance and CQI Evidence (9 items)
        'd6_1_pre_review' => 'Academic / technical review records prior to offering',
        'd6_2_monitoring' => 'Cohort delivery monitoring records',
        'd6_3_lo_analysis' => 'Learning outcome attainment analysis',
        'd6_4_feedback_analysis' => 'Participant / stakeholder feedback analysis',
        'd6_5_gaps_identified' => 'Identified quality issues or gaps',
        'd6_6_cqi_plan' => 'CQI action plan and assigned action owners',
        'd6_7_cqi_evidence' => 'Evidence of CQI action implementation, if completed',
        'd6_8_effectiveness' => 'Evaluation of CQI action effectiveness, where applicable',
        'd6_9_version_control' => 'Version control for SAMC materials, assessments, and documents',

        // Digital Credential Evidence (9 items)
        'd7_1_format' => 'Digital Credential design / format available',
        'd7_2_identity' => 'Credential title and provider identity stated',
        'd7_3_metadata_lo' => 'Learning outcomes stated or accessible via credential metadata',
        'd7_4_slt_stated' => 'Learning time / SLT stated',
        'd7_5_credit_mqf_claim' => 'Credit value and MQF level displayed only based on verified status',
        'd7_6_qv_info' => 'QV details included after QV approval per QVC/MQA requirements',
        'd7_7_standards' => 'Relevant assessment outcomes / standards identifiable',
        'd7_8_verification_mech' => 'Credential has an authenticity verification or validation mechanism',
        'd7_9_audit_trail' => 'Credential correction, replacement, or revocation process has an audit trail',
    ],

    // Grouped sections for detailed UI and Print Views
    'sections' => [
        'D2' => [
            'code' => '1',
            'title' => 'Basic SAMC Eligibility for QV',
            'title_en' => 'Basic SAMC Eligibility for QV',
            'items' => [
                'd2_1_standalone' => 'SAMC is developed as a stand-alone offering and is not an official component of an accredited UTeM course or programme.',
                'd2_2_slt_min' => 'Total learning is at least 40 hours of Student Learning Time (SLT) / notional learning time.',
                'd2_3_registered_provider' => 'The provider is a registered entity authorised to offer SAMC.',
                'd2_4_completed_cohort' => 'At least one (1) participant cohort has completed before the QV application is submitted.',
                'd2_5_cohort_evidence' => 'Complete cohort participation, assessment, and attainment evidence is available.',
                'd2_6_credit_justification' => 'Proposed credits and MQF level have academic justification and are not presented as verified prior to QV.',
            ],
        ],
        'D3' => [
            'code' => '2',
            'title' => 'Academic Design Evidence',
            'title_en' => 'Academic Design Evidence',
            'items' => [
                'd3_1_purpose' => 'Statement of SAMC purpose, target group, and prerequisites.',
                'd3_2_outcomes' => 'Clear, measurable learning outcomes appropriate to the proposed learning level.',
                'd3_3_mapping' => 'Mapping of learning outcomes to content and topics.',
                'd3_4_structure' => 'Content structure and learning sequence.',
                'd3_5_activities' => 'Learning activities supporting achievement of learning outcomes.',
                'd3_6_slt_calc' => 'SLT / notional learning time calculation showing learning and assessment components.',
                'd3_7_credit_just' => 'Justification of proposed credit value based on total learning time and current QV requirements.',
                'd3_8_mqf_just' => 'Justification of proposed MQF level based on complexity of learning outcomes, content, and assessment.',
            ],
        ],
        'D4' => [
            'code' => '3',
            'title' => 'Assessment and Attainment Evidence',
            'title_en' => 'Assessment and Attainment Evidence',
            'items' => [
                'd4_1_assessment_plan' => 'Assessment plan mapping each assessment to learning outcomes.',
                'd4_2_instruments' => 'Assessment instruments used for the evidence cohort.',
                'd4_3_rubrics' => 'Rubrics, marking schemes, or evaluation criteria.',
                'd4_4_weights' => 'Assessment weightings and passing/completion standards.',
                'd4_5_samples' => 'Appropriate and verifiable samples of participant work evidence.',
                'd4_6_results' => 'Complete cohort results/marks records.',
                'd4_7_analysis' => 'Learning outcome attainment analysis or relevant performance analysis.',
                'd4_8_integrity' => 'Identity verification and assessment integrity mechanisms by risk level.',
                'd4_9_verification' => 'Results verification process prior to credential award.',
            ],
        ],
        'D5' => [
            'code' => '4',
            'title' => 'Delivery and Participant Support Evidence',
            'title_en' => 'Delivery and Participant Support Evidence',
            'items' => [
                'd5_1_pre_info' => 'Offering information provided to participants prior to registration.',
                'd5_2_materials' => 'Learning materials used in the evidence cohort.',
                'd5_3_platform' => 'Evidence of delivery platform/mode and participant access.',
                'd5_4_engagement' => 'Relevant participant engagement or participation records.',
                'd5_5_support' => 'Academic and technical support mechanisms.',
                'd5_6_feedback' => 'Participant and/or stakeholder feedback.',
                'd5_7_issues_cqi' => 'Delivery issue records and corrective or CQI actions taken.',
            ],
        ],
        'D6' => [
            'code' => '5',
            'title' => 'Quality Assurance and CQI Evidence',
            'title_en' => 'Quality Assurance and CQI Evidence',
            'items' => [
                'd6_1_pre_review' => 'Academic/technical review records prior to offering.',
                'd6_2_monitoring' => 'Cohort delivery monitoring records.',
                'd6_3_lo_analysis' => 'Learning outcome attainment analysis.',
                'd6_4_feedback_analysis' => 'Participant/stakeholder feedback analysis.',
                'd6_5_gaps_identified' => 'Identified quality issues or gaps.',
                'd6_6_cqi_plan' => 'CQI action plan and action owners.',
                'd6_7_cqi_evidence' => 'Evidence of CQI action implementation, if completed.',
                'd6_8_effectiveness' => 'Evaluation of CQI action effectiveness, where applicable.',
                'd6_9_version_control' => 'Version control for SAMC materials, assessments, and documents.',
            ],
        ],
        'D7' => [
            'code' => '6',
            'title' => 'Digital Credential Evidence',
            'title_en' => 'Digital Credential Evidence',
            'items' => [
                'd7_1_format' => 'Digital Credential design/format available.',
                'd7_2_identity' => 'Credential title and provider identity stated.',
                'd7_3_metadata_lo' => 'Learning outcomes stated or accessible via credential metadata/record.',
                'd7_4_slt_stated' => 'Learning time/SLT stated.',
                'd7_5_credit_mqf_claim' => 'Credit value and MQF level displayed only based on verified status.',
                'd7_6_qv_info' => 'QV details included after QV approval per QVC/MQA requirements.',
                'd7_7_standards' => 'Relevant assessment outcomes/standards identifiable.',
                'd7_8_verification_mech' => 'Credential has an authenticity verification or validation mechanism.',
                'd7_9_audit_trail' => 'Credential correction, replacement, or revocation process has an audit trail.',
            ],
        ],
    ],

    // Appendix D8: 9 Material Change Review Components
    'components' => [
        'outcomes' => 'Learning outcomes',
        'slt' => 'SLT and credits',
        'mqf_level' => 'MQF level',
        'core_content' => 'Core content',
        'assessment' => 'Key assessment and passing standards',
        'delivery' => 'Delivery mode',
        'provider_partner' => 'Provider and partners',
        'integrity_identity' => 'Identity verification and integrity',
        'credential' => 'Credential and QV metadata',
        'quality' => 'Quality assurance and CQI records',
    ],

    'review_actions' => [
        'none' => 'No change',
        'internal' => 'Internal review',
        'refer' => 'Refer to QVC/MQA',
        'na' => 'Not applicable',
    ],

    // Appendix D9: Readiness Decision
    'readiness' => [
        'ready' => 'Ready to submit for QV',
        'ready_minor' => 'Ready with minor corrections',
        'not_ready' => 'Not ready',
    ],

    // Appendix D10: 3 Sign-Off Roles
    'd10_roles' => [
        'coordinator' => [
            'code' => 'P1',
            'title' => 'SAMC Coordinator',
        ],
        'faculty_rep' => [
            'code' => 'P2',
            'title' => 'PTj / Faculty Representative',
        ],
        'qa_authority' => [
            'code' => 'P4',
            'title' => 'QA Party / Secretariat',
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
