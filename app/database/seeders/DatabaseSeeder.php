<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Core\Templates;
use App\Modules\Records\AuditRecorder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(AuditRecorder $audit, Templates $templates): void
    {
        // Settings: one row per key with its first version (Guide values).
        foreach (config('settings-defaults') as $key => $d) {
            if (DB::table('settings')->where('key', $key)->exists()) {
                continue;
            }
            $id = DB::table('settings')->insertGetId(['key' => $key, 'value_type' => $d['type'], 'unit' => $d['unit'], 'description_en' => $d['en'], 'source_ref' => $d['ref']]);
            DB::table('setting_versions')->insert(['setting_id' => $id, 'value' => json_encode($d['value']), 'effective_from' => now(), 'reason' => 'Initial value from the Guide and the Glossary']);
            $audit->record('setting.seeded', ['type' => 'Setting', 'id' => $key], after: ['value' => $d['value']], reason: 'Initial value');
        }

        // Reference data of the demonstration scenario (PRD 7.2) with the 7 official faculties.
        $officialPtjs = [
            ['FTKEK', 'Fakulti Teknologi Dan Kejuruteraan Elektronik Dan Komputer'],
            ['FTKE',  'Fakulti Teknologi Dan Kejuruteraan Elektrik'],
            ['FTKM',  'Fakulti Teknologi Dan Kejuruteraan Mekanikal'],
            ['FTKIP', 'Fakulti Teknologi Dan Kejuruteraan Industri Dan Pembuatan'],
            ['FTMK',  'Fakulti Teknologi Maklumat Dan Komunikasi'],
            ['FPTT',  'Fakulti Pengurusan Teknologi Dan Teknousahawanan'],
            ['FAIX',  'Fakulti Kecerdasan Buatan Dan Keselamatan Siber'],
        ];
        foreach ($officialPtjs as [$c, $n]) {
            DB::table('ptjs')->insertOrIgnore(['code' => $c, 'name' => $n, 'created_at' => now(), 'updated_at' => now()]);
        }
        DB::table('programmes')->insertOrIgnore(['code' => 'BITS', 'name' => 'Bachelor of Computer Science', 'mqf_level' => 6, 'mode' => 'conventional', 'accreditation_status' => 'accredited', 'total_credits' => 120, 'ptj_id' => DB::table('ptjs')->where('code', 'FTMK')->value('id'), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('parent_courses')->insertOrIgnore(['programme_id' => DB::table('programmes')->where('code', 'BITS')->value('id'), 'code' => 'BITS 1123', 'name' => 'Data Structures', 'credit' => 3, 'slt_hours' => 120, 'clos' => 'Explain core data structures;Implement them in code;Choose a structure for a problem;Analyse running time', 'created_at' => now(), 'updated_at' => now()]);

        $this->templates($templates);
        $this->ucredsCourses();

        // Fictional personas of the demonstration scenario. Never real people.
        $ptj = fn ($c) => DB::table('ptjs')->where('code', $c)->value('id');
        foreach ([
            ['Amir Hakimi', 'amir.hakimi@learner.example.test', 'student', null, 'en'],
            ['Dr. Aina Zulkifli', 'aina.zulkifli@utem.example.test', 'lecturer', 'FTMK', 'en'],
            ['Hakim Rahman', 'hakim.rahman@utem.example.test', 'deputy_dean', 'FTMK', 'en'],
            ['Farid Hashim', 'farid.hashim@utem.example.test', 'codl_admin', null, 'en'],
            ['Ir. Siti Aminah', 'siti.aminah@industry.example.test', 'industry_expert', null, 'ms'],
        ] as [$name, $email, $role, $code, $locale]) {
            $u = User::firstOrCreate(['email' => $email], ['name' => $name, 'upn' => $email, 'locale' => $locale, 'participant_group' => $role === 'student' ? 'working professional' : null, 'institution_id' => $role === 'student' ? 'B032410001' : null]);
            $scopeType = $code ? 'ptj' : ($role === 'codl_admin' ? 'university' : null);
            $u->roleAssignments()->firstOrCreate(['role' => $role], ['scope_type' => $scopeType, 'ptj_id' => $code ? $ptj($code) : null, 'reason' => 'Seed data']);
        }

        $this->call(UcredsReferenceSeeder::class);

        $this->tasks();
    }

    /** The public UCreds catalogue (Decision Log v0.7, Appendix A). Reference data; running it twice changes nothing. */
    private function ucredsCourses(): void
    {
        foreach (json_decode((string) file_get_contents(database_path('data/ucreds_courses.json')), true) as $c) {
            DB::table('ucreds_courses')->insertOrIgnore(['course_id' => $c['course_id'], 'title' => $c['title'], 'kind' => $c['kind'], 'domain' => $c['domain'], 'level' => $c['level'], 'subject_code' => $c['subject_code'],
                'duration' => $c['duration'], 'hours' => $c['hours'], 'fee' => $c['fee'], 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    private function templates(Templates $templates): void
    {
        $defs = [
            ['role.assigned', 'notification', false, 'A role was given to a user', 'Your MCMS role changed', 'Hello {name}, you now hold the role {role} ({scope}) from {from}. Reason: {reason}.', 'Peranan MCMS anda telah berubah', 'Salam {name}, anda kini memegang peranan {role} ({scope}) mulai {from}. Sebab: {reason}.'],
            ['role.revoked', 'notification', false, 'A role was taken away from a user', 'Your MCMS role ended', 'Hello {name}, your role {role} has ended. Reason: {reason}.', 'Peranan MCMS anda telah tamat', 'Salam {name}, peranan {role} anda telah tamat. Sebab: {reason}.'],
            ['account.deactivated', 'notification', true, 'An account was deactivated', 'Your MCMS account is deactivated', 'Hello {name}, your account has been deactivated. Reason: {reason}. Your records are kept.', 'Akaun MCMS anda dinyahaktifkan', 'Salam {name}, akaun anda telah dinyahaktifkan. Sebab: {reason}. Rekod anda dikekalkan.'],
            ['account.reactivated', 'notification', true, 'An account was reactivated', 'Your MCMS account is active again', 'Hello {name}, your account is active again.', 'Akaun MCMS anda aktif semula', 'Salam {name}, akaun anda aktif semula.'],
            ['decision.made', 'notification', true, 'A decision was made on something the user submitted', 'A decision was recorded on {item}', 'Hello {name}, the decision on {item} is: {decision}.', 'Keputusan telah direkodkan bagi {item}', 'Salam {name}, keputusan bagi {item} ialah: {decision}.'],
            ['proposal.submitted', 'notification', false, 'A proposal was submitted to the deputy dean', 'Proposal submitted: {title}', 'Hello {name}, {owner} submitted the proposal "{title}". Please assign reviewers.', 'Cadangan dihantar: {title}', 'Salam {name}, {owner} telah menghantar cadangan "{title}". Sila tetapkan penilai.'],
            ['reviewer.assigned', 'notification', false, 'A reviewer was asked to review a proposal', 'Review requested: {title}', 'Hello {name}, you are asked to review "{title}" by {due}. Declare any conflict of interest before you open it.', 'Permintaan penilaian: {title}', 'Salam {name}, anda diminta menilai "{title}" sebelum {due}. Isytiharkan sebarang konflik kepentingan sebelum membukanya.'],
            ['reviewer.declined', 'notification', false, 'A reviewer declared a conflict of interest', 'Reviewer declined: {title}', 'Hello {name}, {reviewer} declared a conflict of interest and left the review of "{title}". Please assign another reviewer.', 'Penilai menarik diri: {title}', 'Salam {name}, {reviewer} mengisytiharkan konflik kepentingan dan menarik diri daripada penilaian "{title}". Sila tetapkan penilai lain.'],
            ['review.submitted', 'notification', false, 'A review was submitted', 'Review submitted: {title}', 'Hello {name}, {reviewer} submitted a review of "{title}": {recommendation}.', 'Penilaian dihantar: {title}', 'Salam {name}, {reviewer} telah menghantar penilaian "{title}": {recommendation}.'],
            ['proposal.returned', 'notification', true, 'A proposal was returned for amendment', 'Returned for amendment: {title}', 'Hello {name}, "{title}" is returned for amendment. Reason: {reason}. It is unlocked.', 'Dikembalikan untuk pindaan: {title}', 'Salam {name}, "{title}" dikembalikan untuk pindaan. Sebab: {reason}. Ia dibuka semula.'],
            ['proposal.endorsed', 'notification', true, 'A proposal was endorsed', 'Endorsed: {title}', 'Hello {name}, "{title}" is endorsed and waits for the Senate decision.', 'Disokong: {title}', 'Salam {name}, "{title}" telah disokong dan menunggu keputusan Senat.'],
            ['change.minor', 'notification', false, 'A minor change was recorded', 'Minor change recorded: {title}', 'Hello {name}, {owner} recorded a minor change to "{title}": {summary}. Please acknowledge it.', 'Perubahan kecil direkodkan: {title}', 'Salam {name}, {owner} merekodkan perubahan kecil bagi "{title}": {summary}. Sila akui.'],
            ['enrolment.registered', 'notification', false, 'A registration waits for payment', 'Please pay to complete your registration: {title}', 'Hello {name}, your place in "{title}" is held while you pay RM {fee}. Open "Registrations and payments" to pay.', 'Sila bayar untuk melengkapkan pendaftaran: {title}', 'Salam {name}, tempat anda dalam "{title}" dikekalkan sementara anda membayar RM {fee}. Buka "Pendaftaran dan bayaran" untuk membayar.'],
            ['payment.confirmed', 'notification', true, 'A payment was received', 'Payment received: {reference}', 'Hello {name}, we received RM {amount} for registration {reference} (payment reference {payment}).', 'Bayaran diterima: {reference}', 'Salam {name}, kami menerima RM {amount} untuk pendaftaran {reference} (rujukan bayaran {payment}).'],
            ['enrolment.confirmed', 'notification', true, 'An enrolment is confirmed, with access instructions', 'You are enrolled: {title}', 'Hello {name}, you are enrolled in "{title}" (ID {reference}). The cohort starts on {start}. Open the learning platform course: {platform}.', 'Anda telah didaftarkan: {title}', 'Salam {name}, anda telah didaftarkan dalam "{title}" (ID {reference}). Kohort bermula pada {start}. Buka kursus platform pembelajaran: {platform}.'],
            ['enrolment.withdrawn', 'notification', true, 'A participant withdrew', 'You have withdrawn: {title}', 'Hello {name}, you have withdrawn from "{title}". Refunds are handled outside MCMS: ask the CODL office.', 'Anda telah menarik diri: {title}', 'Salam {name}, anda telah menarik diri daripada "{title}". Bayaran balik diuruskan di luar MCMS: hubungi pejabat CODL.'],
            ['version.changed', 'notification', true, 'An approved change affects current participants', 'A change to {title}', 'Hello {name}, version {version} of "{title}" takes effect on {date}. What this means for you: {rule}.', 'Perubahan pada {title}', 'Salam {name}, versi {version} bagi "{title}" berkuat kuasa pada {date}. Maksudnya bagi anda: {rule}.'],
            ['results.staged', 'notification', false, 'Results or badges were staged from a UCreds file', 'UCreds {kind} staged: {title}', 'Hello {name}, {staged} rows of the UCreds {kind} file for "{title}" were staged. {detail} Complete any missing marks on the cohort results page.', 'Fail UCreds disediakan: {title}', 'Salam {name}, {staged} baris fail UCreds {kind} bagi "{title}" telah disediakan. {detail} Lengkapkan markah yang tiada pada halaman keputusan kohort.'],
            ['results.verified', 'notification', false, 'Results were verified', 'Your results are verified: {title}', 'Hello {name}, your results for "{title}" are verified. Open "My results" to see them.', 'Keputusan anda telah disahkan: {title}', 'Salam {name}, keputusan anda bagi "{title}" telah disahkan. Buka "Keputusan saya" untuk melihatnya.'],
            ['completion.confirmed', 'notification', true, 'Completion of a micro-credential was confirmed', 'Completion confirmed: {title}', 'Hello {name}, the deputy dean confirmed that you completed "{title}". Your certificate or Digital Credential is issued after this confirmation.', 'Penamatan disahkan: {title}', 'Salam {name}, timbalan dekan telah mengesahkan bahawa anda menamatkan "{title}". Sijil atau Digital Credential anda dikeluarkan selepas pengesahan ini.'],
            ['consent.abc_deposit', 'statement', true, 'Wording of the consent to deposit an Academic Credit Record', null, 'I agree that UTeM may send my Academic Credit Record to the Academic Bank of Credit (ABC) for storage. A deposit is storage of a credit record only. It is not a credit transfer and does not mean any programme accepts the credit. I can decline or postpone and give consent later.', null, 'Saya bersetuju UTeM menghantar Rekod Kredit Akademik saya kepada Akaun Bank Kredit (ABC) untuk disimpan. Deposit ialah penyimpanan rekod kredit sahaja. Ia bukan pindahan kredit dan tidak bermakna mana-mana program menerima kredit tersebut. Saya boleh menolak atau menangguhkan dan memberi persetujuan kemudian.'],
            ['acr.confirmed', 'notification', true, 'The Academic Credit Record was confirmed and needs consent', 'Your Academic Credit Record is ready: {title}', 'Hello {name}, your Academic Credit Record for "{title}" is confirmed. Open "Credit ledger and ABC" to give, decline or postpone consent to deposit it. Nothing is deposited without your consent.', 'Rekod Kredit Akademik anda sedia: {title}', 'Salam {name}, Rekod Kredit Akademik anda bagi "{title}" telah disahkan. Buka "Lejar kredit dan ABC" untuk memberi, menolak atau menangguhkan persetujuan deposit. Tiada apa didepositkan tanpa persetujuan anda.'],
            ['abc.deposited', 'notification', true, 'The credit was deposited', 'Your credit is deposited: {title}', 'Hello {name}, your Academic Credit Record for "{title}" is deposited in ABC and the credit is available until {date}. A deposit is storage only and is not a credit transfer.', 'Kredit anda didepositkan: {title}', 'Salam {name}, Rekod Kredit Akademik anda bagi "{title}" telah didepositkan di ABC dan kredit tersedia sehingga {date}. Deposit ialah penyimpanan sahaja dan bukan pindahan kredit.'],
            ['credit.expiring', 'notification', true, 'Credit validity is ending', 'Your credit ends in {days} days: {title}', 'Hello {name}, the credit from "{title}" is valid until {date}, in {days} days. After that it shows as Expired for transfer purposes. Your record is kept.', 'Kredit anda tamat dalam {days} hari: {title}', 'Salam {name}, kredit daripada "{title}" sah sehingga {date}, dalam {days} hari. Selepas itu ia dipaparkan Tamat Tempoh untuk tujuan pindahan. Rekod anda kekal.'],
            ['credit.expired', 'notification', true, 'Credit validity ended', 'Your credit has expired: {title}', 'Hello {name}, the credit from "{title}" expired on {date}. Your Academic Credit Record is kept. A transfer request that uses it is flagged for re-evaluation.', 'Kredit anda telah tamat tempoh: {title}', 'Salam {name}, kredit daripada "{title}" tamat tempoh pada {date}. Rekod Kredit Akademik anda kekal. Permohonan pindahan yang menggunakannya ditanda untuk penilaian semula.'],
            ['transfer.decided', 'notification', true, 'A credit transfer decision was recorded', 'Credit transfer {ref}: {outcome}', 'Hello {name}, the decision on your credit transfer application {ref} is: {outcome}. Open "Credit transfer" for the details. A transfer decision is separate from admission to the programme.', 'Pindahan kredit {ref}: {outcome}', 'Salam {name}, keputusan permohonan pindahan kredit anda {ref} ialah: {outcome}. Buka "Pindahan kredit" untuk butiran. Keputusan pindahan adalah berasingan daripada kemasukan ke program.'],
            ['transfer.evidence', 'notification', true, 'More evidence is needed for a transfer', 'More evidence needed: {ref}', 'Hello {name}, the evaluator needs more evidence for your credit transfer application {ref}: {request}', 'Bukti tambahan diperlukan: {ref}', 'Salam {name}, penilai memerlukan bukti tambahan bagi permohonan pindahan kredit anda {ref}: {request}'],
            ['plan.decided', 'notification', true, 'A stackable plan decision was recorded', 'Your degree plan {ref}: {decision}', 'Hello {name}, the decision on your stackable degree plan {ref} is: {decision}. A plan is advice. It is not admission, a credit transfer or an award.', 'Pelan ijazah anda {ref}: {decision}', 'Salam {name}, keputusan pelan ijazah berperingkat anda {ref} ialah: {decision}. Pelan ialah nasihat. Ia bukan kemasukan, pindahan kredit atau anugerah.'],
            ['suspension.notice', 'notification', true, 'An offering was suspended or terminated', 'Your cohort was {kind}: {title}', 'Hello {name}, the cohort "{title}" was {kind} from {date}. Reason: {reason}. What happens to you: {plan}. Achievements, credentials and your Academic Credit Record you already earned stay valid.', 'Kohort anda {kind}: {title}', 'Salam {name}, kohort "{title}" telah {kind} mulai {date}. Sebab: {reason}. Apa yang berlaku kepada anda: {plan}. Pencapaian, kredensial dan Rekod Kredit Akademik yang telah anda peroleh kekal sah.'],
            ['review.due', 'notification', false, 'A periodic review is due', 'Periodic review due on {date}: {title}', 'Hello {name}, the periodic review of "{title}" is due on {date}. Record its outcome in Quality review.', 'Semakan berkala pada {date}: {title}', 'Salam {name}, semakan berkala bagi "{title}" perlu dibuat pada {date}. Rekodkan hasilnya dalam Semakan kualiti.'],
            ['correction.recorded', 'notification', true, 'A correction was recorded', 'A correction affects your record', 'Hello {name}, a mismatch in your records was corrected. What was found: {what} What was done: {action} The original is kept in the record and your earlier achievements stay valid.', 'Pembetulan melibatkan rekod anda', 'Salam {name}, ketidakpadanan dalam rekod anda telah dibetulkan. Apa yang ditemui: {what} Apa yang dilakukan: {action} Rekod asal disimpan dan pencapaian anda yang terdahulu kekal sah.'],
            ['qv.expiring', 'notification', false, 'Quality Verification is about to end', 'Quality Verification ends in {days} days: {title}', 'Hello {name}, the Quality Verification of "{title}" ends on {date}, in {days} days. Prepare a new cycle so the credit and level stay in the catalogue.', 'Pengesahan Kualiti tamat dalam {days} hari: {title}', 'Salam {name}, Pengesahan Kualiti bagi "{title}" tamat pada {date}, dalam {days} hari. Sediakan kitaran baharu supaya kredit dan tahap kekal dalam katalog.'],
            ['qv.lapsed', 'notification', true, 'Quality Verification ended', 'Quality Verification ended: {title}', 'Hello {name}, the Quality Verification of "{title}" ended. Reason: {why}. The credit and level are no longer shown for new cohorts and new credentials. Credentials already earned stay.', 'Pengesahan Kualiti tamat: {title}', 'Salam {name}, Pengesahan Kualiti bagi "{title}" telah tamat. Sebab: {why}. Kredit dan tahap tidak lagi dipaparkan untuk kohort dan kredensial baharu. Kredensial yang telah diperoleh kekal.'],
            ['completion.window', 'notification', false, 'The time to finish all modules is running out', 'Time to finish: {title}', 'Hello {name}, you have until {date} to complete all modules of "{title}".', 'Masa untuk menamatkan: {title}', 'Salam {name}, anda mempunyai sehingga {date} untuk menamatkan semua modul "{title}".'],
            ['credential.issued', 'notification', true, 'A credential was issued', 'Your credential is issued: {title}', 'Hello {name}, your certificate and Digital Credential for "{title}" are issued (number {number}). Open "Credentials" to see, share and download them.', 'Kredensial anda dikeluarkan: {title}', 'Salam {name}, sijil dan Digital Credential anda bagi "{title}" telah dikeluarkan (nombor {number}). Buka "Kredensial" untuk melihat, berkongsi dan memuat turunnya.'],
            ['credential.corrected', 'notification', true, 'A credential was corrected', 'Your credential was corrected: {number}', 'Hello {name}, credential {number} was corrected. Reason: {reason}. The original is kept and the public link shows Corrected.', 'Kredensial anda telah dibetulkan: {number}', 'Salam {name}, kredensial {number} telah dibetulkan. Sebab: {reason}. Yang asal disimpan dan pautan awam menunjukkan Dibetulkan.'],
            ['credential.replaced', 'notification', true, 'A credential was replaced', 'Your credential was replaced: {number}', 'Hello {name}, credential {number} was replaced by a new one. Reason: {reason}. Share the new link.', 'Kredensial anda telah diganti: {number}', 'Salam {name}, kredensial {number} telah diganti dengan yang baharu. Sebab: {reason}. Kongsikan pautan baharu.'],
            ['credential.revoked', 'notification', true, 'A credential was revoked', 'Your credential was revoked: {number}', 'Hello {name}, credential {number} was revoked. Reason: {reason}.', 'Kredensial anda telah dibatalkan: {number}', 'Salam {name}, kredensial {number} telah dibatalkan. Sebab: {reason}.'],
            ['enrolment.cancelled', 'notification', true, 'A registration was cancelled', 'Registration cancelled: {title}', 'Hello {name}, your registration for "{title}" is cancelled. {reason}', 'Pendaftaran dibatalkan: {title}', 'Salam {name}, pendaftaran anda untuk "{title}" dibatalkan. {reason}'],
            ['statement.catalogue.umc', 'statement', false, 'Catalogue statement for a UMC', null, 'This is an Unbundled Micro-Credential (UMC) from {parent}. Completing the whole UMC carries the credit of the parent course. A module or a badge on its own is not completion and not credit.', null, 'Ini ialah Unbundled Micro-Credential (UMC) daripada {parent}. Menamatkan keseluruhan UMC membawa kredit kursus induk. Satu modul atau satu lencana sahaja bukan penamatan dan bukan kredit.'],
            ['statement.catalogue.samc', 'statement', false, 'Catalogue statement for a SAMC without valid Quality Verification', null, 'This is a Standalone Micro-Credential (SAMC). It carries no credit value and no MQF level unless it is Quality-Verified and the verification is valid.', null, 'Ini ialah Standalone Micro-Credential (SAMC). Ia tidak membawa nilai kredit atau tahap MQF melainkan telah disahkan melalui Quality Verification dan pengesahan itu masih sah.'],
            ['statement.catalogue.samc_qv', 'statement', false, 'Catalogue statement for a Quality-Verified SAMC', null, 'This SAMC is Quality-Verified. Credit {credit}, MQF level {mqf}, valid until {until}.', null, 'SAMC ini telah disahkan melalui Quality Verification. Kredit {credit}, tahap MQF {mqf}, sah hingga {until}.'],
            ['statement.catalogue.mce', 'statement', false, 'Catalogue statement for an MCE', null, 'This is an Executive Micro-Credential (MCE). It earns no academic credit, no Academic Credit Record and no deposit to the Academic Bank of Credit.', null, 'Ini ialah Micro-Credential Eksekutif (MCE). Ia tidak memberi kredit akademik, Academic Credit Record atau deposit ke Academic Bank of Credit.'],
            ['statement.separate_stages', 'statement', false, 'Notice shown with every offering and credential (D-15)', null, 'Completing this micro-credential, receiving an Academic Credit Record, depositing credit to the Academic Bank of Credit and transferring credit are separate decisions.', null, 'Menamatkan micro-credential ini, menerima Academic Credit Record, mendepositkan kredit ke Academic Bank of Credit dan memindahkan kredit adalah keputusan yang berasingan.'],
        ];
        foreach ($defs as [$key, $kind, $mand, $desc, $sEn, $bEn, $sMs, $bMs]) {
            if (DB::table('templates')->where('key', $key)->exists()) {
                continue;
            }
            $id = DB::table('templates')->insertGetId(['key' => $key, 'kind' => $kind, 'mandatory' => $mand, 'description' => $desc]);
            $templates->addVersion($id, 'en', $sEn, $bEn, 'First version', null);
            $templates->addVersion($id, 'ms', $sMs, $bMs, 'First version', null);
        }
    }

    // Demonstration queue (fictional items) so the task page has something to show.
    private function tasks(): void
    {
        if (DB::table('tasks')->exists()) {
            return;
        }
        $areas = ['proposals' => 'Endorse proposal', 'offerings' => 'Confirm readiness', 'results' => 'Verify results', 'credentials' => 'Confirm completion', 'deposits' => 'Record ABC deposit', 'transfers' => 'Decide transfer'];
        $n = 0;
        foreach ($areas as $area => $verb) {
            foreach (range(1, 4) as $i) {
                $n++;
                DB::table('tasks')->insert(['assignee_role' => 'deputy_dean', 'area' => $area, 'title' => "$verb: sample item $n", 'subject_ref' => strtoupper(substr($area, 0, 3))."-$n", 'created_at' => now()->subDays(($n * 2) % 26 + 1)]);
            }
        }
        DB::table('tasks')->insert([
            ['assignee_role' => 'lecturer', 'area' => 'proposals', 'title' => 'Answer review comments on Data Structures', 'subject_ref' => 'PRO-1', 'created_at' => now()->subDays(2)],
            ['assignee_role' => 'student', 'area' => 'deposits', 'title' => 'Give or decline consent to deposit your ACR', 'subject_ref' => 'ACR-1', 'created_at' => now()->subDay()],
            ['assignee_role' => 'codl_admin', 'area' => 'results', 'title' => 'Import UCreds results for Cloud Native Development', 'subject_ref' => 'IMP-1', 'created_at' => now()->subDays(3)],
        ]);
    }
}
