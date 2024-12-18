<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Templates;

use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor;
use MailPoet\EmailEditor\Integrations\MailPoet\Templates\Library\Newsletter;
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
    $newsletter = new Newsletter();
    register_block_template(
      $this->templatePrefix . '//' . $newsletter->getSlug(),
      [
        'title' => $newsletter->getTitle(),
        'description' => $newsletter->getDescription(),
        'content' => $newsletter->getContent(),
        'post_types' => [EmailEditor::MAILPOET_EMAIL_POST_TYPE],
      ]
    );
  }
}
