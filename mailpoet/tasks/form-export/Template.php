<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Form\Templates\Templates;

use MailPoet\Form\Templates\FormTemplate;

class Template extends FormTemplate {
  const ID = 'TEMPLATE_ID'; //@todo Set proper value

  /** @var string */
  protected $assetsDirectory = 'TEMPLATE_ASSETS_DIR'; //@todo Set proper value

  public function getName(): string {
    return _x('TEMPLATE_NAME', 'Form template name', 'mailpoet'); //@todo Set proper value
  }

  public function getThumbnailUrl(): string {
    return ''; //@todo Add thumbnail
  }

  public function getBody(): array {
    return TEMPLATE_BODY;
  }

  public function getSettings(): array {
    return TEMPLATE_SETTINGS;
  }

  public function getStyles(): string {
    return <<<EOL
TEMPLATE_STYLES
EOL;
  }
}
