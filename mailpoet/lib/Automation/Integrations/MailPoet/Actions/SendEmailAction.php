<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Actions;

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
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\SubscriberSegmentRepository;
use MailPoet\Validator\Builder;
use MailPoet\Validator\Schema\ObjectSchema;
use Throwable;

class SendEmailAction implements Action {
  /** @var SettingsController */
  private $settings;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** @var SubscriberSegmentRepository */
  private $subscriberSegmentRepository;

  /** @var AutomationEmailScheduler */
  private $automationEmailScheduler;

  public function __construct(
    SettingsController $settings,
    NewslettersRepository $newslettersRepository,
    ScheduledTasksRepository $scheduledTasksRepository,
    SubscriberSegmentRepository $subscriberSegmentRepository,
    AutomationEmailScheduler $automationEmailScheduler
  ) {
    $this->settings = $settings;
    $this->newslettersRepository = $newslettersRepository;
    $this->scheduledTasksRepository = $scheduledTasksRepository;
    $this->subscriberSegmentRepository = $subscriberSegmentRepository;
    $this->automationEmailScheduler = $automationEmailScheduler;
  }

  public function getKey(): string {
    return 'mailpoet:send-email';
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

    if (!$subscriberSegment) {
      throw InvalidStateException::create()->withMessage(sprintf("Subscriber ID '%s' is not subscribed to segment ID '%s'.", $subscriberId, $segmentId));
    }

    $subscriber = $subscriberSegment->getSubscriber();
    if (!$subscriber) {
      throw InvalidStateException::create();
    }

    $subscriberStatus = $subscriber->getStatus();
    if ($subscriberStatus !== SubscriberEntity::STATUS_SUBSCRIBED) {
      throw InvalidStateException::create()->withMessage(sprintf("Cannot schedule a newsletter for subscriber ID '%s' because their status is '%s'.", $subscriberId, $subscriberStatus));
    }

    $previouslyScheduledNotification = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($newsletter, $subscriberId);
    if (!empty($previouslyScheduledNotification)) {
      throw InvalidStateException::create()->withMessage(sprintf("Subscriber ID '%s' was already scheduled to receive newsletter ID '%s'.", $subscriberId, $newsletter->getId()));
    }

    try {
      $this->automationEmailScheduler->createSendingTask($newsletter, $subscriber);
    } catch (Throwable $e) {
      throw InvalidStateException::create()->withMessage('Could not create sending task.');
    }
  }

  public function saveEmailSettings(Step $step): void {
    $args = $step->getArgs();
    if (!isset($args['email_id']) || !$args['email_id']) {
      return;
    }

    $email = $this->getEmailForStep($step);
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

  private function getEmailForStep(Step $step): NewsletterEntity {
    $emailId = $step->getArgs()['email_id'] ?? null;
    if (!$emailId) {
      throw InvalidStateException::create();
    }

    $email = $this->newslettersRepository->findOneBy([
      'id' => $emailId,
      'type' => NewsletterEntity::TYPE_AUTOMATION,
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
