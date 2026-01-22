<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Templates;

use Automattic\WooCommerce\EmailEditor\Engine\Templates\Template;
use Automattic\WooCommerce\EmailEditor\Engine\Templates\Templates_Registry;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor;
use MailPoet\EmailEditor\Integrations\MailPoet\Templates\Library\Newsletter;
use MailPoet\Util\CdnAssetUrl;
use MailPoet\WP\Functions as WPFunctions;

class TemplatesController {
  private string $templatePrefix = 'mailpoet';
  private ?string $defaultTemplateSlug = null;
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
    $this->wp->addFilter('woocommerce_email_editor_register_templates', [$this, 'registerTemplates'], 10, 1);
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

    // Store the first registered template as the default
    if ($this->defaultTemplateSlug === null) {
      $this->defaultTemplateSlug = $newsletter->getSlug();
    }

    return $templatesRegistry;
  }

  /**
   * Get the default template slug for new emails.
   *
   * @return string The template slug (e.g., 'newsletter')
   */
  public function getDefaultTemplateSlug(): string {
    // If templates haven't been registered yet, create the Newsletter to get its slug
    if ($this->defaultTemplateSlug === null) {
      $newsletter = new Newsletter($this->cdnAssetUrl);
      $this->defaultTemplateSlug = $newsletter->getSlug();
    }
    return $this->defaultTemplateSlug;
  }
}
