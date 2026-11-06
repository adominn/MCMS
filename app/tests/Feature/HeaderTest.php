<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    config(['mcms.stub_idp' => true]);
    $this->seed(DatabaseSeeder::class);
});

it('gives staff a top-bar search and a profile page, and students neither search nor staff results (UI/UX 4.4)', function () {
    $aina = User::where('name', 'Dr. Aina Zulkifli')->first();
    $amir = User::where('name', 'Amir Hakimi')->first();
    $this->actingAs($aina)->get('/tasks')->assertSee('Search micro-credentials, cohorts, learners')->assertSee('My profile');
    $this->actingAs($aina)->get('/search?q=a')->assertOk()->assertSee('at least two letters');
    $this->actingAs($aina)->get('/profile')->assertOk()->assertSee('My roles')->assertSee('minutes without activity');
    $this->actingAs($amir)->get('/tasks')->assertDontSee('role="search"', false);
    $this->actingAs($amir)->get('/search?q=data')->assertForbidden();
});

it('opens help for student, lecturer, deputy dean and administrator without errors', function () {
    $amir = User::where('name', 'Amir Hakimi')->first();
    $aina = User::where('name', 'Dr. Aina Zulkifli')->first();
    $hakim = User::where('name', 'Hakim Rahman')->first();
    $farid = User::where('name', 'Farid Hashim')->first();

    $this->actingAs($amir)->get('/help')->assertOk()->assertSee('My learning');
    $this->actingAs($aina)->get('/help')->assertOk()->assertSee('Proposals and versions');
    $this->actingAs($hakim)->get('/help')->assertOk()->assertSee('Task queue');
    $this->actingAs($farid)->get('/help')->assertOk()->assertSee('Administration & governance');
});
