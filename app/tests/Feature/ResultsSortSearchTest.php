<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    config(['mcms.stub_idp' => true]);
    $this->seed(DatabaseSeeder::class);
    $this->aina = User::where('name', 'Dr. Aina Zulkifli')->first();
    $this->hakim = User::where('name', 'Hakim Rahman')->first();
});

it('renders search toolbar and sortable column headers on results gradebook table', function () {
    [$o, $v1, $mc, $people] = runningCohort();

    $res = $this->actingAs($this->aina)->get("/offerings/$o/results");

    $res->assertOk()
        ->assertSee('results-search-input')
        ->assertSee('results-sort-btn')
        ->assertSee('results-filter-btn')
        ->assertSee('name="results_sort_field" value="name"', false)
        ->assertSee('name="results_sort_field" value="progress"', false)
        ->assertSee('name="results_sort_field" value="final"', false)
        ->assertSee('name="results_sort_field" value="result"', false)
        ->assertSee('data-filter="missing"', false)
        ->assertSee('data-filter="pass"', false)
        ->assertSee('data-filter="fail"', false)
        ->assertSee('data-filter="held"', false);
});

it('renders results search and sort controls in Bahasa Melayu when BM locale is active', function () {
    [$o, $v1, $mc, $people] = runningCohort();

    $this->actingAs($this->aina)->post('/language', ['lang' => 'ms']);
    $res = $this->actingAs($this->aina)->get("/offerings/$o/results");

    $res->assertOk()
        ->assertSee('Cari nama peserta, no. matrik...')
        ->assertSee('Tapis:')
        ->assertSee('Kosongkan carian');
});
