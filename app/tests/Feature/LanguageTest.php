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
    expect($amir->fresh()->locale)->toBe('ms');
    $this->actingAs($amir)->get('/my-results')->assertSee('Keputusan saya')->assertSee('Pembelajaran saya')->assertSee('Log keluar')->assertDontSee('Registrations and payments');
    $this->actingAs($amir)->post('/language', ['lang' => 'en']);
    expect($amir->fresh()->locale)->toBe('en');
    $this->actingAs($amir)->get('/my-results')->assertSee('My results');
});

it('ensures zero missing Blade translation keys in lang/ms.json', function () {
    $json = json_decode(file_get_contents(base_path('lang/ms.json')), true);
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views')));
    $keys = [];
    foreach ($files as $f) {
        if ($f->isFile() && str_ends_with($f->getFilename(), '.blade.php')) {
            $c = file_get_contents($f->getPathname());
            if (preg_match_all("/__\(\s*['\"]([^'\"]+)['\"]\s*[\),]/", $c, $m)) {
                foreach ($m[1] as $k) {
                    $keys[$k] = true;
                }
            }
        }
    }

    $missing = [];
    foreach (array_keys($keys) as $k) {
        if (! isset($json[$k])) {
            $missing[] = $k;
        }
    }

    expect($missing)->toBeEmpty();
});
