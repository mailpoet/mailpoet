<?php declare(strict_types=1);

namespace MailPoet\AdminPages\Pages;

use DateTimeImmutable;
use MailPoet\AdminPages\PageRenderer;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;
use MailPoet\Automation\Engine\Workflows\Step;
use MailPoet\Automation\Engine\Workflows\Workflow;
use MailPoet\WP\Functions as WPFunctions;

class AutomationEditor {
  /** @var WorkflowStorage */
  private $workflowStorage;

  /** @var PageRenderer */
  private $pageRenderer;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    WorkflowStorage $workflowStorage,
    PageRenderer $pageRenderer,
    WPFunctions $wp
  ) {
    $this->workflowStorage = $workflowStorage;
    $this->pageRenderer = $pageRenderer;
    $this->wp = $wp;
  }

  public function render() {
    // Gutenberg styles
    $this->wp->wpEnqueueStyle('wp-edit-site');
    $this->wp->wpEnqueueStyle('wp-format-library');
    $this->wp->wpEnqueueMedia();

    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

    $workflow = $id ? $this->workflowStorage->getWorkflow($id) : null;
    $this->pageRenderer->displayPage('automation/editor.html', [
      'workflow' => $workflow ? $this->buildWorkflow($workflow) : null,
      'sub_menu' => 'mailpoet-automation',
      'api' => [
        'root' => rtrim($this->wp->escUrlRaw($this->wp->restUrl()), '/'),
        'nonce' => $this->wp->wpCreateNonce('wp_rest'),
      ],
    ]);
  }

  private function buildWorkflow(Workflow $workflow): array {
    return [
      'id' => $workflow->getId(),
      'name' => $workflow->getName(),
      'status' => $workflow->getStatus(),
      'created_at' => $workflow->getCreatedAt()->format(DateTimeImmutable::W3C),
      'updated_at' => $workflow->getUpdatedAt()->format(DateTimeImmutable::W3C),
      'steps' => array_map(function (Step $step) {
        return [
          'id' => $step->getId(),
          'type' => $step->getType(),
          'key' => $step->getKey(),
          'next_step_id' => $step->getNextStepId(),
          'args' => $step->getArgs() ?: new \stdClass(),
        ];
      }, $workflow->getSteps()),
    ];
  }
}
