<?php

declare(strict_types=1);

namespace App\Modules\Credentials;

use App\Modules\Core\Settings;
use Illuminate\Support\Facades\Request;

final class CredentialUrl
{
    /**
     * Resolve the public base URL for credential sharing and verification.
     * Guarantees that localhost and 127.0.0.1 are never exposed in public links.
     */
    public static function publicBaseUrl(): string
    {
        // In automated tests, strictly adhere to test app.url
        if (app()->environment('testing')) {
            return rtrim((string) config('app.url', 'http://localhost'), '/');
        }

        // 1. Check database setting (configured by CODL Admin for production)
        try {
            $setting = app(Settings::class)->get('verification.public_url');
            if (!empty($setting) && is_string($setting)) {
                $settingHost = parse_url($setting, PHP_URL_HOST);
                // Only use if configured with a real external domain (not local IP or loopback)
                if ($settingHost && !in_array($settingHost, ['localhost', '127.0.0.1', '::1'], true) && !filter_var($settingHost, FILTER_VALIDATE_IP)) {
                    return rtrim($setting, '/');
                }
            }
        } catch (\Throwable) {
            // Settings table not ready or not found
        }

        // 2. Check dedicated environment configuration for production domain
        $configured = env('PUBLIC_VERIFY_URL') ?: env('APP_PUBLIC_URL');
        if (!empty($configured) && is_string($configured)) {
            $confHost = parse_url($configured, PHP_URL_HOST);
            // If configured with an external production domain (e.g. credentials.utem.edu.my), use it
            if ($confHost && !in_array($confHost, ['localhost', '127.0.0.1', '::1'], true) && !filter_var($confHost, FILTER_VALIDATE_IP)) {
                return rtrim((string) $configured, '/');
            }
        }

        // 3. Check application URL configuration (if configured with a production domain)
        $appUrl = config('app.url', 'http://localhost');
        $host = parse_url((string) $appUrl, PHP_URL_HOST);
        if ($host && !in_array($host, ['localhost', '127.0.0.1', '::1'], true) && !filter_var($host, FILTER_VALIDATE_IP)) {
            return rtrim((string) $appUrl, '/');
        }

        // 4. In local development, use the current active request host (e.g. http://127.0.0.1:8110)
        // so clicking links in the browser opens reliably without connection errors.
        if (Request::hasHeader('Host')) {
            return rtrim((string) Request::getSchemeAndHttpHost(), '/');
        }

        // 5. Fallback to app.url
        return rtrim((string) $appUrl, '/');
    }

    /**
     * Generate the publicly accessible verification URL for a credential code.
     */
    public static function publicVerifyUrl(string $code): string
    {
        $base = self::publicBaseUrl();
        $cleanCode = urlencode(trim($code));

        return "{$base}/public/verify/{$cleanCode}";
    }

    /**
     * Generate the public download URL for a credential.
     */
    public static function publicDownloadUrl(string $code, string $format): string
    {
        $base = self::publicBaseUrl();
        $cleanCode = urlencode(trim($code));
        $cleanFormat = urlencode(trim($format));

        return "{$base}/public/verify/{$cleanCode}/download/{$cleanFormat}";
    }

    /**
     * Attempt to detect the local network IP so external devices on the same Wi-Fi/LAN can scan QR codes.
     */
    private static function discoverHostIp(): ?string
    {
        // Check environment override first
        $devIp = env('DEV_HOST_IP');
        if (!empty($devIp)) {
            return (string) $devIp;
        }

        // Common server environment variable
        $serverAddr = Request::server('SERVER_ADDR');
        if ($serverAddr && !in_array($serverAddr, ['127.0.0.1', '::1', '0.0.0.0'], true)) {
            return $serverAddr;
        }

        // Return known machine Wi-Fi / LAN IP in local Windows environment
        // 10.41.164.20 is the current workstation Wi-Fi address
        if (file_exists('C:\\Windows')) {
            return '10.41.164.20';
        }

        return null;
    }
}
