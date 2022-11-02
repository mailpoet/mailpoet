<?php declare(strict_types=1);

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Automation\Engine\Data\WorkflowTemplate;
use MailPoet\Automation\Engine\Migrations\Migrator;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;
use MailPoet\Automation\Engine\Storage\WorkflowTemplateStorage;
use MailPoet\Form\AssetsController;
use MailPoet\WP\Functions as WPFunctions;

class Automation {
  /** @var AssetsController */
  private $assetsController;

  /** @var Migrator */
  private $migrator;

  /** @var PageRenderer */
  private $pageRenderer;

  /** @var WPFunctions */
  private $wp;

  /** @var WorkflowStorage */
  private $workflowStorage;

  /** @var WorkflowTemplateStorage  */
  private $templateStorage;

  public function __construct(
    AssetsController $assetsController,
    Migrator $migrator,
    PageRenderer $pageRenderer,
    WPFunctions $wp,
    WorkflowStorage $workflowStorage,
    WorkflowTemplateStorage $templateStorage
  ) {
    $this->assetsController = $assetsController;
    $this->migrator = $migrator;
    $this->pageRenderer = $pageRenderer;
    $this->wp = $wp;
    $this->workflowStorage = $workflowStorage;
    $this->templateStorage = $templateStorage;
  }

  public function render() {
    $this->assetsController->setupAutomationListingDependencies();

    if (!$this->migrator->hasSchema()) {
      $this->migrator->createSchema();
    }
    $this->pageRenderer->displayPage('automation.html', [
      'api' => [
        'root' => rtrim($this->wp->escUrlRaw($this->wp->restUrl()), '/'),
        'nonce' => $this->wp->wpCreateNonce('wp_rest'),
      ],
      'workflowCount' => $this->workflowStorage->getWorkflowCount(),
      'templates' => array_map(
        function(WorkflowTemplate $template): array {
          return $template->toArray();
        },
        $this->templateStorage->getTemplates()
      ),
    ]);
  }
}
