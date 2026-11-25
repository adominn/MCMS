<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Curriculum\ProposalChecker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds 3 complete, logical, zero-blocking-error DRAFT proposals (1 UMC, 1 SAMC, 1 MCE)
 * under Dr. Aina Zulkifli so each category can be reviewed, printed, and submitted manually.
 */
class ThreeCategoryDraftProposalsSeeder extends Seeder
{
    public function run(): void
    {
        $aina = DB::table('users')->where('email', 'aina.zulkifli@utem.example.test')->first();
        if (! $aina) {
            $this->command?->error('Dr. Aina Zulkifli (aina.zulkifli@utem.example.test) not found.');
            return;
        }

        $ptjId = (int) (DB::table('role_assignments')
            ->where('user_id', $aina->id)
            ->where('role', 'lecturer')
            ->value('ptj_id') ?: 1);

        $parentDbCourse = DB::table('parent_courses as c')
            ->leftJoin('programmes as p', 'p.id', '=', 'c.programme_id')
            ->where('c.code', 'BITS 1125')
            ->where('p.accreditation_status', 'accredited')
            ->first(['c.id', 'c.slt_hours']);

        if (! $parentDbCourse) {
            $parentDbCourse = DB::table('parent_courses as c')
                ->leftJoin('programmes as p', 'p.id', '=', 'c.programme_id')
                ->where('p.accreditation_status', 'accredited')
                ->first(['c.id', 'c.slt_hours']);
        }

        $specs = [
            // 1. UMC Draft (120h SLT, Credit-Based, Linked to Accredited BITS 1125 Databases)
            [
                'category' => 'umc',
                'origin' => 'unbundled',
                'qv_planned' => false,
                'parent_course_id' => $parentDbCourse ? (int) $parentDbCourse->id : null,
                'credit_model' => 'credit_based',
                'name_en' => 'Relational Database Engineering & SQL Optimization',
                'name_ms' => 'Kejuruteraan Pangkalan Data Hubungan & Pengoptimuman SQL',
                'discipline' => 'Computer Science & Database Systems',
                'target_group' => 'Undergraduate computing students, junior backend developers, and data analysts seeking modular UTeM credit.',
                'delivery_mode' => 'blended',
                'delivery_language' => 'English / Bahasa Melayu',
                'offering_period' => '12 weeks (1 semester equivalent)',
                'planned_slt' => 120,
                'credit_sought' => true,
                'need' => 'Working technologists and diploma-upgrade learners frequently need accredited relational database design and SQL tuning competencies without enrolling in a full-time semester course.',
                'category_justification' => 'Unbundled directly from the existing UTeM course BITS 1125 Databases (120h SLT) into three 40-hour modules to preserve full curriculum alignment with the parent course.',
                'added_value' => 'Provides hands-on PostgreSQL/MySQL query plan analysis, indexing labs, and structured modular assessments aligned with BITS 1125 learning outcomes.',
                'evidence_types' => ['programme', 'industry'],
                'evidence_docs' => [
                    'programme' => ['BITS1125_Unbundling_Mapping_Minutes.pdf'],
                    'industry' => ['FTMK_Employer_Database_Skills_Survey_2026.pdf'],
                ],
                'assessment_standard' => 'Minimum 50% overall weighted score and at least 50% on each practical component to confirm competency.',
                'integrity_mechanism' => 'Authenticated UTeM portal login, individualized database schemas per learner, and live query-plan walkthrough verification.',
                'feedback_method' => 'Structured rubric feedback and annotated SQL query reviews returned within 7 working days.',
                'credential_types' => ['badge', 'certificate', 'digital_credential'],
                'achievement_level' => 'whole',
                'verification_method' => 'Public UTeM MCMS verification portal with Ed25519 cryptographic QR proof.',
                'qv_status' => 'na',
                'proposed_credit' => 3.0,
                'proposed_mqf_level' => 6,
                'los' => [
                    ['code' => 'LO1', 'parent_clo_id' => 1, 'text_en' => 'Design normalized relational database schemas (up to 3NF) that satisfy enterprise integrity constraints.', 'text_ms' => 'Mereka bentuk skema pangkalan data hubungan ternormal (sehingga 3NF) yang memenuhi kekangan integriti perusahaan.'],
                    ['code' => 'LO2', 'parent_clo_id' => 2, 'text_en' => 'Construct complex analytical SQL queries, views, and indexing strategies to retrieve operational datasets efficiently.', 'text_ms' => 'Membina pertanyaan SQL analitik kompleks, paparan, dan strategi pengindeksan untuk mendapatkan set data operasi secara cekap.'],
                    ['code' => 'LO3', 'parent_clo_id' => 3, 'text_en' => 'Evaluate transaction isolation levels, concurrency locks, and query execution plans for high-throughput database workloads.', 'text_ms' => 'Menilai tahap pengasingan transaksi, kunci keserentakan, dan pelan pelaksanaan pertanyaan bagi beban kerja pangkalan data berdaya pemprosesan tinggi.'],
                ],
                'modules' => [
                    [
                        'seq' => 1, 'slt_hours' => 40, 'is_assessment' => false,
                        'name_en' => 'Relational Modeling & Normalization',
                        'name_ms' => 'Pemodelan Hubungan & Penormalan',
                        'key_content' => 'Entity-Relationship modeling, functional dependencies, 1NF to 3NF/BCNF normalization, referential integrity constraints.',
                        'activities' => [
                            ['name_en' => 'ER Modeling & Normalization Lecture', 'activity_type' => 'lecture', 'slt_hours' => 10, 'lo_idx' => 0],
                            ['name_en' => 'Enterprise Schema Design Lab', 'activity_type' => 'practical', 'slt_hours' => 16, 'lo_idx' => 0],
                            ['name_en' => 'Guided Schema Case Study & Reading', 'activity_type' => 'self_learning', 'slt_hours' => 14, 'lo_idx' => 0],
                        ],
                    ],
                    [
                        'seq' => 2, 'slt_hours' => 40, 'is_assessment' => false,
                        'name_en' => 'Advanced SQL Querying & Indexing',
                        'name_ms' => 'Pertanyaan SQL Lanjutan & Pengindeksan',
                        'key_content' => 'Multi-table joins, window functions, CTEs, B-Tree and composite indexes, query plan inspection.',
                        'activities' => [
                            ['name_en' => 'Analytical SQL & Indexing Seminar', 'activity_type' => 'lecture', 'slt_hours' => 10, 'lo_idx' => 1],
                            ['name_en' => 'SQL Query Optimization Lab', 'activity_type' => 'practical', 'slt_hours' => 18, 'lo_idx' => 1],
                            ['name_en' => 'Self-Paced SQL Problem Sets', 'activity_type' => 'self_learning', 'slt_hours' => 12, 'lo_idx' => 1],
                        ],
                    ],
                    [
                        'seq' => 3, 'slt_hours' => 40, 'is_assessment' => false,
                        'name_en' => 'Transactions, Concurrency & Performance Tuning',
                        'name_ms' => 'Transaksi, Keserentakan & Penalaan Prestasi',
                        'key_content' => 'ACID properties, isolation levels, deadlock diagnosis, EXPLAIN ANALYZE profiling, backup and recovery.',
                        'activities' => [
                            ['name_en' => 'Concurrency Control & Recovery Lecture', 'activity_type' => 'lecture', 'slt_hours' => 10, 'lo_idx' => 2],
                            ['name_en' => 'Deadlock & Execution Plan Tuning Lab', 'activity_type' => 'practical', 'slt_hours' => 16, 'lo_idx' => 2],
                            ['name_en' => 'Database Performance Audit Capstone', 'activity_type' => 'project', 'slt_hours' => 14, 'lo_idx' => 2],
                        ],
                    ],
                ],
                'assessments' => [
                    ['name_en' => 'Schema Design & Normalization Lab', 'mod_seq' => 1, 'weight' => 30, 'csv_key' => 'schema_lab', 'max' => 100, 'pass' => 50, 'lo_idxs' => [0]],
                    ['name_en' => 'SQL Query & Indexing Practical Test', 'mod_seq' => 2, 'weight' => 35, 'csv_key' => 'sql_practical', 'max' => 100, 'pass' => 50, 'lo_idxs' => [1]],
                    ['name_en' => 'Transaction & Query Tuning Capstone', 'mod_seq' => 3, 'weight' => 35, 'csv_key' => 'tuning_capstone', 'max' => 100, 'pass' => 50, 'lo_idxs' => [0, 1, 2]],
                ],
                'readiness' => [
                    'materials' => 'Lecture slides, SQL lab datasets, and normalization rubrics finalized on uLearn.',
                    'platform' => 'UTeM uLearn LMS and sandboxed MySQL/PostgreSQL lab containers provisioned.',
                    'assessment' => '3 assessment components totaling 100% with rubrics and individualized datasets ready.',
                    'facilitators' => 'Dr. Aina Zulkifli',
                    'participant_info' => 'Title, target group, delivery mode, and 12-week schedule verified in proposal.',
                    'records' => 'MCMS automated badge, certificate, ACR, and public verification configured.',
                    'accessibility' => 'Captioned video walkthroughs, screen-reader friendly PDFs, and flexible lab hours.',
                ],
            ],

            // 2. SAMC Draft (80h SLT, 2 credits, MQF 6, QV Planned)
            [
                'category' => 'samc',
                'origin' => 'own',
                'qv_planned' => true,
                'parent_course_id' => null,
                'credit_model' => null,
                'name_en' => 'Generative AI Integration for Software Engineers',
                'name_ms' => 'Integrasi AI Generatif untuk Jurutera Perisian',
                'discipline' => 'Software Engineering & Applied AI',
                'target_group' => 'Software engineers, full-stack developers, and final-year computing graduates building production AI applications.',
                'delivery_mode' => 'online',
                'delivery_language' => 'English',
                'offering_period' => '8 weeks (part-time online)',
                'planned_slt' => 80,
                'credit_sought' => true,
                'need' => 'Malaysian software teams rapidly adopting LLM APIs lack structured engineering training on retrieval-augmented generation, deterministic evaluation harnesses, and privacy guardrails.',
                'category_justification' => 'Stand-alone 80-hour industry-driven micro-credential designed independently of existing UTeM degree courses to address applied generative AI software engineering competencies.',
                'added_value' => 'Bridges the gap between prototype prompting and production-grade AI software engineering with automated evaluation pipelines and safety audits.',
                'evidence_types' => ['industry', 'technology'],
                'evidence_docs' => [
                    'industry' => ['Industry_AI_Engineering_Demand_Brief_2026.pdf'],
                    'technology' => ['LLM_Production_Engineering_Standards_2026.pdf'],
                ],
                'assessment_standard' => 'Minimum 50% overall score and verified passing score on the capstone safety & evaluation audit.',
                'integrity_mechanism' => 'Authenticated git commit history, live oral code walkthrough, and automated plagiarism/test-suite verification.',
                'feedback_method' => 'Written rubric feedback on pull requests and evaluation reports within 7 working days.',
                'credential_types' => ['badge', 'certificate', 'digital_credential'],
                'achievement_level' => 'whole',
                'verification_method' => 'Public UTeM MCMS verification portal with Ed25519 cryptographic QR proof.',
                'qv_status' => 'will_apply',
                'proposed_credit' => 2.0,
                'proposed_mqf_level' => 6,
                'los' => [
                    ['code' => 'LO1', 'parent_clo_id' => null, 'text_en' => 'Design structured prompt templates and retrieval-augmented generation (RAG) pipelines for domain-specific knowledge bases.', 'text_ms' => 'Mereka bentuk templat prom berstruktur dan saluran penjanaan diperkaya dapatan semula (RAG) bagi pangkalan pengetahuan khusus domain.'],
                    ['code' => 'LO2', 'parent_clo_id' => null, 'text_en' => 'Implement reliable LLM API integrations featuring JSON schema validation, automated evaluation suites, and graceful fallback controls.', 'text_ms' => 'Melaksanakan integrasi API LLM yang boleh dipercayai dengan pengesahan skema JSON, suit penilaian automatik, dan kawalan sandaran.'],
                    ['code' => 'LO3', 'parent_clo_id' => null, 'text_en' => 'Evaluate data privacy, hallucination mitigation, latency, and inference cost governance in production AI software services.', 'text_ms' => 'Menilai privasi data, pengurangan halusinasi, kependaman, dan tadbir urus kos inferens dalam perkhidmatan perisian AI produksi.'],
                ],
                'modules' => [
                    [
                        'seq' => 1, 'slt_hours' => 40, 'is_assessment' => false,
                        'name_en' => 'Prompt Architecture & Retrieval-Augmented Generation',
                        'name_ms' => 'Seni Bina Prom & Penjanaan Diperkaya Dapatan Semula',
                        'key_content' => 'System prompt design, chunking strategies, embeddings, vector retrieval, grounding and citation patterns.',
                        'activities' => [
                            ['name_en' => 'Prompt Engineering & RAG Architecture Session', 'activity_type' => 'e_learning', 'slt_hours' => 10, 'lo_idx' => 0],
                            ['name_en' => 'Vector Indexing & Grounded Retrieval Lab', 'activity_type' => 'practical', 'slt_hours' => 18, 'lo_idx' => 0],
                            ['name_en' => 'Architecture Patterns Self-Study', 'activity_type' => 'self_learning', 'slt_hours' => 12, 'lo_idx' => 0],
                        ],
                    ],
                    [
                        'seq' => 2, 'slt_hours' => 40, 'is_assessment' => false,
                        'name_en' => 'Production LLM Engineering, Evaluation & Safety',
                        'name_ms' => 'Kejuruteraan LLM Produksi, Penilaian & Keselamatan',
                        'key_content' => 'Structured outputs, regression eval harnesses, PII redaction, prompt-injection defense, latency/cost observability.',
                        'activities' => [
                            ['name_en' => 'Structured Output & Eval Harness Workshop', 'activity_type' => 'e_learning', 'slt_hours' => 8, 'lo_idx' => 1],
                            ['name_en' => 'Automated Regression Eval & Guardrails Lab', 'activity_type' => 'practical', 'slt_hours' => 16, 'lo_idx' => 1],
                            ['name_en' => 'Production AI Service Capstone & Safety Audit', 'activity_type' => 'project', 'slt_hours' => 16, 'lo_idx' => 2],
                        ],
                    ],
                ],
                'assessments' => [
                    ['name_en' => 'RAG Pipeline Implementation Lab', 'mod_seq' => 1, 'weight' => 30, 'csv_key' => 'rag_lab', 'max' => 100, 'pass' => 50, 'lo_idxs' => [0]],
                    ['name_en' => 'Structured Output & Eval Suite', 'mod_seq' => 2, 'weight' => 30, 'csv_key' => 'eval_suite', 'max' => 100, 'pass' => 50, 'lo_idxs' => [1]],
                    ['name_en' => 'Production AI Service Capstone & Safety Audit', 'mod_seq' => null, 'weight' => 40, 'csv_key' => 'ai_capstone', 'max' => 100, 'pass' => 50, 'lo_idxs' => [0, 1, 2]],
                ],
                'readiness' => [
                    'materials' => 'Reference starter repositories, RAG datasets, and evaluation rubrics published on uLearn.',
                    'platform' => 'UTeM uLearn LMS and API sandbox keys configured for cohort learners.',
                    'assessment' => '3 assessment components totaling 100% with automated test rubrics and oral review guide.',
                    'facilitators' => 'Dr. Aina Zulkifli',
                    'participant_info' => 'Title, target group, online delivery mode, and 8-week schedule verified.',
                    'records' => 'MCMS credential issuance and QV evidence tracking configured.',
                    'accessibility' => 'Asynchronous recordings with transcripts and accessible code documentation.',
                ],
            ],

            // 3. MCE Draft (24h SLT, Non-credit, no MQF, Executive Short Learning)
            [
                'category' => 'mce',
                'origin' => 'own',
                'qv_planned' => false,
                'parent_course_id' => null,
                'credit_model' => null,
                'name_en' => 'Executive Briefing: Cybersecurity Hygiene & Incident Readiness',
                'name_ms' => 'Taklimat Eksekutif: Kebersihan Keselamatan Siber & Kesediaan Insiden',
                'discipline' => 'Cybersecurity & Organizational Governance',
                'target_group' => 'SME managers, administrative officers, and non-technical staff responsible for workplace digital security.',
                'delivery_mode' => 'face_to_face',
                'delivery_language' => 'English / Bahasa Melayu',
                'offering_period' => '3 weeks (3 weekend workshops)',
                'planned_slt' => 24,
                'credit_sought' => false,
                'need' => 'Small and medium enterprises face rising phishing and ransomware incidents caused by everyday operational gaps rather than complex exploits, requiring concise practical training.',
                'category_justification' => 'Focused 24-hour executive short learning programme (under 40h SLT) designed for immediate workplace readiness without academic credit or MQF level.',
                'added_value' => 'Combines real Malaysian phishing case studies with a hands-on tabletop incident escalation drill participants can immediately deploy at work.',
                'evidence_types' => ['industry'],
                'evidence_docs' => [
                    'industry' => ['SME_Cyber_Hygiene_Training_Need_2026.pdf'],
                ],
                'assessment_standard' => 'Minimum 50% on both the threat scenario quiz and the tabletop incident response exercise.',
                'integrity_mechanism' => 'In-person facilitator-observed tabletop exercise and authenticated individual scenario quiz.',
                'feedback_method' => 'Immediate debriefing checklist feedback during workshop plus written summary within 5 working days.',
                'credential_types' => ['badge', 'certificate'],
                'achievement_level' => 'whole',
                'verification_method' => 'Public UTeM MCMS verification portal with QR verification code.',
                'qv_status' => 'na',
                'proposed_credit' => null,
                'proposed_mqf_level' => null,
                'los' => [
                    ['code' => 'LO1', 'parent_clo_id' => null, 'text_en' => 'Identify common enterprise phishing, credential theft, and ransomware attack vectors across hybrid workplaces.', 'text_ms' => 'Mengenal pasti vektor serangan pancingan data, kecurian kelayakan, dan perisian tebusan yang lazim di tempat kerja hibrid.'],
                    ['code' => 'LO2', 'parent_clo_id' => null, 'text_en' => 'Execute a structured first-response containment and incident escalation checklist during a simulated security breach.', 'text_ms' => 'Melaksanakan senarai semak pembendungan tindak balas awal dan eskalasi insiden berstruktur semasa simulasi pelanggaran keselamatan.'],
                ],
                'modules' => [
                    [
                        'seq' => 1, 'slt_hours' => 12, 'is_assessment' => false,
                        'name_en' => 'Threat Landscape & Practical Cyber Hygiene',
                        'name_ms' => 'Landskap Ancaman & Kebersihan Siber Praktikal',
                        'key_content' => 'Phishing indicators, MFA enforcement, password managers, endpoint patching, and safe file sharing.',
                        'activities' => [
                            ['name_en' => 'Workplace Threat Vectors Briefing', 'activity_type' => 'lecture', 'slt_hours' => 4, 'lo_idx' => 0],
                            ['name_en' => 'Phishing & Social Engineering Case Analysis', 'activity_type' => 'tutorial', 'slt_hours' => 4, 'lo_idx' => 0],
                            ['name_en' => 'Cyber Hygiene Self-Audit Checklist', 'activity_type' => 'self_learning', 'slt_hours' => 4, 'lo_idx' => 0],
                        ],
                    ],
                    [
                        'seq' => 2, 'slt_hours' => 12, 'is_assessment' => false,
                        'name_en' => 'Incident Triage & Tabletop Escalation Drill',
                        'name_ms' => 'Triaj Insiden & Latih Tubi Eskalasi Meja',
                        'key_content' => 'First-hour containment steps, NACSA/MyCERT reporting channels, stakeholder communication, and recovery checklists.',
                        'activities' => [
                            ['name_en' => 'Simulated Breach Triage Workshop', 'activity_type' => 'practical', 'slt_hours' => 6, 'lo_idx' => 1],
                            ['name_en' => 'Organizational Incident Playbook Exercise', 'activity_type' => 'project', 'slt_hours' => 6, 'lo_idx' => 1],
                        ],
                    ],
                ],
                'assessments' => [
                    ['name_en' => 'Threat Identification Scenario Quiz', 'mod_seq' => 1, 'weight' => 40, 'csv_key' => 'threat_quiz', 'max' => 100, 'pass' => 50, 'lo_idxs' => [0]],
                    ['name_en' => 'Incident Response Tabletop Exercise', 'mod_seq' => 2, 'weight' => 60, 'csv_key' => 'tabletop_drill', 'max' => 100, 'pass' => 50, 'lo_idxs' => [0, 1]],
                ],
                'readiness' => [
                    'materials' => 'Executive briefing slides, phishing sample cards, and incident response templates ready.',
                    'platform' => 'FTMK executive seminar room and uLearn companion resource folder configured.',
                    'assessment' => '2 assessment components totaling 100% with tabletop observation rubric finalized.',
                    'facilitators' => 'Dr. Aina Zulkifli',
                    'participant_info' => 'Title, target group, face-to-face schedule, and non-credit MCE notice clear.',
                    'records' => 'MCMS non-credit badge and certificate issuance configured.',
                    'accessibility' => 'Ground-floor accessible seminar venue and printed large-font checklists available.',
                ],
            ],
        ];

        $checker = app(ProposalChecker::class);

        foreach ($specs as $spec) {
            $existing = DB::table('mc_versions as v')
                ->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')
                ->where('m.owner_id', $aina->id)
                ->where('v.name_en', $spec['name_en'])
                ->first(['v.id as vid', 'm.id as mcid']);

            if ($existing) {
                $vid = (int) $existing->vid;
                $mcId = (int) $existing->mcid;
                $this->command?->info("Already exists: [{$spec['category']}] {$spec['name_en']} (MC#{$mcId}, v#{$vid})");
                continue;
            }

            $vid = DB::transaction(function () use ($spec, $aina, $ptjId) {
                $now = now();
                $mcId = DB::table('micro_credentials')->insertGetId([
                    'category' => $spec['category'],
                    'category_status' => 'proposed',
                    'origin' => $spec['origin'],
                    'qv_planned' => $spec['qv_planned'],
                    'ptj_id' => $ptjId,
                    'owner_id' => $aina->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $vid = DB::table('mc_versions')->insertGetId([
                    'micro_credential_id' => $mcId,
                    'version_no' => 1,
                    'proposal_type' => 'new',
                    'status' => 'draft',
                    'name_en' => $spec['name_en'],
                    'name_ms' => $spec['name_ms'],
                    'discipline' => $spec['discipline'],
                    'target_group' => $spec['target_group'],
                    'delivery_mode' => $spec['delivery_mode'],
                    'delivery_language' => $spec['delivery_language'],
                    'offering_period' => $spec['offering_period'],
                    'planned_slt' => $spec['planned_slt'],
                    'credit_sought' => $spec['credit_sought'],
                    'parent_course_id' => $spec['parent_course_id'],
                    'credit_model' => $spec['credit_model'],
                    'need' => $spec['need'],
                    'category_justification' => $spec['category_justification'],
                    'added_value' => $spec['added_value'],
                    'evidence_types' => json_encode($spec['evidence_types']),
                    'evidence_files' => json_encode($spec['evidence_docs']),
                    'assessment_standard' => $spec['assessment_standard'],
                    'integrity_mechanism' => $spec['integrity_mechanism'],
                    'feedback_method' => $spec['feedback_method'],
                    'credential_types' => json_encode($spec['credential_types']),
                    'achievement_level' => $spec['achievement_level'],
                    'verification_method' => $spec['verification_method'],
                    'qv_status' => $spec['qv_status'],
                    'proposed_credit' => $spec['proposed_credit'],
                    'proposed_mqf_level' => $spec['proposed_mqf_level'],
                    'ip_confirmed' => true,
                    'ip_note' => 'All curriculum materials, labs, and assessment rubrics are original UTeM works.',
                    'updated_by' => $aina->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                // Learning outcomes
                $loIds = [];
                foreach ($spec['los'] as $idx => $lo) {
                    $loIds[$idx] = DB::table('learning_outcomes')->insertGetId([
                        'mc_version_id' => $vid,
                        'code' => $lo['code'],
                        'text_en' => $lo['text_en'],
                        'text_ms' => $lo['text_ms'],
                        'parent_clo_id' => $lo['parent_clo_id'],
                        'sort_order' => $idx + 1,
                    ]);
                }

                // Modules + Activities
                $modIdsBySeq = [];
                foreach ($spec['modules'] as $mod) {
                    $mid = DB::table('mc_modules')->insertGetId([
                        'mc_version_id' => $vid,
                        'seq' => $mod['seq'],
                        'name_en' => $mod['name_en'],
                        'name_ms' => $mod['name_ms'],
                        'key_content' => $mod['key_content'],
                        'slt_hours' => $mod['slt_hours'],
                        'is_assessment' => $mod['is_assessment'],
                    ]);
                    $modIdsBySeq[$mod['seq']] = $mid;

                    foreach ($mod['activities'] as $act) {
                        DB::table('mc_module_activities')->insert([
                            'mc_module_id' => $mid,
                            'name_en' => $act['name_en'],
                            'activity_type' => $act['activity_type'],
                            'slt_hours' => $act['slt_hours'],
                            'learning_outcome_id' => $loIds[$act['lo_idx']] ?? null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }

                // Assessments + Outcome alignment
                foreach ($spec['assessments'] as $comp) {
                    $cid = DB::table('assessment_components')->insertGetId([
                        'mc_version_id' => $vid,
                        'mc_module_id' => $comp['mod_seq'] ? ($modIdsBySeq[$comp['mod_seq']] ?? null) : null,
                        'name_en' => $comp['name_en'],
                        'weight_percent' => $comp['weight'],
                        'csv_key' => $comp['csv_key'],
                        'max_mark' => $comp['max'],
                        'pass_mark' => $comp['pass'],
                    ]);

                    foreach ($comp['lo_idxs'] as $lIdx) {
                        if (isset($loIds[$lIdx])) {
                            DB::table('assessment_component_outcomes')->insert([
                                'assessment_component_id' => $cid,
                                'learning_outcome_id' => $loIds[$lIdx],
                            ]);
                        }
                    }
                }

                // Readiness items (all 7 ready)
                foreach ($spec['readiness'] as $rKey => $rNote) {
                    DB::table('proposal_readiness')->insert([
                        'mc_version_id' => $vid,
                        'item' => $rKey,
                        'status' => 'yes',
                        'note' => $rNote,
                    ]);
                }

                return $vid;
            });

            // Write valid minimal PDF files on disk so evidence download/print works cleanly
            $evDir = storage_path('app/proposals/'.$vid.'/evidence');
            if (! is_dir($evDir)) {
                mkdir($evDir, 0755, true);
            }
            foreach ($spec['evidence_docs'] as $evType => $files) {
                foreach ($files as $fname) {
                    $full = $evDir.DIRECTORY_SEPARATOR.$fname;
                    if (! file_exists($full)) {
                        file_put_contents($full, $this->buildMinimalPdf(strtoupper($spec['category']).' Supporting Evidence: '.$fname));
                    }
                }
            }

            $chk = $checker->check($vid);
            $this->command?->info(sprintf(
                'Seeded [%s] v#%d "%s" | SLT: %dh | Blocking: %d | Warnings: %d',
                strtoupper($spec['category']),
                $vid,
                $spec['name_en'],
                $chk['slt'],
                count($chk['blocking']),
                count($chk['warnings'])
            ));
            foreach ($chk['blocking'] as $b) {
                $this->command?->warn('  BLOCK: ['.$b['tab'].'] '.$b['msg']);
            }
            foreach ($chk['warnings'] as $w) {
                $this->command?->warn('  WARN: ['.$w['tab'].'] '.$w['msg']);
            }
        }
    }

    private function buildMinimalPdf(string $title): string
    {
        $safe = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], substr($title, 0, 90));
        $stream = "BT /F1 12 Tf 50 750 Td ({$safe}) Tj ET";
        $len = strlen($stream);

        return "%PDF-1.4\n"
            ."1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n"
            ."2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n"
            ."3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >> endobj\n"
            ."4 0 obj << /Length {$len} >> stream\n{$stream}\nendstream endobj\n"
            ."5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n"
            ."xref\n0 6\n0000000000 65535 f \n0000000009 00000 n \n0000000058 00000 n \n0000000115 00000 n \n0000000241 00000 n \n0000000350 00000 n \n"
            ."trailer << /Size 6 /Root 1 0 R >>\nstartxref\n420\n%%EOF\n";
    }
}
