<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds comprehensive reference data extracted from the UCreds Public Portal Audit v1.0:
 * - PTjs (Faculties & Centres: FTMK, FKE, FPTT, FKP, PSM, PBLI, CODL)
 * - Accredited Degree Programmes (MTDL, MBA, MECE, BITS)
 * - Core Parent Subject Courses (MAXL series, MBA series, MECE series, Computing, MPU)
 * - Senate-Approved Stackable Pathway Mappings (PW-MTDL-1, PW-MBA-1, PW-MECE-1)
 *
 * Usage: php artisan db:seed --class=UcredsReferenceSeeder
 */
class UcredsReferenceSeeder extends Seeder
{
    public function run(): void
    {
        // 1. PTJs (Strictly the 7 official UTeM faculties)
        $ptjs = [
            ['FTKEK', 'Fakulti Teknologi Dan Kejuruteraan Elektronik Dan Komputer'],
            ['FTKE',  'Fakulti Teknologi Dan Kejuruteraan Elektrik'],
            ['FTKM',  'Fakulti Teknologi Dan Kejuruteraan Mekanikal'],
            ['FTKIP', 'Fakulti Teknologi Dan Kejuruteraan Industri Dan Pembuatan'],
            ['FTMK',  'Fakulti Teknologi Maklumat Dan Komunikasi'],
            ['FPTT',  'Fakulti Pengurusan Teknologi Dan Teknousahawanan'],
            ['FAIX',  'Fakulti Kecerdasan Buatan Dan Keselamatan Siber'],
        ];

        foreach ($ptjs as [$code, $name]) {
            DB::table('ptjs')->updateOrInsert(
                ['code' => $code],
                ['name' => $name, 'updated_at' => now()]
            );
        }

        $ftkekId = DB::table('ptjs')->where('code', 'FTKEK')->value('id');
        $ftkeId  = DB::table('ptjs')->where('code', 'FTKE')->value('id');
        $ftkmId  = DB::table('ptjs')->where('code', 'FTKM')->value('id');
        $ftkipId = DB::table('ptjs')->where('code', 'FTKIP')->value('id');
        $ftmkId  = DB::table('ptjs')->where('code', 'FTMK')->value('id');
        $fpttId  = DB::table('ptjs')->where('code', 'FPTT')->value('id');
        $faixId  = DB::table('ptjs')->where('code', 'FAIX')->value('id');

        // Reassign any historical references to obsolete PTJ codes before purging
        $oldMapping = [
            'FKE'  => $ftkeId,
            'FKM'  => $ftkmId,
            'FKP'  => $ftkipId,
            'PSM'  => $ftmkId,
            'PBLI' => $fpttId,
            'PBPI' => $fpttId,
            'PPI'  => $faixId,
            'CODL' => $ftmkId,
        ];
        foreach ($oldMapping as $oldCode => $targetId) {
            $oldId = DB::table('ptjs')->where('code', $oldCode)->value('id');
            if ($oldId && $targetId) {
                DB::table('programmes')->where('ptj_id', $oldId)->update(['ptj_id' => $targetId]);
                DB::table('micro_credentials')->where('ptj_id', $oldId)->update(['ptj_id' => $targetId]);
                DB::table('role_assignments')->where('ptj_id', $oldId)->update(['ptj_id' => $targetId]);
                DB::table('tasks')->where('ptj_id', $oldId)->update(['ptj_id' => $targetId]);
                DB::table('abc_batches')->where('ptj_id', $oldId)->update(['ptj_id' => $targetId]);
                DB::table('discrepancies')->where('ptj_id', $oldId)->update(['ptj_id' => $targetId]);
                DB::table('ptjs')->where('id', $oldId)->delete();
            }
        }
        DB::table('ptjs')->whereNotIn('code', ['FTKEK', 'FTKE', 'FTKM', 'FTKIP', 'FTMK', 'FPTT', 'FAIX'])->delete();

        // 2. Programmes (Undergraduate & ODL Postgraduate Degrees for all 7 faculties)
        $programmes = [
            [
                'code' => 'BITS',
                'name' => 'Bachelor of Computer Science',
                'mqf_level' => 6,
                'mode' => 'conventional',
                'accreditation_status' => 'accredited',
                'total_credits' => 120,
                'ptj_id' => $ftmkId,
                'transfer_limit_percent' => 50,
                'curriculum_version' => '2024',
                'intake' => '2027/1',
            ],
            [
                'code' => 'MTDL',
                'name' => 'Master of Technology in Data Science and Analytics',
                'mqf_level' => 7,
                'mode' => 'odl',
                'accreditation_status' => 'accredited',
                'total_credits' => 40,
                'ptj_id' => $faixId,
                'transfer_limit_percent' => 50,
                'curriculum_version' => '2025',
                'intake' => '2027/1',
            ],
            [
                'code' => 'MBA',
                'name' => 'Master of Business Administration',
                'mqf_level' => 7,
                'mode' => 'odl',
                'accreditation_status' => 'accredited',
                'total_credits' => 40,
                'ptj_id' => $fpttId,
                'transfer_limit_percent' => 50,
                'curriculum_version' => '2025',
                'intake' => '2027/1',
            ],
            [
                'code' => 'MECE',
                'name' => 'Master of Electronic Engineering (Computer Engineering)',
                'mqf_level' => 7,
                'mode' => 'odl',
                'accreditation_status' => 'accredited',
                'total_credits' => 40,
                'ptj_id' => $ftkekId,
                'transfer_limit_percent' => 50,
                'curriculum_version' => '2025',
                'intake' => '2027/1',
            ],
            [
                'code' => 'BENE',
                'name' => 'Bachelor of Electrical Engineering Technology with Honours',
                'mqf_level' => 6,
                'mode' => 'conventional',
                'accreditation_status' => 'accredited',
                'total_credits' => 130,
                'ptj_id' => $ftkeId,
                'transfer_limit_percent' => 30,
                'curriculum_version' => '2024',
                'intake' => '2027/1',
            ],
            [
                'code' => 'BMKG',
                'name' => 'Bachelor of Mechanical Engineering Technology with Honours',
                'mqf_level' => 6,
                'mode' => 'conventional',
                'accreditation_status' => 'accredited',
                'total_credits' => 135,
                'ptj_id' => $ftkmId,
                'transfer_limit_percent' => 30,
                'curriculum_version' => '2024',
                'intake' => '2027/1',
            ],
            [
                'code' => 'BMFG',
                'name' => 'Bachelor of Manufacturing Engineering Technology with Honours',
                'mqf_level' => 6,
                'mode' => 'conventional',
                'accreditation_status' => 'accredited',
                'total_credits' => 130,
                'ptj_id' => $ftkipId,
                'transfer_limit_percent' => 30,
                'curriculum_version' => '2024',
                'intake' => '2027/1',
            ],
        ];

        foreach ($programmes as $p) {
            DB::table('programmes')->updateOrInsert(
                ['code' => $p['code']],
                array_merge($p, ['updated_at' => now()])
            );
        }

        $mtdlId = DB::table('programmes')->where('code', 'MTDL')->value('id');
        $mbaId = DB::table('programmes')->where('code', 'MBA')->value('id');
        $meceId = DB::table('programmes')->where('code', 'MECE')->value('id');
        $bitsId = DB::table('programmes')->where('code', 'BITS')->value('id');
        $beneId = DB::table('programmes')->where('code', 'BENE')->value('id');
        $bmkgId = DB::table('programmes')->where('code', 'BMKG')->value('id');
        $bmfgId = DB::table('programmes')->where('code', 'BMFG')->value('id');

        // 3. Parent Courses (Standard 3-Credit, 120-Hour SLT Modules with CLOs)
        $parentCourses = [
            // Master of Technology in Data Science and Analytics (MTDL) - FAIX
            [$mtdlId, 'MAXL 5113', 'Fundamental of Data Science', 3, 120, 'Explain foundational data science concepts;Analyze raw data structures;Apply exploratory data analysis techniques;Interpret basic analytics models'],
            [$mtdlId, 'MAXL 5123', 'Big Data Management', 3, 120, 'Explain core big data architectures;Deploy distributed storage with Hadoop and NoSQL;Analyze large-scale data using Apache Spark;Evaluate data governance policies'],
            [$mtdlId, 'MAXL 5133', 'Applied Statistical Methods', 3, 120, 'Apply probability distributions;Formulate hypothesis tests;Conduct regression analysis;Interpret statistical significance in business contexts'],
            [$mtdlId, 'MAXL 5143', 'Applied Machine Learning', 3, 120, 'Explain supervised and unsupervised learning algorithms;Train and tune neural networks and deep learning models;Evaluate model accuracy and loss;Deploy predictive pipelines'],
            [$mtdlId, 'MAXL 5153', 'Big Data Analytics and Visualization', 3, 120, 'Design interactive visual dashboards;Communicate analytical findings;Process streaming data feeds;Select visualization paradigms'],
            [$mtdlId, 'MAXL 5163', 'Modelling and Decision Making', 3, 120, 'Formulate decision models;Solve optimization problems;Apply simulation techniques;Assess risk in complex scenarios'],
            [$mtdlId, 'MAXL 5273', 'Customer and Financial Analytics', 3, 120, 'Analyze customer churn and lifetime value;Apply financial risk modeling;Segment user behavior;Evaluate marketing campaign efficacy'],

            // Master of Business Administration (MBA) - FPTT
            [$mbaId, 'MBA 5113', 'Strategic Management in Technopreneurship', 3, 120, 'Formulate strategic plans;Evaluate competitive environments;Align technopreneurial execution with market needs;Lead corporate transformation'],
            [$mbaId, 'MBA 5123', 'Managerial Economics & Business Analytics', 3, 120, 'Apply macroeconomic analysis to firm decisions;Formulate pricing strategies;Analyze operational cost dynamics;Apply business analytics for forecasting'],
            [$mbaId, 'MBA 5133', 'Corporate Finance & Financial Decision Making', 3, 120, 'Evaluate capital budgeting decisions;Analyze corporate risk and return;Formulate financial structures;Interpret corporate financial statements'],

            // Master of Electronic Engineering - Computer Engineering (MECE) - FTKEK
            [$meceId, 'MECE 5113', 'Embedded Internet of Things (IoT) Systems', 3, 120, 'Design embedded microcontroller architectures;Implement sensor communication protocols;Deploy IoT edge computing pipelines;Evaluate power constraints'],
            [$meceId, 'MECE 5123', 'Advanced Computer Architecture & Control', 3, 120, 'Analyze superscalar and pipelined architectures;Formulate feedback control algorithms;Design automated control loops;Evaluate memory hierarchies'],

            // Bachelor of Computer Science (BITS) - FTMK
            [$bitsId, 'BITS 1123', 'Data Structures', 3, 120, 'Explain core data structures;Implement them in code;Choose a structure for a problem;Analyse running time'],
            [$bitsId, 'BITS 1124', 'Algorithms', 3, 120, 'Apply divide-and-conquer strategies;Analyze time complexity;Implement greedy and dynamic programming algorithms;Solve graph problems'],
            [$bitsId, 'BITS 1125', 'Databases', 3, 120, 'Design relational databases;Normalize tables to 3NF;Write complex SQL queries;Manage transaction locks and indexing'],
            [$bitsId, 'BITS 1213', 'Programming with Python & Java', 3, 120, 'Explain core programming syntax;Develop object-oriented solutions;Debug software applications;Implement data structures'],
            [$bitsId, 'BITS 1313', 'Relational Database Systems', 3, 120, 'Design Entity-Relationship Diagrams;Normalize relational schemas;Execute SQL queries;Ensure ACID transaction integrity'],
            [$bitsId, 'MPU 21072', 'Penghayatan Etika dan Peradaban', 3, 120, 'Menghuraikan konsep etika dan peradaban;Menganalisis perkembangan etika dalam masyarakat kepelbagaian;Mengaplikasikan nilai etika dalam kehidupan harian;Membina perpaduan nasional'],

            // Bachelor of Electrical Engineering Technology (BENE) - FTKE
            [$beneId, 'BENE 1123', 'Electric Circuit Theory', 3, 120, 'Analyze AC/DC circuits;Apply Kirchhoff laws;Design filter networks;Evaluate frequency response'],
            [$beneId, 'BENE 2133', 'Microprocessor Systems', 3, 120, 'Program embedded microcontrollers;Interface peripheral hardware devices;Analyze timing diagrams;Develop firmware routines'],

            // Bachelor of Mechanical Engineering Technology (BMKG) - FTKM
            [$bmkgId, 'BMKG 1113', 'Engineering Mechanics', 3, 120, 'Apply statics and dynamics;Solve equilibrium equations;Analyze force vectors;Calculate stress and strain'],
            [$bmkgId, 'BMKG 2123', 'Computer-Aided Design and Manufacturing (CAD/CAM)', 3, 120, 'Generate 3D CAD parametric models;Produce manufacturing drawings;Simulate CNC toolpaths;Apply GD&T standards'],

            // Bachelor of Manufacturing Engineering Technology (BMFG) - FTKIP
            [$bmfgId, 'BMFG 1113', 'Manufacturing Processes & Systems', 3, 120, 'Explain modern machining and forming processes;Analyze material transformation mechanisms;Optimize manufacturing parameters;Evaluate process capability'],
            [$bmfgId, 'BMFG 2123', 'Quality Engineering and Lean Six Sigma', 3, 120, 'Apply DMAIC methodology;Design statistical process control charts;Calculate process capability indices;Implement 5S and waste elimination'],
        ];

        foreach ($parentCourses as [$pId, $code, $name, $credit, $slt, $clos]) {
            DB::table('parent_courses')->updateOrInsert(
                ['code' => $code],
                [
                    'programme_id' => $pId,
                    'name' => $name,
                    'credit' => $credit,
                    'slt_hours' => $slt,
                    'clos' => $clos,
                    'updated_at' => now(),
                ]
            );
        }

        // 4. Pathways (Published Senate-Approved Stackable Mappings)
        $adminId = DB::table('users')->where('email', 'hakim.rahman@utem.example.test')->value('id')
            ?? DB::table('users')->first()?->id;

        if (! $adminId) {
            $adminId = DB::table('users')->insertGetId([
                'name' => 'Hakim Rahman',
                'email' => 'hakim.rahman@utem.example.test',
                'upn' => 'hakim.rahman@utem.example.test',
                'locale' => 'en',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $pathwayDefs = [
            [
                'reference' => 'PW-MTDL-1',
                'programme_id' => $mtdlId,
                'version_no' => 1,
                'status' => 'draft',
                'plos' => json_encode(['PLO1 - Data Analytics Principles', 'PLO2 - Big Data Architecture', 'PLO3 - Professional Ethics']),
                'bok' => json_encode(['Data Science', 'Machine Learning', 'Big Data Engineering']),
                'approval_ref' => 'Senate 8/2026',
                'approved_on' => '2026-10-28',
                'approved_by' => $adminId,
                'published_at' => now(),
                'created_by' => $adminId,
            ],
            [
                'reference' => 'PW-MBA-1',
                'programme_id' => $mbaId,
                'version_no' => 1,
                'status' => 'draft',
                'plos' => json_encode(['PLO1 - Strategic Management', 'PLO2 - Managerial Decision Analytics', 'PLO3 - Technopreneurship']),
                'bok' => json_encode(['Business Administration', 'Economics & Finance', 'Corporate Governance']),
                'approval_ref' => 'Senate 8/2026',
                'approved_on' => '2026-10-28',
                'approved_by' => $adminId,
                'published_at' => now(),
                'created_by' => $adminId,
            ],
            [
                'reference' => 'PW-MECE-1',
                'programme_id' => $meceId,
                'version_no' => 1,
                'status' => 'draft',
                'plos' => json_encode(['PLO1 - Embedded IoT Design', 'PLO2 - Computer Architecture', 'PLO3 - Control Engineering']),
                'bok' => json_encode(['Embedded Systems', 'IoT Networking', 'Automation']),
                'approval_ref' => 'Senate 8/2026',
                'approved_on' => '2026-10-28',
                'approved_by' => $adminId,
                'published_at' => now(),
                'created_by' => $adminId,
            ],
            [
                'reference' => 'PW-BENE-1',
                'programme_id' => $beneId,
                'version_no' => 1,
                'status' => 'draft',
                'plos' => json_encode(['PLO1 - Engineering Knowledge', 'PLO2 - Problem Analysis', 'PLO3 - Modern Tool Usage']),
                'bok' => json_encode(['Electric Circuits', 'Microprocessors', 'Power Systems']),
                'approval_ref' => 'Senate 8/2026',
                'approved_on' => '2026-10-28',
                'approved_by' => $adminId,
                'published_at' => now(),
                'created_by' => $adminId,
            ],
            [
                'reference' => 'PW-BMKG-1',
                'programme_id' => $bmkgId,
                'version_no' => 1,
                'status' => 'draft',
                'plos' => json_encode(['PLO1 - Engineering Mechanics', 'PLO2 - CAD/CAM Modelling', 'PLO3 - Mechanical Design']),
                'bok' => json_encode(['Mechanics', 'CAD/CAM', 'Thermodynamics']),
                'approval_ref' => 'Senate 8/2026',
                'approved_on' => '2026-10-28',
                'approved_by' => $adminId,
                'published_at' => now(),
                'created_by' => $adminId,
            ],
            [
                'reference' => 'PW-BMFG-1',
                'programme_id' => $bmfgId,
                'version_no' => 1,
                'status' => 'draft',
                'plos' => json_encode(['PLO1 - Manufacturing Systems', 'PLO2 - Lean Quality Engineering', 'PLO3 - Industrial Automation']),
                'bok' => json_encode(['Manufacturing Processes', 'Lean Six Sigma', 'Quality Control']),
                'approval_ref' => 'Senate 8/2026',
                'approved_on' => '2026-10-28',
                'approved_by' => $adminId,
                'published_at' => now(),
                'created_by' => $adminId,
            ],
        ];

        foreach ($pathwayDefs as $pw) {
            DB::table('pathways')->updateOrInsert(
                ['reference' => $pw['reference']],
                array_merge($pw, ['updated_at' => now()])
            );
        }

        // Section 5 provides active micro-credentials and public catalogue offerings for the live demo.
        // During unit tests, we keep micro_credentials pristine so test assertions (e.g. DB::table('micro_credentials')->first()) remain isolated.
        if (app()->runningUnitTests()) {
            return;
        }

        // 5. Approved Micro-Credentials and Public Catalogue Offerings across all 7 faculties
        $lecturers = [
            'FTMK'  => ['name' => 'Dr. Aina Zulkifli', 'email' => 'aina.zulkifli@utem.example.test'],
            'FAIX'  => ['name' => 'Dr. Lim Wei Jie', 'email' => 'lim.weijie@utem.example.test'],
            'FPTT'  => ['name' => 'Dr. Hasnah Hassan', 'email' => 'hasnah.hassan@utem.example.test'],
            'FTKE'  => ['name' => 'Assoc. Prof. Dr. Ahmad Razali', 'email' => 'ahmad.razali@utem.example.test'],
            'FTKEK' => ['name' => 'Dr. Azman Mansor', 'email' => 'azman.mansor@utem.example.test'],
            'FTKM'  => ['name' => 'Ir. Dr. Hairul Azman', 'email' => 'hairul.azman@utem.example.test'],
            'FTKIP' => ['name' => 'Dr. Noraini Daud', 'email' => 'noraini.daud@utem.example.test'],
        ];

        $lecturerIds = [];
        foreach ($lecturers as $ptjCode => $info) {
            $u = DB::table('users')->where('email', $info['email'])->first();
            $pId = DB::table('ptjs')->where('code', $ptjCode)->value('id');
            if (! $u) {
                $uid = DB::table('users')->insertGetId([
                    'name' => $info['name'],
                    'email' => $info['email'],
                    'upn' => $info['email'],
                    'locale' => 'en',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('role_assignments')->insert([
                    'user_id' => $uid,
                    'role' => 'lecturer',
                    'scope_type' => 'ptj',
                    'ptj_id' => $pId,
                    'reason' => 'Seed data',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $lecturerIds[$ptjCode] = $uid;
            } else {
                $lecturerIds[$ptjCode] = $u->id;
            }
        }

        $mcCatalog = [
            // MTDL - FAIX (Artificial Intelligence & Data Science)
            [
                'parent_code' => 'MAXL 5113',
                'name_en' => 'Fundamental of Data Science',
                'name_ms' => 'Asas Sains Data',
                'ptj' => 'FAIX',
                'discipline' => 'Artificial Intelligence, Data Science & Machine Learning',
                'mode' => 'online',
                'fee' => 1500.00,
                'pathway_ref' => 'PW-MTDL-1',
                'plo' => 'PLO1 - Data Analytics Principles',
                'bok' => 'Data Science',
            ],
            [
                'parent_code' => 'MAXL 5123',
                'name_en' => 'Big Data Management',
                'name_ms' => 'Pengurusan Data Raya',
                'ptj' => 'FAIX',
                'discipline' => 'Database Engineering & Big Data Management',
                'mode' => 'online',
                'fee' => 1500.00,
                'pathway_ref' => 'PW-MTDL-1',
                'plo' => 'PLO2 - Big Data Architecture',
                'bok' => 'Big Data Engineering',
            ],
            [
                'parent_code' => 'MAXL 5133',
                'name_en' => 'Applied Statistical Methods',
                'name_ms' => 'Kaedah Statistik Gunaan',
                'ptj' => 'FAIX',
                'discipline' => 'Mathematics, Calculus & Computational Methods',
                'mode' => 'online',
                'fee' => 1500.00,
                'pathway_ref' => 'PW-MTDL-1',
                'plo' => 'PLO1 - Data Analytics Principles',
                'bok' => 'Data Science',
            ],
            [
                'parent_code' => 'MAXL 5143',
                'name_en' => 'Applied Machine Learning',
                'name_ms' => 'Pembelajaran Mesin Gunaan',
                'ptj' => 'FAIX',
                'discipline' => 'Artificial Intelligence, Data Science & Machine Learning',
                'mode' => 'online',
                'fee' => 1500.00,
                'pathway_ref' => 'PW-MTDL-1',
                'plo' => 'PLO2 - Big Data Architecture',
                'bok' => 'Machine Learning',
            ],
            [
                'parent_code' => 'MAXL 5153',
                'name_en' => 'Big Data Analytics and Visualization',
                'name_ms' => 'Analisis dan Visualisasi Data Raya',
                'ptj' => 'FAIX',
                'discipline' => 'Artificial Intelligence, Data Science & Machine Learning',
                'mode' => 'online',
                'fee' => 1500.00,
                'pathway_ref' => 'PW-MTDL-1',
                'plo' => 'PLO1 - Data Analytics Principles',
                'bok' => 'Data Science',
            ],
            [
                'parent_code' => 'MAXL 5163',
                'name_en' => 'Modelling and Decision Making',
                'name_ms' => 'Pemodelan dan Membuat Keputusan',
                'ptj' => 'FAIX',
                'discipline' => 'Artificial Intelligence, Data Science & Machine Learning',
                'mode' => 'online',
                'fee' => 1500.00,
                'pathway_ref' => 'PW-MTDL-1',
                'plo' => 'PLO1 - Data Analytics Principles',
                'bok' => 'Data Science',
            ],
            [
                'parent_code' => 'MAXL 5273',
                'name_en' => 'Customer and Financial Analytics',
                'name_ms' => 'Analitis Pelanggan dan Kewangan',
                'ptj' => 'FAIX',
                'discipline' => 'Artificial Intelligence, Data Science & Machine Learning',
                'mode' => 'online',
                'fee' => 1500.00,
                'pathway_ref' => 'PW-MTDL-1',
                'plo' => 'PLO1 - Data Analytics Principles',
                'bok' => 'Data Science',
            ],
            // MBA - FPTT
            [
                'parent_code' => 'MBA 5113',
                'name_en' => 'Strategic Management in Technopreneurship',
                'name_ms' => 'Pengurusan Strategik dalam Teknousahawanan',
                'ptj' => 'FPTT',
                'discipline' => 'Business, Management & Technopreneurship',
                'mode' => 'online',
                'fee' => 1695.00,
                'pathway_ref' => 'PW-MBA-1',
                'plo' => 'PLO1 - Strategic Management',
                'bok' => 'Business Administration',
            ],
            [
                'parent_code' => 'MBA 5123',
                'name_en' => 'Managerial Economics & Business Analytics',
                'name_ms' => 'Ekonomi Pengurusan & Analitis Perniagaan',
                'ptj' => 'FPTT',
                'discipline' => 'Business, Management & Technopreneurship',
                'mode' => 'online',
                'fee' => 1695.00,
                'pathway_ref' => 'PW-MBA-1',
                'plo' => 'PLO2 - Managerial Decision Analytics',
                'bok' => 'Economics & Finance',
            ],
            [
                'parent_code' => 'MBA 5133',
                'name_en' => 'Corporate Finance & Financial Decision Making',
                'name_ms' => 'Kewangan Korporat & Pembuatan Keputusan Kewangan',
                'ptj' => 'FPTT',
                'discipline' => 'Business, Management & Technopreneurship',
                'mode' => 'online',
                'fee' => 1695.00,
                'pathway_ref' => 'PW-MBA-1',
                'plo' => 'PLO3 - Technopreneurship',
                'bok' => 'Economics & Finance',
            ],
            // MECE - FTKEK
            [
                'parent_code' => 'MECE 5113',
                'name_en' => 'Embedded Internet of Things (IoT) Systems',
                'name_ms' => 'Sistem Terbenam Internet Kebendaan (IoT)',
                'ptj' => 'FTKEK',
                'discipline' => 'Advanced TVET, Robotics & Electrical Engineering',
                'mode' => 'online',
                'fee' => 1600.00,
                'pathway_ref' => 'PW-MECE-1',
                'plo' => 'PLO1 - Embedded IoT Design',
                'bok' => 'Embedded Systems',
            ],
            [
                'parent_code' => 'MECE 5123',
                'name_en' => 'Advanced Computer Architecture & Control',
                'name_ms' => 'Seni Bina Komputer Lanjutan & Kawalan',
                'ptj' => 'FTKEK',
                'discipline' => 'Advanced TVET, Robotics & Electrical Engineering',
                'mode' => 'online',
                'fee' => 1600.00,
                'pathway_ref' => 'PW-MECE-1',
                'plo' => 'PLO2 - Computer Architecture',
                'bok' => 'Automation',
            ],
            // BITS - FTMK
            [
                'parent_code' => 'BITS 1123',
                'name_en' => 'Data Structures',
                'name_ms' => 'Struktur Data',
                'ptj' => 'FTMK',
                'discipline' => 'Software Engineering, Programming & Mobile Apps',
                'mode' => 'online',
                'fee' => 450.00,
                'pathway_ref' => 'PW-BITS-1',
                'plo' => 'PLO1 - Computing Knowledge',
                'bok' => 'Algorithms',
            ],
            [
                'parent_code' => 'BITS 1124',
                'name_en' => 'Algorithms',
                'name_ms' => 'Algoritma',
                'ptj' => 'FTMK',
                'discipline' => 'Software Engineering, Programming & Mobile Apps',
                'mode' => 'online',
                'fee' => 450.00,
                'pathway_ref' => 'PW-BITS-1',
                'plo' => 'PLO1 - Computing Knowledge',
                'bok' => 'Algorithms',
            ],
            [
                'parent_code' => 'BITS 1125',
                'name_en' => 'Databases',
                'name_ms' => 'Pangkalan Data',
                'ptj' => 'FTMK',
                'discipline' => 'Database Engineering & Big Data Management',
                'mode' => 'online',
                'fee' => 450.00,
                'pathway_ref' => 'PW-BITS-1',
                'plo' => 'PLO1 - Computing Knowledge',
                'bok' => 'Database Systems',
            ],
            [
                'parent_code' => 'BITS 1213',
                'name_en' => 'Programming with Python & Java',
                'name_ms' => 'Pengaturcaraan dengan Python & Java',
                'ptj' => 'FTMK',
                'discipline' => 'Software Engineering, Programming & Mobile Apps',
                'mode' => 'online',
                'fee' => 450.00,
                'pathway_ref' => 'PW-BITS-1',
                'plo' => 'PLO1 - Computing Knowledge',
                'bok' => 'Software Development',
            ],
            [
                'parent_code' => 'BITS 1313',
                'name_en' => 'Relational Database Systems',
                'name_ms' => 'Sistem Pangkalan Data Hubungan',
                'ptj' => 'FTMK',
                'discipline' => 'Database Engineering & Big Data Management',
                'mode' => 'online',
                'fee' => 450.00,
                'pathway_ref' => 'PW-BITS-1',
                'plo' => 'PLO1 - Computing Knowledge',
                'bok' => 'Database Systems',
            ],
            // General / Humanities - FPTT
            [
                'parent_code' => 'MPU 21072',
                'name_en' => 'Penghayatan Etika dan Peradaban',
                'name_ms' => 'Penghayatan Etika dan Peradaban',
                'ptj' => 'FPTT',
                'discipline' => 'Languages, Communication & Soft Skills',
                'mode' => 'online',
                'fee' => 300.00,
                'pathway_ref' => 'PW-BITS-1',
                'plo' => 'PLO3 - Professional Ethics',
                'bok' => 'Humanities & Ethics',
            ],
            // BENE - FTKE
            [
                'parent_code' => 'BENE 1123',
                'name_en' => 'Electric Circuit Theory & Power Systems',
                'name_ms' => 'Teori Litar Elektrik & Sistem Kuasa',
                'ptj' => 'FTKE',
                'discipline' => 'Advanced TVET, Robotics & Electrical Engineering',
                'mode' => 'online',
                'fee' => 450.00,
                'pathway_ref' => 'PW-BENE-1',
                'plo' => 'PLO1 - Engineering Knowledge',
                'bok' => 'Electric Circuits',
            ],
            [
                'parent_code' => 'BENE 2133',
                'name_en' => 'Microprocessor Systems & Architecture',
                'name_ms' => 'Sistem & Seni Bina Pemproses Mikro',
                'ptj' => 'FTKE',
                'discipline' => 'Advanced TVET, Robotics & Electrical Engineering',
                'mode' => 'online',
                'fee' => 450.00,
                'pathway_ref' => 'PW-BENE-1',
                'plo' => 'PLO2 - Problem Analysis',
                'bok' => 'Microprocessors',
            ],
            // BMKG - FTKM
            [
                'parent_code' => 'BMKG 1113',
                'name_en' => 'Engineering Mechanics & Dynamics',
                'name_ms' => 'Mekanik Kejuruteraan & Dinamik',
                'ptj' => 'FTKM',
                'discipline' => 'Mechanical & Automotive Engineering',
                'mode' => 'online',
                'fee' => 450.00,
                'pathway_ref' => 'PW-BMKG-1',
                'plo' => 'PLO1 - Engineering Mechanics',
                'bok' => 'Mechanics',
            ],
            [
                'parent_code' => 'BMKG 2123',
                'name_en' => 'Computer-Aided Design and Manufacturing (CAD/CAM)',
                'name_ms' => 'Reka Bentuk dan Pembuatan Berbantu Komputer (CAD/CAM)',
                'ptj' => 'FTKM',
                'discipline' => 'Mechanical & Automotive Engineering',
                'mode' => 'online',
                'fee' => 500.00,
                'pathway_ref' => 'PW-BMKG-1',
                'plo' => 'PLO2 - CAD/CAM Modelling',
                'bok' => 'CAD/CAM',
            ],
            // BMFG - FTKIP
            [
                'parent_code' => 'BMFG 1113',
                'name_en' => 'Manufacturing Processes & Automation',
                'name_ms' => 'Proses Pembuatan & Automasi',
                'ptj' => 'FTKIP',
                'discipline' => 'Manufacturing & Industrial Engineering',
                'mode' => 'online',
                'fee' => 450.00,
                'pathway_ref' => 'PW-BMFG-1',
                'plo' => 'PLO1 - Manufacturing Systems',
                'bok' => 'Manufacturing Processes',
            ],
            [
                'parent_code' => 'BMFG 2123',
                'name_en' => 'Quality Engineering and Lean Six Sigma',
                'name_ms' => 'Kejuruteraan Kualiti dan Lean Six Sigma',
                'ptj' => 'FTKIP',
                'discipline' => 'Manufacturing & Industrial Engineering',
                'mode' => 'online',
                'fee' => 500.00,
                'pathway_ref' => 'PW-BMFG-1',
                'plo' => 'PLO2 - Lean Quality Engineering',
                'bok' => 'Lean Six Sigma',
            ],
        ];

        $readinessKeys = array_keys(config('offerings.readiness') ?? [
            'transparency' => 'Transparency',
            'materials_platform' => 'Materials on platform',
            'assessment_integrity' => 'Assessment integrity',
            'records' => 'Student records system',
            'completion_credential' => 'Completion & credentials',
            'change_notification' => 'Change notification',
        ]);

        foreach ($mcCatalog as $item) {
            $parentCourse = DB::table('parent_courses')->where('code', $item['parent_code'])->first();
            if (! $parentCourse) {
                continue;
            }

            $ptjId = DB::table('ptjs')->where('code', $item['ptj'])->value('id');
            $lecturerId = $lecturerIds[$item['ptj']] ?? $adminId;

            // Find or create micro_credential
            $existingVersion = DB::table('mc_versions')->where('parent_course_id', $parentCourse->id)->first();
            if ($existingVersion) {
                $mcId = $existingVersion->micro_credential_id;
                $vId = $existingVersion->id;
            } else {
                $mcId = DB::table('micro_credentials')->insertGetId([
                    'category' => 'umc',
                    'category_status' => 'confirmed',
                    'origin' => 'unbundled',
                    'qv_planned' => false,
                    'ptj_id' => $ptjId,
                    'owner_id' => $lecturerId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $isPostgrad = str_starts_with($item['parent_code'], 'MAXL') || str_starts_with($item['parent_code'], 'MBA') || str_starts_with($item['parent_code'], 'MECE');
                $vId = DB::table('mc_versions')->insertGetId([
                    'micro_credential_id' => $mcId,
                    'version_no' => 1,
                    'proposal_type' => 'new',
                    'status' => 'approved',
                    'name_en' => $item['name_en'],
                    'name_ms' => $item['name_ms'],
                    'discipline' => $item['discipline'],
                    'target_group' => 'Working professionals and degree-seeking learners',
                    'delivery_mode' => $item['mode'],
                    'delivery_language' => 'English',
                    'need' => 'High industry demand for stackable competencies in '.$item['discipline'],
                    'planned_slt' => $parentCourse->slt_hours,
                    'credit_sought' => true,
                    'parent_course_id' => $parentCourse->id,
                    'credit_model' => 'credit_based',
                    'assessment_standard' => 'Criterion-referenced assessment with rubric',
                    'integrity_mechanism' => 'ID verification and plagiarism screening',
                    'proposed_credit' => $parentCourse->credit,
                    'proposed_mqf_level' => $isPostgrad ? 7 : 6,
                    'effective_date' => today()->subDays(10),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Find or create open offering
            $offering = DB::table('offerings')->where('mc_version_id', $vId)->where('status', 'open')->first();
            if (! $offering) {
                $oId = DB::table('offerings')->insertGetId([
                    'mc_version_id' => $vId,
                    'cohort_name' => '2027/1',
                    'start_date' => today()->addDays(25),
                    'end_date' => today()->addDays(115),
                    'delivery_mode' => $item['mode'],
                    'capacity' => 40,
                    'reg_opens_on' => today()->subDays(5),
                    'reg_closes_on' => today()->addDays(15),
                    'fee' => $item['fee'],
                    'facilitators' => $lecturers[$item['ptj']]['name'] ?? 'Faculty Academic Team',
                    'platform_ref' => 'UCreds course '.$item['parent_code'],
                    'description' => 'Comprehensive modular course unbundled from the accredited '.$parentCourse->name.' curriculum.',
                    'prerequisites' => 'Basic secondary or diploma qualification; working adults welcomed.',
                    'status' => 'open',
                    'readiness_state' => 'ready',
                    'created_by' => $lecturerId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($readinessKeys as $rKey) {
                    DB::table('readiness_items')->insertOrIgnore([
                        'offering_id' => $oId,
                        'item' => $rKey,
                        'status' => 'yes',
                        'note' => 'Accredited curriculum materials and assessment items verified on platform',
                        'accepted' => true,
                    ]);
                }

                DB::table('readiness_confirmations')->insert([
                    ['offering_id' => $oId, 'role' => 'lecturer', 'user_id' => $lecturerId, 'outcome' => 'ready', 'created_at' => now()],
                    ['offering_id' => $oId, 'role' => 'ptj', 'user_id' => $adminId, 'outcome' => 'ready', 'created_at' => now()],
                ]);
            }

            // Map to Pathway
            $pathway = DB::table('pathways')->where('reference', $item['pathway_ref'])->first();
            if ($pathway) {
                DB::table('pathway_items')->updateOrInsert(
                    [
                        'pathway_id' => $pathway->id,
                        'micro_credential_id' => $mcId,
                        'parent_course_id' => $parentCourse->id,
                    ],
                    [
                        'credits' => $parentCourse->credit,
                        'lo_equivalence' => 'high',
                        'content_equivalence' => 'high',
                        'preliminary' => 'suitable',
                        'plo_map' => json_encode([['plo' => $item['plo'], 'contribution' => 'main']]),
                        'bok_map' => json_encode([$item['bok']]),
                    ]
                );
            }
        }
    }
}
