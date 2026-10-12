<?php

declare(strict_types=1);

namespace Osmium\Services\OsmiumSocialLinks\Models;

/**
 * Loads this service's own settings file (app/config/services/osmium-social-links.json.php).
 *
 * Core passes get() to every theme block as $osmiumSocialLinks (see ServiceLoader::serviceConfigContext),
 * so another service can read the saved URLs without depending on this one.
 */
class OsmiumSocialLinksConfig
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
    private static string $configPath = 'app/config/services/osmium-social-links.json.php';

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
        $saved = \is_array($decoded) ? ($decoded['socialLinks'] ?? []) : [];
        $savedLinks = \is_array($saved['links'] ?? null) ? $saved['links'] : [];
        $savedIcons = \is_array($saved['icons'] ?? null) ? $saved['icons'] : [];

        foreach (self::NETWORKS as $network => $label) {
            self::$config['links'][$network] = \trim((string) ($savedLinks[$network] ?? ''));
            self::$config['icons'][$network] = \trim((string) ($savedIcons[$network] ?? ''));
        }
        self::$config['iconPattern'] = \trim((string) ($saved['iconPattern'] ?? ''));

        return self::$config;
    }

    /**
     * Saved URLs only, in network order: network key => URL.
     */
    public static function filled(array $config): array
    {
        return \array_filter($config['links'] ?? [], static fn($url) => $url !== '');
    }

    /**
     * The icon class for a network: its own override, else the site-wide pattern with {network}
     * replaced by the network key. Empty means no icon, so the link prints as plain text.
     */
    public static function iconClass(array $config, string $network): string
    {
        $override = \trim((string) ($config['icons'][$network] ?? ''));
        if ($override !== '') return $override;

        $pattern = \trim((string) ($config['iconPattern'] ?? ''));
        $hasPattern = $pattern !== '';

        return $hasPattern ? \str_replace('{network}', $network, $pattern) : '';
    }

    public static function clearCache(): void
    {
        self::$config = null;
    }

    public static function defaults(): array
    {
        $empty = \array_fill_keys(\array_keys(self::NETWORKS), '');

        return ['links' => $empty, 'iconPattern' => '', 'icons' => $empty];
    }
}
