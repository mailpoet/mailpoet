<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\NewsletterSaveController;
use MailPoet\WP\Functions as WPFunctions;
use WP_CLI;

class Cli {
  private const TEMPLATES = [
    [
      'name' => 'testing-email-with-core-blocks',
      'subject' => 'Hey [subscriber:firstname | default:subscriber], we test new email editor!',
      'preheader' => 'This is a testing email containing core blocks with different configurations.',
    ],
  ];

  private NewsletterSaveController $newsletterSaveController;

  private WPFunctions $wp;

  public function __construct(
    NewsletterSaveController $newsletterSaveController,
    WPFunctions $wp
  ) {
    $this->newsletterSaveController = $newsletterSaveController;
    $this->wp = $wp;
  }

  public function initialize(): void {
    if (!class_exists(WP_CLI::class)) {
      return;
    }

    WP_CLI::add_command('mailpoet email-editor create-templates', [$this, 'createTemplates'], [
      'shortdesc' => 'Create MailPoet email editor templates',
    ]);

    // Deprecated colon-named alias kept for backwards compatibility. Remove in a future release (STOMAIL-8177).
    $this->registerDeprecatedAlias('mailpoet:email-editor:create-templates', 'mailpoet email-editor create-templates', [$this, 'createTemplates']);
  }

  private function registerDeprecatedAlias(string $oldName, string $newName, callable $handler): void {
    WP_CLI::add_command($oldName, function () use ($oldName, $newName, $handler): void {
      WP_CLI::warning(sprintf('`wp %s` is deprecated and will be removed in a future release. Use `wp %s` instead.', $oldName, $newName));
      $handler();
    }, [
      'shortdesc' => sprintf('Deprecated. Use `wp %s` instead', $newName),
    ]);
  }

  public function createTemplates(): void {
    WP_CLI::log("Starting creating MailPoet email editor templates.");
    foreach (self::TEMPLATES as $template) {
      $content = file_get_contents(__DIR__ . "/templates/{$template['name']}.html");
      $newsletter = $this->newsletterSaveController->save([
        'subject' => $template['subject'],
        'preheader' => $template['preheader'],
        'type' => NewsletterEntity::TYPE_STANDARD,
        'new_editor' => true,
      ]);

      $wpPost = $newsletter->getWpPost();
      if (!$wpPost) {
        WP_CLI::error("Failed to create a post for the email template {$template['name']}.");
        return;
      }

      $this->wp->wpUpdatePost([
        'ID' => $wpPost->getId(),
        'post_title' => $this->getTemplateName($template['name']),
        'post_content' => $content,
      ]);
      WP_CLI::log("Created a new email template {$template['name']}.");
    }
    WP_CLI::log('Finished creating MailPoet email editor templates.');
  }

  private function getTemplateName(string $templateName): string {
    $name = str_replace('-', ' ', $templateName);
    return ucwords($name);
  }
}
