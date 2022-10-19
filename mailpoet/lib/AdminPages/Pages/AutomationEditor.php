<?php declare(strict_types=1);

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Mappers\WorkflowMapper;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;
use MailPoet\Form\AssetsController;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice as WPNotice;

class AutomationEditor {
  /** @var AssetsController */
  private $assetsController;

  /** @var WorkflowMapper */
  private $workflowMapper;

  /** @var WorkflowStorage */
  private $workflowStorage;

  /** @var PageRenderer */
  private $pageRenderer;

  /** @var Registry */
  private $registry;

  /** @var SegmentsRepository  */
  private $segmentsRepository;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    AssetsController $assetsController,
    WorkflowMapper $workflowMapper,
    WorkflowStorage $workflowStorage,
    PageRenderer $pageRenderer,
    Registry $registry,
    SegmentsRepository $segmentsRepository,
    WPFunctions $wp
  ) {
    $this->assetsController = $assetsController;
    $this->workflowMapper = $workflowMapper;
    $this->workflowStorage = $workflowStorage;
    $this->pageRenderer = $pageRenderer;
    $this->registry = $registry;
    $this->segmentsRepository = $segmentsRepository;
    $this->wp = $wp;
  }

  public function render() {
    $this->assetsController->setupAutomationEditorDependencies();

    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

    $this->wp->doAction(Hooks::EDITOR_BEFORE_LOAD, (int)$id);

    $workflow = $id ? $this->workflowStorage->getWorkflow($id) : null;
    if (!$workflow) {
      $notice = new WPNotice(
        WPNotice::TYPE_ERROR,
        __('Workflow not found.', 'mailpoet')
      );
      $notice->displayWPNotice();
      $this->pageRenderer->displayPage('blank.html');
      return;
    }

    if ($workflow->getStatus() === Workflow::STATUS_TRASH) {
      $this->wp->wpSafeRedirect($this->wp->adminUrl('admin.php?page=mailpoet-automation&status=trash'));
      exit();
    }

    $segments = [];
    foreach ($this->segmentsRepository->findAll() as $segment) {
      $segments[] = ['id' => $segment->getId(), 'name' => $segment->getName(), 'type' => $segment->getType()];
    }
    $roles = new \WP_Roles();
    $this->pageRenderer->displayPage('automation/editor.html', [
      'context' => $this->buildContext(),
      'workflow' => $this->workflowMapper->buildWorkflow($workflow),
      'sub_menu' => 'mailpoet-automation',
      'api' => [
        'root' => rtrim($this->wp->escUrlRaw($this->wp->restUrl()), '/'),
        'nonce' => $this->wp->wpCreateNonce('wp_rest'),
      ],
      'user_roles' => $roles->get_names(),
      'segments' => $segments,
    ]);
  }

  private function buildContext(): array {
    $steps = [];
    foreach ($this->registry->getSteps() as $key => $step) {
      $steps[$key] = [
        'key' => $step->getKey(),
        'name' => $step->getName(),
        'args_schema' => $step->getArgsSchema()->toArray(),
      ];
    }
    return ['steps' => $steps];
  }
}
