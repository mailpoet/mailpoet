<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Templates;

use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor;
use MailPoet\EmailEditor\Integrations\MailPoet\Templates\Library\Newsletter;
use MailPoet\Util\CdnAssetUrl;
use MailPoet\WP\Functions as WPFunctions;

class TemplatesController {
  private string $templatePrefix = 'mailpoet';
  private WPFunctions $wp;
  private CdnAssetUrl $cdnAssetUrl;

  public function __construct(
    WPFunctions $wp,
    CdnAssetUrl $cdnAssetUrl
  ) {
    $this->wp = $wp;
    $this->cdnAssetUrl = $cdnAssetUrl;
  }

  public function initialize() {
    $this->wp->addAction('mailpoet_email_editor_register_templates', [$this, 'registerTemplates'], 10, 0);
  }

  public function registerTemplates() {
    $newsletter = new Newsletter($this->cdnAssetUrl);
    $templateName = $this->templatePrefix . '//' . $newsletter->getSlug();

    if (\WP_Block_Templates_Registry::get_instance()->is_registered($templateName)) {
      // skip registration if the template was already registered.
      return;
    }

    register_block_template(
      $templateName,
      [
        'title' => $newsletter->getTitle(),
        'description' => $newsletter->getDescription(),
        'content' => $newsletter->getContent(),
        'post_types' => [EmailEditor::MAILPOET_EMAIL_POST_TYPE],
      ]
    );
  }
}
