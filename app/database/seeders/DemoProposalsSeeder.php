<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Core\Tasks;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// Fictional proposals in different stages, so the review and approval flows (FL-03, FL-04) can be walked from the start.
// Run on request: php artisan db:seed --class=DemoProposalsSeeder
class DemoProposalsSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('mc_versions')->where('name_en', 'Database Systems')->exists()) {
            return; // already seeded
        }
        $ftmk = DB::table('ptjs')->where('code', 'FTMK')->value('id');
        $aina = User::where('name', 'Dr. Aina Zulkifli')->firstOrFail();
        $hakim = User::where('name', 'Hakim Rahman')->firstOrFail();
        $lim = User::firstOrCreate(['email' => 'lim.weijie@utem.example.test'], ['name' => 'Dr. Lim Wei Jie', 'upn' => 'lim.weijie@utem.example.test']);
        $lim->roleAssignments()->firstOrCreate(['role' => 'lecturer'], ['scope_type' => 'ptj', 'ptj_id' => $ftmk, 'reason' => 'Seed data']);

        // A: waits for Dr. Aina's review (FL-03)
        $a = $this->proposal($lim, $ftmk, 'Cyber Security Operations Fundamentals', 'in_review', now()->subDays(4));
        $inv = $this->assignment($a, $aina, $hakim, 'invited', today()->addDays(3));
        Tasks::open('lecturer', 'review', $inv, 'Review: Cyber Security Operations Fundamentals', "/reviews/$inv", userId: $aina->id);
        Tasks::open('deputy_dean', 'decide', $a, 'Decide on proposal: Cyber Security Operations Fundamentals', "/approvals/$a", $ftmk);

        // B: reviewed, with two blocking comments, one already answered (FL-04)
        $b = $this->proposal($aina, $ftmk, 'Database Systems', 'in_review', now()->subDays(9));
        $rv = $this->assignment($b, $lim, $hakim, 'submitted', today()->subDays(2));
        $ratings = [];
        foreach (array_keys(config('proposals.review_items')) as $k) {
            $ratings[$k] = ['rating' => in_array($k, ['slt', 'alignment']) ? 'needs_amendment' : 'satisfactory', 'comment' => null, 'blocking' => false];
        }
        DB::table('reviews')->insert(['review_assignment_id' => $rv, 'ratings' => json_encode($ratings), 'recommendation' => 'amend', 'summary' => 'Sound content; the hours and the alignment need work.', 'submitted_at' => now()->subDays(2)]);
        $c1 = DB::table('review_comments')->insertGetId(['mc_version_id' => $b, 'review_assignment_id' => $rv, 'author_id' => $lim->id, 'item' => 'slt', 'text' => 'Adequacy of SLT: the practical work is under-counted.', 'blocking' => true, 'status' => 'answered', 'response' => 'I added 6 hours of lab work to module 2.', 'responded_by' => $aina->id, 'responded_at' => now()->subDay(), 'created_at' => now()->subDays(2)]);
        DB::table('review_comments')->insert(['mc_version_id' => $b, 'review_assignment_id' => $rv, 'author_id' => $lim->id, 'item' => 'alignment', 'text' => 'Alignment of learning and assessment: outcome 3 has no assessment.', 'blocking' => true, 'status' => 'open', 'created_at' => now()->subDays(2)]);
        Tasks::open('deputy_dean', 'decide', $b, 'Decide on proposal: Database Systems', "/approvals/$b", $ftmk);
        Tasks::open('lecturer', 'respond', $b, 'Respond to review comments: Database Systems', "/proposals/$b?tab=comments", userId: $aina->id);

        // C: endorsed, waits for the Senate decision (FL-04)
        $c = $this->proposal($aina, $ftmk, 'Applied Data Analytics', 'endorsed', now()->subDays(20));
        $rc = $this->assignment($c, $lim, $hakim, 'submitted', today()->subDays(12));
        DB::table('reviews')->insert(['review_assignment_id' => $rc, 'ratings' => json_encode(array_map(fn () => ['rating' => 'satisfactory', 'comment' => null, 'blocking' => false], config('proposals.review_items'))), 'recommendation' => 'support', 'summary' => 'Ready for the Senate.', 'submitted_at' => now()->subDays(12)]);
        DB::table('approval_decisions')->insert(['mc_version_id' => $c, 'stage' => 'endorsement', 'decision' => 'endorsed', 'decided_by' => $hakim->id, 'decided_role' => 'deputy_dean', 'verified_category' => 'samc', 'created_at' => now()->subDays(8)]);
        DB::table('micro_credentials')->where('id', DB::table('mc_versions')->where('id', $c)->value('micro_credential_id'))->update(['category_status' => 'verified', 'category_confirmed_by' => $hakim->id, 'category_confirmed_at' => now()->subDays(8)]);
        Tasks::open('deputy_dean', 'senate', $c, 'Record Senate decision: Applied Data Analytics', "/approvals/$c/senate", $ftmk);
    }

    protected function proposal(User $owner, int $ptj, string $title, string $status, $submitted): int
    {
        $mc = DB::table('micro_credentials')->insertGetId(['category' => 'samc', 'category_status' => 'proposed', 'origin' => 'own', 'qv_planned' => false, 'ptj_id' => $ptj, 'owner_id' => $owner->id, 'created_at' => now(), 'updated_at' => now()]);
        $v = DB::table('mc_versions')->insertGetId([
            'micro_credential_id' => $mc, 'status' => $status, 'name_en' => $title, 'name_ms' => $title, 'discipline' => 'Computing', 'target_group' => 'Working engineers', 'delivery_mode' => 'online', 'delivery_language' => 'English',
            'need' => 'Industry asked for it.', 'planned_slt' => 80, 'ip_confirmed' => true, 'submitted_at' => $submitted, 'updated_by' => $owner->id, 'created_at' => $submitted, 'updated_at' => now(),
        ]);
        DB::table('mc_modules')->insert(['mc_version_id' => $v, 'seq' => 1, 'name_en' => 'Module 1', 'slt_hours' => 80]);
        $lo = DB::table('learning_outcomes')->insertGetId(['mc_version_id' => $v, 'code' => 'LO1', 'text_en' => 'Apply the topic in practice', 'sort_order' => 1]);
        $comp = DB::table('assessment_components')->insertGetId(['mc_version_id' => $v, 'name_en' => 'Project', 'weight_percent' => 100]);
        DB::table('assessment_component_outcomes')->insert(['assessment_component_id' => $comp, 'learning_outcome_id' => $lo]);
        foreach (array_keys(config('proposals.readiness')) as $item) {
            DB::table('proposal_readiness')->insert(['mc_version_id' => $v, 'item' => $item, 'status' => 'yes']);
        }

        return $v;
    }

    private function assignment(int $version, User $reviewer, User $by, string $status, $due): int
    {
        return DB::table('review_assignments')->insertGetId(['mc_version_id' => $version, 'reviewer_id' => $reviewer->id, 'kind' => 'reviewer', 'assigned_by' => $by->id, 'due_on' => $due, 'scope' => 'Academic content and assessment', 'status' => $status,
            'coi_declared_at' => $status === 'invited' ? null : now()->subDays(3), 'has_conflict' => $status === 'invited' ? null : false, 'created_at' => now(), 'updated_at' => now()]);
    }
}
