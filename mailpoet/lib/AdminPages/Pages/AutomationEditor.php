<?php declare(strict_types=1);

namespace MailPoet\AdminPages\Pages;

use DateTimeImmutable;
use MailPoet\AdminPages\PageRenderer;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;
use MailPoet\Automation\Engine\Workflows\Step;
use MailPoet\Automation\Engine\Workflows\Workflow;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice as WPNotice;

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
    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

    $workflow = $id ? $this->workflowStorage->getWorkflow($id) : null;
    if (!$workflow) {
      $notice = new WPNotice(
        WPNotice::TYPE_ERROR,
        __("Workflow not found.", 'mailpoet')
      );
      $notice->displayWPNotice();
      $this->pageRenderer->displayPage('blank.html');
      return;
    }

    $this->pageRenderer->displayPage('automation/editor.html', [
      'workflow' => $this->buildWorkflow($workflow),
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
