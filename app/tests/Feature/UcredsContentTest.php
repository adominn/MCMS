<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config(['mcms.stub_idp' => true]);
    $this->seed(DatabaseSeeder::class);
    $this->aina = User::where('name', 'Dr. Aina Zulkifli')->first();
    $this->hakim = User::where('name', 'Hakim Rahman')->first();
    $this->amir = User::where('name', 'Amir Hakimi')->first();
    $this->farid = User::where('name', 'Farid Hashim')->first();
    $this->lim = User::create(['name' => 'Dr. Lim Wei Jie', 'email' => 'lim@utem.example.test']);
    $this->lim->roleAssignments()->create(['role' => 'lecturer', 'scope_type' => 'ptj', 'ptj_id' => DB::table('ptjs')->where('code', 'FTMK')->value('id')]);
});

it('holds the public UCreds catalogue as reference data, and loading it twice adds nothing (D-34)', function () {
    expect(DB::table('ucreds_courses')->count())->toBe(59)->and(DB::table('ucreds_courses')->where('kind', 'programme')->count())->toBe(3);
    $this->seed(DatabaseSeeder::class);
    expect(DB::table('ucreds_courses')->count())->toBe(59);
    $mtdl = DB::table('ucreds_courses')->where('course_id', 80)->first();
    expect($mtdl->title)->toBe('Master of Technology in Data Science and Analytics')->and((float) $mtdl->fee)->toBe(25110.0)->and((float) $mtdl->hours)->toBe(1600.0);
    expect(DB::table('ucreds_courses')->where('course_id', 517)->value('subject_code'))->toBe('MAXL5143')->and((float) DB::table('ucreds_courses')->where('course_id', 517)->value('fee'))->toBe(0.0);
});

it('lets the CODL administrator search the content by title, course ID or subject code (D-34)', function () {
    $this->actingAs($this->farid)->get('/admin/ucreds/courses')->assertOk()->assertSee('Master of Business Administration')->assertSee('59 courses');
    $this->get('/admin/ucreds/courses?q=MAXL5143')->assertOk()->assertSee('Introduction to Deep Learning')->assertDontSee('Master of Business Administration');
    $this->get('/admin/ucreds/courses?q=155')->assertOk()->assertSee('First Order Differential Equations');
    $this->get('/admin/ucreds/courses?domain='.urlencode('Cybersecurity, Information Security & Privacy'))->assertOk()->assertSee('Phishing Techniques')->assertDontSee('Master of Business Administration');
    $this->get('/admin/ucreds/courses?q=zzzz')->assertOk()->assertSee('Nothing matches');
});

it('keeps the content to CODL administration', function () {
    foreach ([$this->hakim, $this->aina, $this->amir] as $u) {
        $this->actingAs($u)->get('/admin/ucreds/courses')->assertForbidden();
    }
});

it('offers the UCreds courses as the learning platform course reference of an offering (D-34)', function () {
    approvedV1(); // the form appears once there is an approved version to offer
    $this->actingAs($this->aina)->get('/offerings/new')->assertOk()->assertSee('datalist', false)->assertSee('UCreds course 517')->assertSee('Introduction to Deep Learning (MAXL5143)');
});
