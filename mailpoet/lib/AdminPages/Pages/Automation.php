<?php declare(strict_types = 1);

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Automation\Engine\Data\AutomationTemplate;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Engine\Storage\AutomationTemplateStorage;
use MailPoet\Form\AssetsController;
use MailPoet\WP\Functions as WPFunctions;

class Automation {
  /** @var AssetsController */
  private $assetsController;

  /** @var PageRenderer */
  private $pageRenderer;

  /** @var WPFunctions */
  private $wp;

  /** @var AutomationStorage */
  private $automationStorage;

  /** @var AutomationTemplateStorage  */
  private $templateStorage;

  public function __construct(
    AssetsController $assetsController,
    PageRenderer $pageRenderer,
    WPFunctions $wp,
    AutomationStorage $automationStorage,
    AutomationTemplateStorage $templateStorage
  ) {
    $this->assetsController = $assetsController;
    $this->pageRenderer = $pageRenderer;
    $this->wp = $wp;
    $this->automationStorage = $automationStorage;
    $this->templateStorage = $templateStorage;
  }

  public function render() {
    $this->assetsController->setupAutomationListingDependencies();
    $this->pageRenderer->displayPage('automation.html', [
      'locale_full' => $this->wp->getLocale(),
      'api' => [
        'root' => rtrim($this->wp->escUrlRaw($this->wp->restUrl()), '/'),
        'nonce' => $this->wp->wpCreateNonce('wp_rest'),
      ],
      'automationCount' => $this->automationStorage->getAutomationCount(),
      'templates' => array_map(
        function(AutomationTemplate $template): array {
          return $template->toArray();
        },
        $this->templateStorage->getTemplates()
      ),
    ]);
  }
}
