<?php

declare(strict_types=1);

namespace App\Modules\Credentials;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

// SR-08: Ed25519 signing keys. A rotated key is retired, not removed, so credentials already issued still verify.
final class KeyStore
{
    public static function b64(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    public static function unb64(string $s): string
    {
        return (string) base64_decode(strtr($s, '-_', '+/'), true);
    }

    /** The key that signs now. A development install creates the first one on demand. */
    public static function active(): object
    {
        return DB::table('signing_keys')->where('status', 'active')->orderByDesc('id')->first() ?? self::rotate();
    }

    public static function rotate(): object
    {
        $pair = sodium_crypto_sign_keypair();
        DB::table('signing_keys')->where('status', 'active')->update(['status' => 'retired']);
        $kid = 'mcms-'.now()->format('Y-m').'-'.(1 + DB::table('signing_keys')->count());
        $id = DB::table('signing_keys')->insertGetId(['kid' => $kid, 'public_key' => self::b64(sodium_crypto_sign_publickey($pair)), 'secret_enc' => Crypt::encryptString(self::b64(sodium_crypto_sign_secretkey($pair))), 'status' => 'active', 'created_at' => now()]);

        return DB::table('signing_keys')->find($id);
    }

    public static function secret(object $key): string
    {
        return self::unb64(Crypt::decryptString($key->secret_enc));
    }

    /** The public keys, for the stable address of IR-20. */
    public static function jwks(): array
    {
        return ['keys' => DB::table('signing_keys')->orderBy('id')->get()->map(fn ($k) => ['kty' => 'OKP', 'crv' => 'Ed25519', 'x' => $k->public_key, 'kid' => $k->kid, 'use' => 'sig', 'alg' => 'EdDSA'])->all()];
    }
}
