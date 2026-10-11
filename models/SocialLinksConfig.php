<?php

declare(strict_types=1);

namespace Osmium\Services\SocialLinks\Models;

/**
 * Loads this service's own settings file (app/config/services/social-links.json.php).
 *
 * Core passes get() to every theme block as $socialLinks (see ServiceLoader::serviceConfigContext),
 * so another service can read the saved URLs without depending on this one.
 */
class SocialLinksConfig
{
    /** @var array<string, string> network key => label */
    public const NETWORKS = [
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'x' => 'X',
        'linkedin' => 'LinkedIn',
        'youtube' => 'YouTube',
        'tiktok' => 'TikTok',
        'pinterest' => 'Pinterest',
    ];

    private static ?array $config = null;
    private static string $configPath = 'app/config/services/social-links.json.php';

    public static function get(): array
    {
        $configLoaded = self::$config !== null;
        if ($configLoaded) return self::$config;

        self::$config = self::defaults();

        $configExists = \file_exists(self::$configPath);
        if (!$configExists) return self::$config;

        $content = \file_get_contents(self::$configPath);
        $jsonStart = \strpos(haystack: $content, needle: '{');
        $noJsonFound = $jsonStart === false;
        if ($noJsonFound) return self::$config;

        $decoded = \json_decode(\substr(string: $content, offset: $jsonStart), associative: true);
        $saved = \is_array($decoded) ? ($decoded['socialLinks']['links'] ?? []) : [];

        foreach (self::NETWORKS as $network => $label) {
            $url = \is_array($saved) ? \trim((string) ($saved[$network] ?? '')) : '';
            self::$config['links'][$network] = $url;
        }

        return self::$config;
    }

    /**
     * Saved URLs only, in network order: network key => URL.
     */
    public static function filled(array $config): array
    {
        return \array_filter($config['links'] ?? [], static fn($url) => $url !== '');
    }

    public static function clearCache(): void
    {
        self::$config = null;
    }

    public static function defaults(): array
    {
        return ['links' => \array_fill_keys(\array_keys(self::NETWORKS), '')];
    }
}
