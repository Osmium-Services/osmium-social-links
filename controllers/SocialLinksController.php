<?php

declare(strict_types=1);

namespace Osmium\Services\SocialLinks\Controllers;

use Osmium\Modules\Admin\Core\AdminController;
use Osmium\Services\SocialLinks\Models\SocialLinksConfig;

/**
 * Social links settings: a full-page form POST/redirect flow, like Cookie Consent and Schema.
 *
 * Routes:
 *   - index() → /admin/settings/social-links/  (GET shows the form, POST saves it)
 */
class SocialLinksController extends AdminController
{
    private const CONFIG_FILE_PATH = 'app/config/services/social-links.json.php';

    public function index(): void
    {
        $isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
        if ($isPost) $this->handleSubmit();

        $this->data['admin']['links'] = SocialLinksConfig::get()['links'];
        $this->data['admin']['networks'] = SocialLinksConfig::NETWORKS;
        $this->data['admin']['settingsSaved'] = $_SESSION['social_links_saved'] ?? false;
        $this->data['admin']['settingsError'] = $_SESSION['social_links_error'] ?? false;
        unset($_SESSION['social_links_saved'], $_SESSION['social_links_error']);

        $this->setView('social-links/index.phtml');
    }

    private function handleSubmit(): void
    {
        $csrfValid = $this->admin->auth->validateCsrf();
        if (!$csrfValid) {
            $_SESSION['social_links_error'] = 'Invalid form submission. Please try again.';
            $this->redirect('settings/social-links/');
        }

        $current = SocialLinksConfig::get()['links'];
        $errors = [];
        $links = [];

        foreach (SocialLinksConfig::NETWORKS as $network => $label) {
            $links[$network] = $this->readUrl(
                network: $network,
                label: $label,
                current: $current[$network],
                errors: $errors,
            );
        }

        $this->saveConfig($links);

        $this->admin->model->changelog->log(description: 'Updated social links', recordType: 'settings');

        SocialLinksConfig::clearCache();

        $_SESSION['social_links_saved'] = true;
        if ($errors) $_SESSION['social_links_error'] = \implode(' ', $errors);
        $this->redirect('settings/social-links/');
    }

    private function readUrl(string $network, string $label, string $current, array &$errors): string
    {
        $value = \trim((string) ($_POST[$network] ?? ''));
        $isEmpty = $value === '';
        if ($isEmpty) return '';

        $isHttp = (bool) \preg_match('#^https?://[^\s]+$#i', $value);
        if ($isHttp) return $value;

        $errors[] = "{$label} must be a full link starting with https:// - kept the previous value.";
        return $current;
    }

    private function saveConfig(array $links): void
    {
        $dir = \dirname(self::CONFIG_FILE_PATH);
        $dirExists = \is_dir($dir);
        if (!$dirExists) \mkdir(directory: $dir, permissions: 0755, recursive: true);

        $json = \json_encode(
            value: ['socialLinks' => ['links' => $links]],
            flags: JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );

        \file_put_contents(self::CONFIG_FILE_PATH, "<?php exit(); ?>\n" . $json . "\n");
    }
}
