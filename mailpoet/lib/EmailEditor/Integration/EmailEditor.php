<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integration;

use MailPoet\EmailEditor\Core\EmailEditor as CoreEmailEditor;
use MailPoet\Features\FeaturesController;
use MailPoet\WP\Functions as WPFunctions;

class EmailEditor {
  /** @var \MailPoet\EmailEditor\Core\EmailEditor */
  private $coreEmailEditor;

  /** @var WPFunctions */
  private $wp;

  /** @var FeaturesController */
  private $featuresController;

  public function __construct(
    CoreEmailEditor $coreEmailEditor,
    WPFunctions $wp,
    FeaturesController $featuresController
  ) {
    $this->coreEmailEditor = $coreEmailEditor;
    $this->wp = $wp;
    $this->featuresController = $featuresController;
  }

  public function initialize(): void {
    if (!$this->featuresController->isSupported(FeaturesController::GUTENBERG_EMAIL_EDITOR)) {
      return;
    }
    $this->wp->addFilter('mailpoet_email_editor_post_types', [$this, 'addEmailPostType']);
    $this->coreEmailEditor->initialize();
  }

  public function addEmailPostType(array $postTypes): array {
    $postTypes[] = [
      'name' => 'mailpoet_email',
      'args' => [
        'labels' => [
          'name' => __('Emails', 'mailpoet'),
          'singular_name' => __('Email', 'mailpoet'),
        ],
        'rewrite' => ['slug' => 'mailpoet_email'],
      ],
    ];
    return $postTypes;
  }
}
