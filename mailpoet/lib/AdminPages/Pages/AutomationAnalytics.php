<?php declare(strict_types = 1);

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Automation\Engine\Mappers\AutomationMapper;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Form\AssetsController;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice as WPNotice;

class AutomationAnalytics {

  /** @var AssetsController */
  private $assetsController;

  /** @var PageRenderer */
  private $pageRenderer;

  /** @var AutomationStorage */
  private $automationStorage;

  /** @var AutomationMapper */
  private $automationMapper;

  /** @var Registry */
  private $registry;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    AssetsController $assetsController,
    PageRenderer $pageRenderer,
    AutomationStorage $automationStorage,
    AutomationMapper $automationMapper,
    Registry $registry,
    WPFunctions $wp
  ) {
    $this->assetsController = $assetsController;
    $this->pageRenderer = $pageRenderer;
    $this->automationStorage = $automationStorage;
    $this->automationMapper = $automationMapper;
    $this->registry = $registry;
    $this->wp = $wp;
  }

  public function render() {
    $this->assetsController->setupAutomationAnalyticsDependencies();

    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
    $automation = $id ? $this->automationStorage->getAutomation($id) : null;
    if (!$automation) {
      $notice = new WPNotice(
        WPNotice::TYPE_ERROR,
        __('Automation not found.', 'mailpoet')
      );
      $notice->displayWPNotice();
      $this->pageRenderer->displayPage('blank.html');
      return;
    }

    $this->pageRenderer->displayPage('automation/analytics.html', [
      'registry' => $this->buildRegistry(),
      'context' => $this->buildContext(),
      'automation' => $this->automationMapper->buildAutomation($automation),
      'locale_full' => $this->wp->getLocale(),
      'api' => [
        'root' => rtrim($this->wp->escUrlRaw($this->wp->restUrl()), '/'),
        'nonce' => $this->wp->wpCreateNonce('wp_rest'),
      ],
      'jsonapi' => [
        'root' => rtrim($this->wp->escUrlRaw(admin_url('admin-ajax.php')), '/'),
      ],
    ]);
  }

  private function buildRegistry(): array {
    $steps = [];
    foreach ($this->registry->getSteps() as $key => $step) {
      $steps[$key] = [
        'key' => $step->getKey(),
        'name' => $step->getName(),
        'args_schema' => $step->getArgsSchema()->toArray(),
      ];
    }

    return [
      'steps' => $steps,
    ];
  }

  private function buildContext(): array {
    $data = [];
    foreach ($this->registry->getContextFactories() as $key => $factory) {
      $data[$key] = $factory();
    }
    return $data;
  }
}
