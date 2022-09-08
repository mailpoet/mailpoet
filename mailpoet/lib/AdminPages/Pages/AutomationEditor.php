<?php declare(strict_types=1);

namespace MailPoet\AdminPages\Pages;

use DateTimeImmutable;
use MailPoet\AdminPages\PageRenderer;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice as WPNotice;

class AutomationEditor {
  /** @var WorkflowStorage */
  private $workflowStorage;

  /** @var PageRenderer */
  private $pageRenderer;

  /** @var Registry */
  private $registry;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    WorkflowStorage $workflowStorage,
    PageRenderer $pageRenderer,
    Registry $registry,
    WPFunctions $wp
  ) {
    $this->workflowStorage = $workflowStorage;
    $this->pageRenderer = $pageRenderer;
    $this->registry = $registry;
    $this->wp = $wp;
  }

  public function render() {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

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

    $this->pageRenderer->displayPage('automation/editor.html', [
      'context' => $this->buildContext(),
      'workflow' => $this->buildWorkflow($workflow),
      'sub_menu' => 'mailpoet-automation',
      'api' => [
        'root' => rtrim($this->wp->escUrlRaw($this->wp->restUrl()), '/'),
        'nonce' => $this->wp->wpCreateNonce('wp_rest'),
      ],
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

  private function buildWorkflow(Workflow $workflow): array {
    return [
      'id' => $workflow->getId(),
      'name' => $workflow->getName(),
      'status' => $workflow->getStatus(),
      'created_at' => $workflow->getCreatedAt()->format(DateTimeImmutable::W3C),
      'updated_at' => $workflow->getUpdatedAt()->format(DateTimeImmutable::W3C),
      'activated_at' => $workflow->getActivatedAt() ? $workflow->getActivatedAt()->format(DateTimeImmutable::W3C) : null,
      'author' => [
        'id' => $workflow->getAuthor()->ID,
        'name' => $workflow->getAuthor()->display_name,
      ],
      'steps' => array_map(function (Step $step) {
        return [
          'id' => $step->getId(),
          'type' => $step->getType(),
          'key' => $step->getKey(),
          'args' => $step->getArgs(),
          'next_steps' => array_map(function (NextStep $nextStep) {
            return $nextStep->toArray();
          }, $step->getNextSteps()),
        ];
      }, $workflow->getSteps()),
    ];
  }
}
