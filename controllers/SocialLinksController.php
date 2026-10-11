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

        $config = SocialLinksConfig::get();
        $this->data['admin']['links'] = $config['links'];
        $this->data['admin']['icons'] = $config['icons'];
        $this->data['admin']['iconPattern'] = $config['iconPattern'];
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

        $config = SocialLinksConfig::get();
        $current = $config['links'];
        $errors = [];
        $links = [];
        $icons = [];

        foreach (SocialLinksConfig::NETWORKS as $network => $label) {
            $links[$network] = $this->readUrl(
                network: $network,
                label: $label,
                current: $current[$network],
                errors: $errors,
            );
        }

        foreach (SocialLinksConfig::NETWORKS as $network => $label) {
            $icons[$network] = $this->readIcon(field: "icon_{$network}", label: $label, current: $config['icons'][$network], errors: $errors);
        }

        $iconPattern = $this->readIcon(field: 'icon_pattern', label: 'Icon class pattern', current: $config['iconPattern'], errors: $errors, allowPlaceholder: true);

        $this->saveConfig(links: $links, icons: $icons, iconPattern: $iconPattern);

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

    /**
     * Icon classes are written into a class attribute, so only class-name characters are allowed.
     */
    private function readIcon(string $field, string $label, string $current, array &$errors, bool $allowPlaceholder = false): string
    {
        $value = \trim((string) ($_POST[$field] ?? ''));
        $isEmpty = $value === '';
        if ($isEmpty) return '';

        $allowed = $allowPlaceholder ? '/^[A-Za-z0-9_\- {}]+$/' : '/^[A-Za-z0-9_\- ]+$/';
        $isValid = (bool) \preg_match($allowed, $value);
        $placeholderOk = !$allowPlaceholder || \substr_count($value, '{') === \substr_count($value, '{network}');
        if ($isValid && $placeholderOk) return $value;

        $errors[] = "{$label} may only hold class names (letters, numbers, - and _)" . ($allowPlaceholder ? ' and {network}' : '') . ' - kept the previous value.';
        return $current;
    }

    private function saveConfig(array $links, array $icons, string $iconPattern): void
    {
        $dir = \dirname(self::CONFIG_FILE_PATH);
        $dirExists = \is_dir($dir);
        if (!$dirExists) \mkdir(directory: $dir, permissions: 0755, recursive: true);

        $json = \json_encode(
            value: ['socialLinks' => ['links' => $links, 'iconPattern' => $iconPattern, 'icons' => $icons]],
            flags: JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );

        \file_put_contents(self::CONFIG_FILE_PATH, "<?php exit(); ?>\n" . $json . "\n");
    }
}
