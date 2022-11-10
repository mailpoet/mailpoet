<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Automation\Engine\Data\AutomationTemplate;
use MailPoet\Automation\Engine\Storage\AutomationTemplateStorage;
use MailPoet\Form\AssetsController;
use MailPoet\WP\Functions as WPFunctions;

class AutomationTemplates {
  /** @var AssetsController */
  private $assetsController;

  /** @var PageRenderer */
  private $pageRenderer;

  /** @var AutomationTemplateStorage  */
  private $templateStorage;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    AssetsController $assetsController,
    PageRenderer $pageRenderer,
    AutomationTemplateStorage $templateStorage,
    WPFunctions $wp
  ) {
    $this->assetsController = $assetsController;
    $this->pageRenderer = $pageRenderer;
    $this->templateStorage = $templateStorage;
    $this->wp = $wp;
  }

  public function render() {
    $this->assetsController->setupAutomationTemplatesDependencies();

    $this->pageRenderer->displayPage(
      'automation/templates.html',
      [
        'sub_menu' => 'mailpoet-automation',
        'api' => [
          'root' => rtrim($this->wp->escUrlRaw($this->wp->restUrl()), '/'),
          'nonce' => $this->wp->wpCreateNonce('wp_rest'),
        ],
        'templates' => array_map(
          function(AutomationTemplate $template): array {
            return $template->toArray();
          },
          $this->templateStorage->getTemplates()
        ),
      ]
    );
  }
}
