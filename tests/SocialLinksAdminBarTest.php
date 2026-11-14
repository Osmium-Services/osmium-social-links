<?php

declare(strict_types=1);

namespace Tests\Services\SocialLinks;

use Osmium\Services\OsmiumSocialLinks\Models\OsmiumSocialLinksConfig;
use Osmium\Services\OsmiumSocialLinks\Models\SocialLinksAdminBar;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../models/OsmiumSocialLinksConfig.php';
require_once __DIR__ . '/../models/SocialLinksAdminBar.php';

/**
 * The staff bar's social links answers: a page that printed the links announces it and gets a "Social links" link.
 */
final class SocialLinksAdminBarTest extends TestCase
{
    protected function setUp(): void
    {
        OsmiumSocialLinksConfig::forgetShown();
    }

    public function test_a_page_that_printed_no_links_announces_nothing(): void
    {
        $this->assertNull(SocialLinksAdminBar::context(['data' => []]));
    }

    public function test_a_page_that_printed_the_links_announces_it(): void
    {
        OsmiumSocialLinksConfig::noteShown();

        $this->assertSame('social-links:all', SocialLinksAdminBar::context(['data' => []]));
    }

    public function test_the_link_opens_the_social_links_admin_with_the_services_own_icon(): void
    {
        $links = SocialLinksAdminBar::links(['context' => 'social-links:all']);

        $this->assertSame('social-links/', $links[0]['path']);
        $this->assertSame('Edit social links', $links[0]['label']);
        $this->assertSame('bx-share-alt', $links[0]['icon']);
        $this->assertSame(90, $links[0]['order']); // Supporting: after the page's own and any main service link
    }

    public function test_other_contexts_get_no_links(): void
    {
        $this->assertNull(SocialLinksAdminBar::links(['context' => 'product:5']));
        $this->assertNull(SocialLinksAdminBar::links([]));
    }
}
