<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Actions;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Data\StepValidationArgs;
use MailPoet\Automation\Engine\Integration\Action;
use MailPoet\Automation\Engine\Integration\ValidationException;
use MailPoet\Automation\Integrations\MailPoet\Payloads\SegmentPayload;
use MailPoet\Automation\Integrations\MailPoet\Payloads\SubscriberPayload;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\InvalidStateException;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Scheduler\AutomationEmailScheduler;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\SubscriberSegmentRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Validator\Builder;
use MailPoet\Validator\Schema\ObjectSchema;
use Throwable;

class SendEmailAction implements Action {
  const KEY = 'mailpoet:send-email';

  /** @var SettingsController */
  private $settings;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var SubscriberSegmentRepository */
  private $subscriberSegmentRepository;

  /** @var SubscribersRepository  */
  private $subscribersRepository;

  /** @var AutomationEmailScheduler */
  private $automationEmailScheduler;

  public function __construct(
    SettingsController $settings,
    NewslettersRepository $newslettersRepository,
    SubscriberSegmentRepository $subscriberSegmentRepository,
    SubscribersRepository $subscribersRepository,
    AutomationEmailScheduler $automationEmailScheduler
  ) {
    $this->settings = $settings;
    $this->newslettersRepository = $newslettersRepository;
    $this->subscriberSegmentRepository = $subscriberSegmentRepository;
    $this->subscribersRepository = $subscribersRepository;
    $this->automationEmailScheduler = $automationEmailScheduler;
  }

  public function getKey(): string {
    return self::KEY;
  }

  public function getName(): string {
    return __('Send email', 'mailpoet');
  }

  public function getArgsSchema(): ObjectSchema {
    $nameDefault = $this->settings->get('sender.name');
    $addressDefault = $this->settings->get('sender.address');
    $replyToNameDefault = $this->settings->get('reply_to.name');
    $replyToAddressDefault = $this->settings->get('reply_to.address');

    $nonEmptyString = Builder::string()->required()->minLength(1);
    return Builder::object([
      // required fields
      'email_id' => Builder::integer()->required(),
      'name' => $nonEmptyString->default(__('Send email', 'mailpoet')),
      'subject' => $nonEmptyString->default(__('Subject', 'mailpoet')),
      'preheader' => Builder::string()->required()->default(''),
      'sender_name' => $nonEmptyString->default($nameDefault),
      'sender_address' => $nonEmptyString->formatEmail()->default($addressDefault),

      // optional fields
      'reply_to_name' => ($replyToNameDefault && $replyToNameDefault !== $nameDefault)
        ? Builder::string()->minLength(1)->default($replyToNameDefault)
        : Builder::string()->minLength(1),
      'reply_to_address' => ($replyToAddressDefault && $replyToAddressDefault !== $addressDefault)
        ? Builder::string()->formatEmail()->default($replyToAddressDefault)
        : Builder::string()->formatEmail(),
      'ga_campaign' => Builder::string()->minLength(1),
    ]);
  }

  public function getSubjectKeys(): array {
    return [
      'mailpoet:segment',
      'mailpoet:subscriber',
    ];
  }

  public function validate(StepValidationArgs $args): void {
    try {
      $this->getEmailForStep($args->getStep());
    } catch (InvalidStateException $exception) {
      $emailId = $args->getStep()->getArgs()['email_id'] ?? '';
      if (empty($emailId)) {
        throw ValidationException::create()
          ->withError('email_id', __("Automation email not found.", 'mailpoet'));
      }
      throw ValidationException::create()
        ->withError(
          'email_id',
          // translators: %s is the ID of email.
          sprintf(__("Automation email with ID '%s' not found.", 'mailpoet'), $emailId)
        );
    }
  }

  public function run(StepRunArgs $args): void {
    $newsletter = $this->getEmailForStep($args->getStep());
    $segmentId = $args->getSinglePayloadByClass(SegmentPayload::class)->getId();
    $subscriberId = $args->getSinglePayloadByClass(SubscriberPayload::class)->getId();

    $subscriberSegment = $this->subscriberSegmentRepository->findOneBy([
      'subscriber' => $subscriberId,
      'segment' => $segmentId,
      'status' => SubscriberEntity::STATUS_SUBSCRIBED,
    ]);

    if ($newsletter->getType() !== NewsletterEntity::TYPE_TRANSACTIONAL && !$subscriberSegment) {
      throw InvalidStateException::create()->withMessage(sprintf("Subscriber ID '%s' is not subscribed to segment ID '%s'.", $subscriberId, $segmentId));
    }

    $subscriber = $subscriberSegment ? $subscriberSegment->getSubscriber() : $this->subscribersRepository->findOneById($subscriberId);
    if (!$subscriber) {
      throw InvalidStateException::create();
    }

    $subscriberStatus = $subscriber->getStatus();
    if ($newsletter->getType() !== NewsletterEntity::TYPE_TRANSACTIONAL && $subscriberStatus !== SubscriberEntity::STATUS_SUBSCRIBED) {
      throw InvalidStateException::create()->withMessage(sprintf("Cannot schedule a newsletter for subscriber ID '%s' because their status is '%s'.", $subscriberId, $subscriberStatus));
    }

    if ($subscriberStatus === SubscriberEntity::STATUS_BOUNCED) {
      throw InvalidStateException::create()->withMessage(sprintf("Cannot schedule an email for subscriber ID '%s' because their status is '%s'.", $subscriberId, $subscriberStatus));
    }

    try {
      $this->automationEmailScheduler->createSendingTask($newsletter, $subscriber);
    } catch (Throwable $e) {
      throw InvalidStateException::create()->withMessage('Could not create sending task.');
    }
  }

  public function saveEmailSettings(Step $step, Automation $automation): void {
    $args = $step->getArgs();
    if (!isset($args['email_id']) || !$args['email_id']) {
      return;
    }

    $email = $this->getEmailForStep($step);
    $email->setType($this->isTransactional($step, $automation) ? NewsletterEntity::TYPE_TRANSACTIONAL : NewsletterEntity::TYPE_AUTOMATION);
    $email->setStatus(NewsletterEntity::STATUS_ACTIVE);
    $email->setSubject($args['subject'] ?? '');
    $email->setPreheader($args['preheader'] ?? '');
    $email->setSenderName($args['sender_name'] ?? '');
    $email->setSenderAddress($args['sender_address'] ?? '');
    $email->setReplyToName($args['reply_to_name'] ?? '');
    $email->setReplyToAddress($args['reply_to_address'] ?? '');
    $email->setGaCampaign($args['ga_campaign'] ?? '');
    $this->newslettersRepository->flush();
  }

  private function isTransactional(Step $step, Automation $automation): bool {
    $allSteps = $automation->getSteps();

    $triggers = array_filter(
      $allSteps,
      function(Step $step): bool {
        return $step->getType() === Step::TYPE_TRIGGER;
      }
    );
    $transactionalTriggers = array_filter(
      $triggers,
      function(Step $step): bool {
        return in_array($step->getKey(), ['woocommerce:order-status-changed'], true);
      }
    );

    if (!$triggers || count($transactionalTriggers) !== count($triggers)) {
      return false;
    }

    foreach ($transactionalTriggers as $trigger) {
      $nextSteps = array_map(
        function(NextStep $nextStep): string {
          return $nextStep->getId();
        },
        $trigger->getNextSteps()
      );
      if (!in_array($step->getId(), $nextSteps, true)) {
        return false;
      }
    }
    return true;
  }

  private function getEmailForStep(Step $step): NewsletterEntity {
    $emailId = $step->getArgs()['email_id'] ?? null;
    if (!$emailId) {
      throw InvalidStateException::create();
    }

    $email = $this->newslettersRepository->findOneBy([
      'id' => $emailId,
    ]);
    if (!$email) {
      throw InvalidStateException::create()->withMessage(
        // translators: %s is the ID of email.
        sprintf(__("Automation email with ID '%s' not found.", 'mailpoet'), $emailId)
      );
    }
    return $email;
  }
}
