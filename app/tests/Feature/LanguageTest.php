<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    config(['mcms.stub_idp' => true]);
    $this->seed(DatabaseSeeder::class);
});

it('switches the interface to Bahasa Melayu for the session and back (UI/UX 4.7)', function () {
    $amir = User::where('name', 'Amir Hakimi')->first();
    $this->actingAs($amir)->get('/tasks')->assertSee('My learning')->assertSee('Registrations and payments');
    $this->actingAs($amir)->post('/language', ['lang' => 'ms'])->assertRedirect();
    $this->actingAs($amir)->get('/my-results')->assertSee('Keputusan saya')->assertSee('Pembelajaran saya')->assertSee('Log keluar')->assertDontSee('Registrations and payments');
    $this->actingAs($amir)->post('/language', ['lang' => 'en']);
    $this->actingAs($amir)->get('/my-results')->assertSee('My results');
});
