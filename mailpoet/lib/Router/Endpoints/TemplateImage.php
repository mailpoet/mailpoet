<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Router\Endpoints;

use MailPoet\Config\AccessControl;
use MailPoet\NewsletterTemplates\TemplateImageLoader;

class TemplateImage {
  const ENDPOINT = 'template_image';
  const ACTION_GET_EXTERNAL_IMAGE = 'getExternalImage';

  /** @var TemplateImageLoader */
  private $templateImageLoader;

  public $allowedActions = [self::ACTION_GET_EXTERNAL_IMAGE];
  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_EMAILS,
  ];

  public function __construct(
    TemplateImageLoader $templateImageLoader
  ) {
    $this->templateImageLoader = $templateImageLoader;
  }

  public function getExternalImage($data = [], $return = false) {
    if (empty($_GET['url'])) {
      return false;
    }
    $result = $this->templateImageLoader->loadExternalImage(
      sanitize_text_field(wp_unslash($_GET['url']))
    );
    if ($return) {
      return $result;
    }
    exit;
  }
}
