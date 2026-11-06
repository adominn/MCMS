<?php

declare(strict_types=1);

namespace App\Modules\Credentials;

use Illuminate\Support\Facades\DB;

// The signed Digital Credential: a W3C VC-JWT (EdDSA). The Data Integrity proof eddsa-rdfc-2022 was tested first and has no usable PHP library (SDD DA, README).
final class Jwt
{
    public static function sign(array $payload, object $key): string
    {
        $head = KeyStore::b64(json_encode(['alg' => 'EdDSA', 'typ' => 'JWT', 'kid' => $key->kid]));
        $body = KeyStore::b64(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return "$head.$body.".KeyStore::b64(sodium_crypto_sign_detached("$head.$body", KeyStore::secret($key)));
    }

    /** @return array{ok: bool, payload: ?array, kid: ?string, error: ?string} */
    public static function verify(string $jwt): array
    {
        $jwt = trim($jwt);
        $p = explode('.', $jwt);
        $bad = fn (string $e) => ['ok' => false, 'payload' => null, 'kid' => null, 'error' => $e];
        if (count($p) !== 3 || strlen($jwt) > 200000) {
            return $bad('This is not a signed credential file.');
        }
        $head = json_decode(KeyStore::unb64($p[0]), true);
        $payload = json_decode(KeyStore::unb64($p[1]), true);
        if (! is_array($head) || ! is_array($payload) || ($head['alg'] ?? '') !== 'EdDSA' || ! isset($head['kid'])) {
            return $bad('This is not a signed credential file.');
        }
        $key = DB::table('signing_keys')->where('kid', (string) $head['kid'])->first();
        $sig = KeyStore::unb64($p[2]);
        if (! $key || strlen($sig) !== SODIUM_CRYPTO_SIGN_BYTES || ! sodium_crypto_sign_verify_detached($sig, "$p[0].$p[1]", KeyStore::unb64($key->public_key))) {
            return $bad('The signature does not match the issuer key: the file was changed or was not issued by UTeM.');
        }

        return ['ok' => true, 'payload' => $payload, 'kid' => $head['kid'], 'error' => null];
    }
}
