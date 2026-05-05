<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Hooks;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Engine\WordPress;
use MailPoet\Automation\Integrations\MailPoet\Actions\SendEmailAction;
use MailPoet\Automation\Integrations\MailPoet\Templates\EmailFactory;
use MailPoet\DI\ContainerWrapper;
use MailPoet\EmailEditor\Integrations\MailPoet\BlockEmailContentDetector;
use MailPoet\Newsletter\NewsletterDeleteController;
use MailPoet\Newsletter\NewslettersRepository;

class AutomationEditorLoadingHooks {

  /** @var WordPress */
  private $wp;

  /** @var AutomationStorage  */
  private $automationStorage;

  /** @var NewslettersRepository  */
  private $newslettersRepository;

  private NewsletterDeleteController $newsletterDeleteController;

  private BlockEmailContentDetector $blockEmailContentDetector;

  public function __construct(
    WordPress $wp,
    AutomationStorage $automationStorage,
    NewslettersRepository $newslettersRepository,
    NewsletterDeleteController $newsletterDeleteController,
    BlockEmailContentDetector $blockEmailContentDetector
  ) {
    $this->wp = $wp;
    $this->automationStorage = $automationStorage;
    $this->newslettersRepository = $newslettersRepository;
    $this->newsletterDeleteController = $newsletterDeleteController;
    $this->blockEmailContentDetector = $blockEmailContentDetector;
  }

  public function init(): void {
    $this->wp->addAction(Hooks::EDITOR_BEFORE_LOAD, [$this, 'beforeEditorLoad']);
  }

  public function beforeEditorLoad(int $automationId): void {
    $automation = $this->automationStorage->getAutomation($automationId);
    if (!$automation) {
      return;
    }
    $this->disconnectEmptyEmailsFromSendEmailStep($automation);
    $this->setAutomationIdForEmails($automation);
  }

  private function setAutomationIdForEmails(Automation $automation): void {
    $emailFactory = ContainerWrapper::getInstance()->get(EmailFactory::class);
    $emailFactory->setAutomationIdForEmails($automation);
  }

  private function disconnectEmptyEmailsFromSendEmailStep(Automation $automation): void {
    $sendEmailSteps = array_filter(
      $automation->getSteps(),
      function(Step $step): bool {
        return $step->getKey() === SendEmailAction::KEY;
      }
    );
    $automationChanged = false;
    $newsletterIdsToDelete = [];
    foreach ($sendEmailSteps as $step) {
      $args = $step->getArgs();
      $emailId = $args['email_id'] ?? 0;
      if (!$emailId) {
        continue;
      }
      $newsletterEntity = $this->newslettersRepository->findOneById($emailId);
      $disconnectEmail = !$newsletterEntity;

      if ($newsletterEntity) {
        $wpPostId = $newsletterEntity->getWpPostId();
        if ($wpPostId) {
          $wpPost = $this->wp->getPost($wpPostId);
          $disconnectEmail = !$wpPost instanceof \WP_Post || !$this->blockEmailContentDetector->hasMeaningfulContent($wpPost);
          if (!$disconnectEmail && (int)($args['email_wp_post_id'] ?? 0) !== (int)$wpPostId) {
            $args['email_wp_post_id'] = $wpPostId;
          }
        } else {
          $disconnectEmail = $newsletterEntity->getBody() === null;
          if (!$disconnectEmail && isset($args['email_wp_post_id'])) {
            unset($args['email_wp_post_id']);
          }
        }
      }

      if ($disconnectEmail) {
        $newsletterIdsToDelete[] = (int)$emailId;
        unset($args['email_id']);
        unset($args['email_wp_post_id']);
      }

      if ($args === $step->getArgs()) {
        continue;
      }

      $updatedStep = new Step(
        $step->getId(),
        $step->getType(),
        $step->getKey(),
        $args,
        $step->getNextSteps(),
        $step->getFilters()
      );

      $steps = $automation->getSteps();
      $steps[$updatedStep->getId()] = $updatedStep;
      $automation->setSteps($steps);
      $automationChanged = true;

      if ($disconnectEmail && $automation->getStatus() === Automation::STATUS_ACTIVE) {
        $automation->setStatus(Automation::STATUS_DRAFT);
      }
    }

    if ($automationChanged) {
      $this->automationStorage->updateAutomation($automation);
    }

    if ($newsletterIdsToDelete !== []) {
      $this->newsletterDeleteController->bulkDelete(
        array_values(array_unique($newsletterIdsToDelete))
      );
    }
  }
}
