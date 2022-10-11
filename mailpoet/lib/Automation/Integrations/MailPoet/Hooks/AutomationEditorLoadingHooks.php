<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Hooks;

use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\WP\Functions as WP;

class AutomationEditorLoadingHooks {

  /** @var WP */
  private $wp;

  /** @var WorkflowStorage  */
  private $workflowStorage;

  /** @var NewslettersRepository  */
  private $newslettersRepository;

  public function __construct(
    WP $wp,
    WorkflowStorage $workflowStorage,
    NewslettersRepository $newslettersRepository
  ) {
    $this->wp = $wp;
    $this->workflowStorage = $workflowStorage;
    $this->newslettersRepository = $newslettersRepository;
  }

  public function init(): void {
    $this->wp->addAction(Hooks::EDITOR_BEFORE_LOAD, [$this, 'beforeEditorLoad']);
  }

  public function beforeEditorLoad(int $workflowId): void {
    $workflow = $this->workflowStorage->getWorkflow($workflowId);
    if (!$workflow) {
      return;
    }
    $this->disconnectEmptyEmailsFromSendEmailStep($workflow);
  }

  private function disconnectEmptyEmailsFromSendEmailStep(Workflow $workflow): void {
    $sendEmailSteps = array_filter(
      $workflow->getSteps(),
      function(Step $step): bool {
        return $step->getKey() === 'mailpoet:send-email';
      }
    );
    foreach ($sendEmailSteps as $step) {
      $emailId = $step->getArgs()['email_id'] ?? 0;
      if (!$emailId) {
        continue;
      }
      $newsletterEntity = $this->newslettersRepository->findOneById($emailId);
      if ($newsletterEntity && $newsletterEntity->getBody() !== null) {
        continue;
      }

      $this->newslettersRepository->bulkDelete([$emailId]);
      $args = $step->getArgs();
      unset($args['email_id']);
      $updatedStep = new Step(
        $step->getId(),
        $step->getType(),
        $step->getKey(),
        $args,
        $step->getNextSteps()
      );

      $steps = array_merge(
        $workflow->getSteps(),
        [$updatedStep->getId() => $updatedStep]
      );
      $workflow->setSteps($steps);

      //To be valid, an email would need to be associated to an active workflow.
      if ($workflow->getStatus() === Workflow::STATUS_ACTIVE) {
        $workflow->setStatus(Workflow::STATUS_DRAFT);
      }
      $this->workflowStorage->updateWorkflow($workflow);
    }
  }
}
