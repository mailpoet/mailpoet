<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Migrator\DbMigration;

/**
 * In https://github.com/mailpoet/mailpoet/pull/5628 we removed Google+ social icons
 * but they were still used in templates. This migration removes them from the templates.
 */
class Migration_20241007_170437_Db extends DbMigration {
  public function run(): void {
    global $wpdb;
    $templatesTable = esc_sql($wpdb->prefix . 'mailpoet_newsletter_templates');

    if (!$this->tableExists($templatesTable)) {
      return;
    }

    $templatesWithGooglePlus = $this->connection->fetchAllAssociative(
      "SELECT id, body FROM $templatesTable WHERE body LIKE '%\"iconType\":\"google-plus\"%'"
    );
    foreach ($templatesWithGooglePlus as $template) {
      if (!is_string($template['body'])) {
        continue;
      }
      $body = json_decode($template['body'], true);
      $error = json_last_error();
      if ($error || !is_array($body)) {
        continue;
      }
      $content = &$body['content'];
      $this->removeGooglePlusIcons($content);
      $updatedBody = json_encode($body);
      if ($updatedBody === false) {
        continue;
      }
      $this->connection->update(
        $templatesTable,
        ['body' => $updatedBody],
        ['id' => $template['id']]
      );
    }
  }

  private function removeGooglePlusIcons(&$array) {
    if (!isset($array['blocks']) || !is_array($array['blocks'])) {
      return;
    }
    foreach ($array['blocks'] as &$block) {
      $this->removeGooglePlusIcons($block);
      if (!isset($block['type']) || $block['type'] !== 'social') {
        continue;
      }
      $filteredIcons = array_filter($block['icons'], function($icon) {
        return $icon['iconType'] !== 'google-plus';
      });
      $block['icons'] = array_values($filteredIcons);
    }
  }
}
