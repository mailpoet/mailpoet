<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Templates;

use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor;
use MailPoet\WP\Functions as WPFunctions;

class TemplatesController {
  private string $templatePrefix = 'mailpoet';
  private WPFunctions $wp;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  public function initialize() {
    $this->wp->addAction('mailpoet_email_editor_register_templates', [$this, 'registerTemplates'], 10, 0);
  }

  public function registerTemplates() {
    $newsletter = [
      'title' => __('Newsletter', 'mailpoet'),
      'description' => __('A basic template with header and footer.', 'mailpoet'),
      'slug' => 'newsletter',
      'filename' => 'newsletter.html',
    ];
    register_block_template(
      $this->templatePrefix . '//' . $newsletter['slug'],
      [
        'title' => $newsletter['title'],
        'description' => $newsletter['description'],
        'content' => (string)file_get_contents(__DIR__ . '/' . $newsletter['filename']),
        'post_types' => [EmailEditor::MAILPOET_EMAIL_POST_TYPE],
      ]
    );
  }
}
