<?php

namespace MailPoet\Form\Templates\Templates;

use MailPoet\Form\Templates\FormTemplate;

class Template extends FormTemplate {
  const ID = 'TEMPLATE_ID';

  /** @var string */
  protected $assetsDirectory = 'TEMPLATE_ASSETS_DIR';

  public function getName(): string {
    return 'TEMPLATE_NAME';
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
