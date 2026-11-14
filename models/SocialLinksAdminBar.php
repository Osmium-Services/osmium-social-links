<?php

declare(strict_types=1);

namespace Osmium\Services\OsmiumSocialLinks\Models;

/**
 * What the social links service tells the front-end staff bar: that the page printed the links (adminbar.context)
 * and the admin link that goes with it (adminbar.links). Themes print the links through the service's block, which
 * notes it in OsmiumSocialLinksConfig; core asks after the page is built, so the answer is current. A supporting link
 * (order 90): it sits after the page's own and any main service link. Nothing printed means nothing announced.
 */
class SocialLinksAdminBar
{
    /**
     * @param array<string, mixed> $payload the finished page's view data (unused: the block notes itself)
     * @return string|null "social-links:all" when the page printed the links, null otherwise
     */
    public static function context(array $payload): ?string
    {
        return OsmiumSocialLinksConfig::wasShown() ? 'social-links:all' : null;
    }

    /**
     * @param array{context?: string, role?: string} $payload
     * @return array<int, array<string, mixed>>|null
     */
    public static function links(array $payload): ?array
    {
        $isContext = ($payload['context'] ?? '') === 'social-links:all';
        if (!$isContext) return null;

        return [['label' => 'Edit social links', 'icon' => 'bx-share-alt', 'path' => 'social-links/', 'primary' => true, 'order' => 90]];
    }
}
