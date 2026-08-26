<?php declare(strict_types = 1);

namespace MailPoet\Migrations\App;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Migrator\AppMigration;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\WooCommerce\TransactionalEmails;

/**
 * Before this fix, {store_address} and {store_email} were never resolved, so any
 * WC transactional template created earlier has them baked into its footer text
 * as raw, unresolved placeholders. Re-resolves them (and links {woocommerce}) in
 * the existing saved template so stores that already hit the bug also see the fix.
 */
class Migration_20260826_120000_App extends AppMigration {
  const RAW_PLACEHOLDER_TOKENS = [
    '{site_title}',
    '{site_address}',
    '{site_url}',
    '{store_address}',
    '{store_email}',
    '{woocommerce}',
    '{WooCommerce}',
  ];

  public function run(): void {
    $newslettersRepository = $this->container->get(NewslettersRepository::class);
    $newsletter = $newslettersRepository->findOneBy(['type' => NewsletterEntity::TYPE_WC_TRANSACTIONAL_EMAIL]);
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
      $node[$key] = $transactionalEmails->resolveFooterPlaceholders($value);
      $changed = true;
    }
    return $node;
  }

  private function containsRawPlaceholder(string $text): bool {
    foreach (self::RAW_PLACEHOLDER_TOKENS as $token) {
      if (strpos($text, $token) !== false) {
        return true;
      }
    }
    return false;
  }
}
