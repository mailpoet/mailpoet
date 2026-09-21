<?php declare(strict_types = 1);

namespace MailPoet\Migrations\App;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Migrator\AppMigration;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WooCommerce\TransactionalEmails;
use MailPoet\WP\Functions as WPFunctions;
use Throwable;

/**
 * Before this fix, {store_address} and {store_email} were never resolved, so any
 * WC transactional template created earlier has them baked into its footer text
 * as raw, unresolved placeholders. Re-resolves them (and links {woocommerce}) in
 * the existing saved template so stores that already hit the bug also see the fix.
 *
 * WooCommerce isn't necessarily ready yet when this runs: on a plugin update,
 * Initializer::maybeRunActivator hooks 'init' at PHP_INT_MIN, but WC::init() sets up
 * WC()->countries (needed by Helper::wcGetStoreAddress()) on 'init' priority 0, and
 * WC_Emails itself isn't loaded at all when WooCommerce is deactivated. Both would
 * otherwise fatal here, on every request, for exactly the sites this migration targets.
 * So: bail out entirely if WooCommerce isn't active, and if 'woocommerce_init' (fired at
 * the end of WC::init(), still ahead of us on this same request) hasn't happened yet,
 * defer the actual work to it instead of running immediately.
 */
class Migration_20260826_120000_App extends AppMigration {
  public function run(): void {
    $woocommerceHelper = $this->container->get(WooCommerceHelper::class);
    if (!$woocommerceHelper->isWooCommerceActive()) {
      return;
    }

    $wp = $this->container->get(WPFunctions::class);
    if ($wp->didAction('woocommerce_init')) {
      $this->resolveExistingTemplate();
      return;
    }

    $wp->addAction('woocommerce_init', function () {
      try {
        $this->resolveExistingTemplate();
      } catch (Throwable $e) {
        // This runs outside the migrator's own try/catch (the migration already returned
        // and was marked completed), so a failure here must not escape as a fatal, but it
        // must leave a trace: the saved template would otherwise stay unresolved silently.
        $this->container->get(LoggerFactory::class)
          ->getLogger(LoggerFactory::TOPIC_MIGRATIONS)
          ->error(sprintf('%s: %s', static::class, $e->getMessage()));
      }
    });
  }

  private function resolveExistingTemplate(): void {
    $settings = $this->container->get(SettingsController::class);
    $newsletterId = $settings->get(TransactionalEmails::SETTING_EMAIL_ID);
    if (!$newsletterId) {
      return;
    }

    $newslettersRepository = $this->container->get(NewslettersRepository::class);
    $newsletter = $newslettersRepository->findOneById((int)$newsletterId);
    if (!$newsletter instanceof NewsletterEntity) {
      return;
    }

    $body = $newsletter->getBody();
    if (!is_array($body)) {
      return;
    }

    $transactionalEmails = $this->container->get(TransactionalEmails::class);
    $changed = false;
    $body = $this->resolvePlaceholdersInBlocks($body, $transactionalEmails, $changed);
    if (!$changed) {
      return;
    }

    $newsletter->setBody($body);
    $this->entityManager->flush();
  }

  private function resolvePlaceholdersInBlocks(array $node, TransactionalEmails $transactionalEmails, bool &$changed): array {
    foreach ($node as $key => $value) {
      if (is_array($value)) {
        $node[$key] = $this->resolvePlaceholdersInBlocks($value, $transactionalEmails, $changed);
        continue;
      }
      if ($key !== 'text' || !is_string($value) || strpos($value, '{') === false) {
        continue;
      }
      if (!$this->containsRawPlaceholder($value)) {
        continue;
      }
      $node[$key] = $transactionalEmails->resolvePlaceholdersInFooterText($value);
      $changed = true;
    }
    return $node;
  }

  private function containsRawPlaceholder(string $text): bool {
    foreach (TransactionalEmails::FOOTER_PLACEHOLDER_TOKENS as $token) {
      if (strpos($text, $token) !== false) {
        return true;
      }
    }
    return false;
  }
}
