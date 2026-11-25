<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Idempotent demo seeder for end-to-end testing of:
 *   1. SAMC Creation -> Review -> Endorse -> CODL Verify -> Senate Approval -> Quality Verification (QV)
 *      ("Applied Industrial IoT & Edge Analytics", code MC-FTMK-IOT, status: draft under Dr. Aina Zulkifli)
 *   2. SAMC Cohort CQI (Continuous Quality Improvement)
 *      ("Cloud Native Development 2026/2-CQI", status: ended, 4 participants, 3 feedback responses, ready to open CQI)
 */
final class SamcLifecycleDemoSeeder extends Seeder
{
    public function run(): void
    {
        $ainaId = (int) (DB::table('users')->where('email', 'aina.zulkifli@utem.example.test')->value('id') ?? 6);
        $ftmkId = (int) (DB::table('ptjs')->where('code', 'FTMK')->value('id') ?? 1);

        // =========================================================================
        // 1. Ready-to-Submit SAMC Draft Proposal: Applied Industrial IoT & Edge Analytics
        // =========================================================================
        $mc = DB::table('micro_credentials')->where('code', 'MC-FTMK-IOT')->first();
        if (! $mc) {
            $mcId = DB::table('micro_credentials')->insertGetId([
                'code' => 'MC-FTMK-IOT',
                'category' => 'samc',
                'category_status' => 'proposed',
                'origin' => 'own',
                'qv_planned' => 1,
                'ptj_id' => $ftmkId,
                'owner_id' => $ainaId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $mcId = (int) $mc->id;
        }

        $ver = DB::table('mc_versions')->where('micro_credential_id', $mcId)->where('version_no', 1)->first();
        if (! $ver) {
            $verId = DB::table('mc_versions')->insertGetId([
                'micro_credential_id' => $mcId,
                'version_no' => 1,
                'proposal_type' => 'new',
                'status' => 'draft',
                'name_en' => 'Applied Industrial IoT & Edge Analytics',
                'name_ms' => 'IoT Perindustrian Gunaan & Analitik Pinggir',
                'discipline' => 'Computing',
                'target_group' => 'Manufacturing engineers, embedded developers, and final-year engineering/computing learners',
                'delivery_mode' => 'blended',
                'delivery_language' => 'English',
                'offering_period' => '8 weeks',
                'need' => 'Strong regional Industry 4.0 demand across Melaka and southern corridor electronics plants for practical MQTT telemetry and edge anomaly detection skills.',
                'category_justification' => 'Stand-alone industry-driven competency unit not unbundled from an existing UTeM parent course; designed for MQA Quality Verification (QV) to carry 2 academic credits at MQF Level 6.',
                'existing_equivalents' => 'No direct UTeM parent course covers end-to-end MQTT edge gateway deployment with live industrial anomaly detection.',
                'recognition_potential' => 'Eligible for ABC deposit upon Quality Verification (QV) and potential elective credit transfer into FTMK MQF 6 computing programmes.',
                'follow_up_action' => null,
                'evidence_types' => json_encode(['industry', 'standard', 'technology']),
                'added_value' => 'Provides hands-on industrial telemetry and edge inference workflows aligned with Industry 4.0 plant requirements.',
                'planned_slt' => 80,
                'credit_sought' => 1,
                'parent_course_id' => null,
                'credit_model' => 'topic_based',
                'assessment_standard' => 'Competency-based practical gateway lab (50%) and edge anomaly detection capstone project (50%); minimum 50% required in each component.',
                'integrity_mechanism' => 'Authenticated Git repository commits, live hardware/simulator telemetry logs, and oral viva walkthrough for capstone submission.',
                'feedback_method' => 'Structured rubric scoring with individualized written engineering feedback within 7 working days.',
                'credential_types' => json_encode(['certificate', 'badge']),
                'achievement_level' => 'module',
                'qv_status' => 'planned',
                'verification_method' => 'Digital credential verification portal with cryptographic signature and unique public verification code.',
                'proposed_credit' => 2.0,
                'proposed_mqf_level' => 6,
                'ip_confirmed' => 1,
                'ip_note' => 'All lab manuals, datasets, and MQTT sandbox scripts are original UTeM FTMK instructional materials.',
                'updated_by' => $ainaId,
                'change_class' => 'initial',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $verId = (int) $ver->id;
        }

        if (DB::table('mc_modules')->where('mc_version_id', $verId)->count() === 0) {
            $m1 = DB::table('mc_modules')->insertGetId([
                'mc_version_id' => $verId,
                'seq' => 1,
                'name_en' => 'Industrial Sensor Networks & MQTT Telemetry',
                'name_ms' => 'Rangkaian Penderia Industri & Telemetri MQTT',
                'key_content' => 'Industrial sensor interfacing, Modbus/MQTT broker architecture, payload serialization, TLS security, and time-series ingestion.',
                'slt_hours' => 40,
                'is_assessment' => 0,
                'separately_recognisable' => 1,
            ]);
            $m2 = DB::table('mc_modules')->insertGetId([
                'mc_version_id' => $verId,
                'seq' => 2,
                'name_en' => 'Edge Analytics & Predictive Anomaly Detection',
                'name_ms' => 'Analitik Pinggir & Pengesanan Anomali Ramalan',
                'key_content' => 'Streaming window aggregation, lightweight edge ML models, threshold alerting, and fault diagnosis dashboards.',
                'slt_hours' => 40,
                'is_assessment' => 0,
                'separately_recognisable' => 1,
            ]);

            DB::table('assessment_components')->insert([
                [
                    'mc_version_id' => $verId,
                    'mc_module_id' => $m1,
                    'name_en' => 'MQTT Telemetry Gateway Practical Lab',
                    'weight_percent' => 50.0,
                    'csv_key' => 'mqtt_lab',
                    'max_mark' => 100.00,
                    'pass_mark' => 50.00,
                ],
                [
                    'mc_version_id' => $verId,
                    'mc_module_id' => $m2,
                    'name_en' => 'Edge Anomaly Detection Capstone Project',
                    'weight_percent' => 50.0,
                    'csv_key' => 'edge_capstone',
                    'max_mark' => 100.00,
                    'pass_mark' => 50.00,
                ],
            ]);
        }

        if (DB::table('learning_outcomes')->where('mc_version_id', $verId)->count() === 0) {
            DB::table('learning_outcomes')->insert([
                [
                    'mc_version_id' => $verId,
                    'code' => 'LO1',
                    'text_en' => 'Configure secure MQTT sensor telemetry pipelines between industrial edge nodes and time-series brokers.',
                    'text_ms' => 'Mengkonfigurasi saluran telemetri penderia MQTT yang selamat antara nod pinggir industri dan broker siri masa.',
                    'sort_order' => 1,
                    'parent_clo_id' => null,
                ],
                [
                    'mc_version_id' => $verId,
                    'code' => 'LO2',
                    'text_en' => 'Implement lightweight edge analytics models to detect equipment anomalies in real-time telemetry streams.',
                    'text_ms' => 'Melaksanakan model analitik pinggir ringan bagi mengesan anomali peralatan dalam aliran telemetri masa nyata.',
                    'sort_order' => 2,
                    'parent_clo_id' => null,
                ],
                [
                    'mc_version_id' => $verId,
                    'code' => 'LO3',
                    'text_en' => 'Evaluate edge deployment reliability, latency, and security trade-offs for a manufacturing use case.',
                    'text_ms' => 'Menilai kebolehpercayaan, kependaman dan keselamatan pelaksanaan pinggir bagi kes guna pembuatan.',
                    'sort_order' => 3,
                    'parent_clo_id' => null,
                ],
            ]);
        }

        // =========================================================================
        // 2. Ready-for-CQI Completed SAMC Cohort: Cloud Native Development 2026/2-CQI
        // =========================================================================
        $cndVerId = (int) (DB::table('mc_versions')->where('name_en', 'Cloud Native Development')->value('id') ?? 2);
        $cqiOff = DB::table('offerings')->where('mc_version_id', $cndVerId)->where('cohort_name', '2026/2-CQI')->first();
        if (! $cqiOff) {
            $cqiOffId = DB::table('offerings')->insertGetId([
                'mc_version_id' => $cndVerId,
                'cohort_name' => '2026/2-CQI',
                'start_date' => '2026-08-01',
                'end_date' => '2026-11-15',
                'reg_opens_on' => '2026-07-01',
                'reg_closes_on' => '2026-07-31',
                'capacity' => 30,
                'fee' => 250.00,
                'delivery_mode' => 'online',
                'platform_ref' => 'uLearn-CND-2026-2CQI',
                'status' => 'ended',
                'readiness_state' => 'confirmed',
                'results_submitted_at' => '2026-11-16 10:00:00',
                'created_by' => $ainaId,
                'created_at' => '2026-07-01 09:00:00',
                'updated_at' => '2026-11-17 14:00:00',
            ]);
        } else {
            $cqiOffId = (int) $cqiOff->id;
            DB::table('offerings')->where('id', $cqiOffId)->update([
                'status' => 'ended',
                'readiness_state' => 'confirmed',
                'results_submitted_at' => '2026-11-16 10:00:00',
            ]);
        }

        $comps = DB::table('assessment_components')->where('mc_version_id', $cndVerId)->orderBy('id')->get();
        $learners = [
            ['email' => 'amir.hakimi@learner.example.test', 'ref' => 'ENR-2026-CQI-01', 'status' => 'completed', 'marks' => [88, 92], 'comment' => 'Great hands-on Docker labs. Kubernetes networking lab could use an extra troubleshooting walkthrough.'],
            ['email' => 'bee.tan@learner.example.test',     'ref' => 'ENR-2026-CQI-02', 'status' => 'completed', 'marks' => [78, 84], 'comment' => 'Very relevant to industry work. Sandbox provisioning was slightly slow during week 5 peak hours.'],
            ['email' => 'cee.lim@learner.example.test',     'ref' => 'ENR-2026-CQI-03', 'status' => 'completed', 'marks' => [70, 74], 'comment' => 'Clear rubric and helpful lecturer feedback. Adding a short Helm chart cheat-sheet would help beginners.'],
            ['email' => 'dan.ong@learner.example.test',     'ref' => 'ENR-2026-CQI-04', 'status' => 'enrolled',  'marks' => [44, 42], 'comment' => null],
        ];

        $hakimId = (int) (DB::table('users')->where('email', 'hakim.rahman@utem.example.test')->value('id') ?? 10);

        foreach ($learners as $idx => $l) {
            $uid = DB::table('users')->where('email', $l['email'])->value('id');
            if (! $uid) {
                continue;
            }
            $enrol = DB::table('enrolments')->where('offering_id', $cqiOffId)->where('user_id', $uid)->first();
            if (! $enrol) {
                $eid = DB::table('enrolments')->insertGetId([
                    'reference' => $l['ref'],
                    'offering_id' => $cqiOffId,
                    'mc_version_id' => $cndVerId,
                    'user_id' => $uid,
                    'status' => $l['status'],
                    'progress_percent' => $l['status'] === 'completed' ? 100 : 65,
                    'modules_completed' => $l['status'] === 'completed' ? 2 : 1,
                    'fee_amount' => 250.00,
                    'ack_text' => 'I understand that completing a micro-credential is not automatic credit transfer or programme admission.',
                    'ack_versions' => json_encode(['en' => 1, 'ms' => 1]),
                    'status_changed_at' => '2026-11-17 14:00:00',
                    'created_at' => '2026-07-15 10:00:00',
                    'updated_at' => '2026-11-17 14:00:00',
                ]);
            } else {
                $eid = (int) $enrol->id;
            }

            foreach ($comps as $cIdx => $comp) {
                if (! isset($l['marks'][$cIdx])) {
                    continue;
                }
                if (! DB::table('assessment_results')->where('enrolment_id', $eid)->where('assessment_component_id', $comp->id)->exists()) {
                    DB::table('assessment_results')->insert([
                        'enrolment_id' => $eid,
                        'assessment_component_id' => $comp->id,
                        'score' => $l['marks'][$cIdx],
                        'status' => 'verified',
                        'source' => 'manual',
                        'entered_by' => $ainaId,
                        'verified_by' => $hakimId,
                        'verified_at' => '2026-11-17 14:00:00',
                        'created_at' => '2026-11-16 09:30:00',
                        'updated_at' => '2026-11-17 14:00:00',
                    ]);
                }
            }

            if ($l['comment'] !== null && ! DB::table('feedback_responses')->where('enrolment_id', $eid)->exists()) {
                DB::table('feedback_responses')->insert([
                    'offering_id' => $cqiOffId,
                    'enrolment_id' => $eid,
                    'answers' => json_encode([
                        'ease' => 4, 'admin' => 4, 'depth' => 5, 'clarity' => 4, 'content' => 5,
                        'support' => 5, 'fairness' => 5, 'lecturer' => 5, 'platform' => $idx === 1 ? 3 : 4,
                        'practice' => 5, 'workload' => 4, 'recommend' => 5, 'relevance' => 5,
                        'activities' => 4, 'assessment' => 5, 'reliability' => $idx === 1 ? 3 : 4, 'feedback_quality' => 5,
                    ]),
                    'comment' => $l['comment'],
                    'submitted_at' => '2026-11-18 11:00:00',
                ]);
            }
        }
    }
}
