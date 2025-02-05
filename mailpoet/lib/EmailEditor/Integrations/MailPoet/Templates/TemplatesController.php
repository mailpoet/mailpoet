<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Templates;

use MailPoet\EmailEditor\Engine\Templates\Template;
use MailPoet\EmailEditor\Engine\Templates\Templates_Registry;
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
    $this->wp->addFilter('mailpoet_email_editor_register_templates', [$this, 'registerTemplates'], 10, 1);
  }

  public function registerTemplates(Templates_Registry $templatesRegistry): Templates_Registry {
    $newsletter = new Newsletter($this->cdnAssetUrl);

    $template = new Template(
      $this->templatePrefix,
      $newsletter->getSlug(),
      $newsletter->getTitle(),
      $newsletter->getDescription(),
      $newsletter->getContent(),
      [EmailEditor::MAILPOET_EMAIL_POST_TYPE]
    );
    $templatesRegistry->register($template);

    return $templatesRegistry;
  }
}
