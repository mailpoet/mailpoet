<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Actions;

use MailPoet\AutomaticEmails\WooCommerce\Events\AbandonedCart;
use MailPoet\Automation\Engine\Control\AutomationController;
use MailPoet\Automation\Engine\Control\StepRunController;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Data\StepValidationArgs;
use MailPoet\Automation\Engine\Exceptions\NotFoundException;
use MailPoet\Automation\Engine\Integration\Action;
use MailPoet\Automation\Engine\Integration\ValidationException;
use MailPoet\Automation\Integrations\MailPoet\Payloads\SegmentPayload;
use MailPoet\Automation\Integrations\MailPoet\Payloads\SubscriberPayload;
use MailPoet\Automation\Integrations\WooCommerce\Payloads\AbandonedCartPayload;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\InvalidStateException;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Options\NewsletterOptionFieldsRepository;
use MailPoet\Newsletter\Options\NewsletterOptionsRepository;
use MailPoet\Newsletter\Scheduler\AutomationEmailScheduler;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\SubscriberSegmentRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Validator\Builder;
use MailPoet\Validator\Schema\ObjectSchema;
use Throwable;

class SendEmailAction implements Action {
  const KEY = 'mailpoet:send-email';

  // Intervals to poll for email status after sending. These are only
  // used when immediate status sync fails or the email is never sent.
  private const POLL_INTERVALS = [
    5 * MINUTE_IN_SECONDS, // ~5 minutes
    10 * MINUTE_IN_SECONDS, // ~15 minutes
    45 * MINUTE_IN_SECONDS, // ~1 hour
    4 * HOUR_IN_SECONDS, // ~5 hours           ...from email scheduling
    19 * HOUR_IN_SECONDS, // ~1 day
    4 * DAY_IN_SECONDS, // ~5 days
    25 * DAY_IN_SECONDS, // ~1 month
  ];

  // Retry intervals for sending. These are used when the email address
  // is not confirmed, and we need send non-transactional emails.
  private const OPTIN_RETRY_INTERVALS = [
    1 * MINUTE_IN_SECONDS, // ~1 minute
    5 * MINUTE_IN_SECONDS, // ~5 minutes
    20 * MINUTE_IN_SECONDS, // ~20 minutes
    1 * HOUR_IN_SECONDS, // ~1 hour
    12 * HOUR_IN_SECONDS, // ~12 hours
    1 * DAY_IN_SECONDS, // ~1 day
  ];
  private const WAIT_OPTIN = 'wait_optin';
  private const OPTIN_RETRIES = 'optin_retries';

  private const TRANSACTIONAL_TRIGGERS = [
    'mailpoet:custom-trigger',
    'woocommerce:order-status-changed',
    'woocommerce:order-created',
    'woocommerce:order-completed',
    'woocommerce:order-cancelled',
    'woocommerce:abandoned-cart',
    'woocommerce-subscriptions:subscription-created',
    'woocommerce-subscriptions:subscription-expired',
    'woocommerce-subscriptions:subscription-payment-failed',
    'woocommerce-subscriptions:subscription-renewed',
    'woocommerce-subscriptions:subscription-status-changed',
    'woocommerce-subscriptions:trial-ended',
    'woocommerce-subscriptions:trial-started',
    'woocommerce:buys-from-a-tag',
    'woocommerce:buys-from-a-category',
    'woocommerce:buys-a-product',
  ];

  private AutomationController $automationController;

  private SettingsController $settings;

  private NewslettersRepository $newslettersRepository;

  private SubscriberSegmentRepository $subscriberSegmentRepository;

  private SubscribersRepository $subscribersRepository;

  private SegmentsRepository $segmentsRepository;

  private AutomationEmailScheduler $automationEmailScheduler;

  private NewsletterOptionsRepository $newsletterOptionsRepository;

  private NewsletterOptionFieldsRepository $newsletterOptionFieldsRepository;

  public function __construct(
    AutomationController $automationController,
    SettingsController $settings,
    NewslettersRepository $newslettersRepository,
    SubscriberSegmentRepository $subscriberSegmentRepository,
    SubscribersRepository $subscribersRepository,
    SegmentsRepository $segmentsRepository,
    AutomationEmailScheduler $automationEmailScheduler,
    NewsletterOptionsRepository $newsletterOptionsRepository,
    NewsletterOptionFieldsRepository $newsletterOptionFieldsRepository
  ) {
    $this->automationController = $automationController;
    $this->settings = $settings;
    $this->newslettersRepository = $newslettersRepository;
    $this->subscriberSegmentRepository = $subscriberSegmentRepository;
    $this->subscribersRepository = $subscribersRepository;
    $this->segmentsRepository = $segmentsRepository;
    $this->automationEmailScheduler = $automationEmailScheduler;
    $this->newsletterOptionsRepository = $newsletterOptionsRepository;
    $this->newsletterOptionFieldsRepository = $newsletterOptionFieldsRepository;
  }

  public function getKey(): string {
    return self::KEY;
  }

  public function getName(): string {
    // translators: automation action title
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
      'mailpoet:subscriber',
    ];
  }

  public function validate(StepValidationArgs $args): void {
    try {
      $this->getEmailForStep($args->getStep());
    } catch (InvalidStateException $exception) {
      $exception = ValidationException::create()
        ->withMessage(__('Cannot send the email because it was not found. Please, go to the automation editor and update the email contents.', 'mailpoet'));

      $emailId = $args->getStep()->getArgs()['email_id'] ?? '';
      if (empty($emailId)) {
        $exception->withError('email_id', __("Automation email not found.", 'mailpoet'));
      } else {
        $exception->withError(
          'email_id',
          // translators: %s is the ID of email.
          sprintf(__("Automation email with ID '%s' not found.", 'mailpoet'), $emailId)
        );
      }
      throw $exception;
    }
  }

  public function run(StepRunArgs $args, StepRunController $controller): void {
    $newsletter = $this->getEmailForStep($args->getStep());
    $subscriber = $this->getSubscriber($args);
    $state = null;

    if ($args->isFirstRun()) {
      $subscriberStatus = $subscriber->getStatus();
      if ($subscriberStatus === SubscriberEntity::STATUS_BOUNCED) {
        // translators: %s is the subscriber's status.
        throw InvalidStateException::create()->withMessage(sprintf(__("Cannot send the email because the subscriber's status is '%s'.", 'mailpoet'), $subscriberStatus));
      }

      if ($this->isOptInRequired($newsletter, $subscriber)) {
        $controller->getRunLog()->saveLogData([self::WAIT_OPTIN => 1]);
        $this->rerunLater($args->getRunNumber(), $controller, $newsletter, $subscriber);
        return;
      }

      $this->scheduleEmail($args, $newsletter, $subscriber);
    } else {
      // Re-running for opt-in?
      $state = $this->getRunLogData($controller);

      if (array_key_exists(self::WAIT_OPTIN, $state) && $state[self::WAIT_OPTIN] === 1) {
        if ($this->isOptInRequired($newsletter, $subscriber)) {
          $this->rerunLater($args->getRunNumber(), $controller, $newsletter, $subscriber);
          return;
        }

        // Subscriber is now confirmed, so we can schedule an email.
        $controller->getRunLog()->saveLogData([
          self::WAIT_OPTIN => 0,
          self::OPTIN_RETRIES => $args->getRunNumber(),
        ]);
        $this->scheduleEmail($args, $newsletter, $subscriber);
      }

      // Check/sync sending status with the automation step
      $success = $this->checkSendingStatus($args, $newsletter, $subscriber);
      if ($success) {
        return;
      }
    }

    // At this point, we're re-running to check sending status. We need
    // to offset opt-in reruns count from sending reruns.
    $runNumber = $args->getRunNumber();
    $state = $state ?? $this->getRunLogData($controller);
    $optinRetryCount = $state[self::OPTIN_RETRIES] ?? 0;
    $runNumber -= $optinRetryCount;
    $this->rerunLater($runNumber, $controller, $newsletter, $subscriber);
  }

  private function scheduleEmail(StepRunArgs $args, NewsletterEntity $newsletter, SubscriberEntity $subscriber): void {
    $meta = $this->getNewsletterMeta($args);
    try {
      $this->automationEmailScheduler->createSendingTask($newsletter, $subscriber, $meta);
    } catch (Throwable $e) {
      throw InvalidStateException::create()->withMessage(__('Could not create sending task.', 'mailpoet'));
    }
  }

  private function getRunLogData(StepRunController $controller): array {
    $runLog = $controller->getRunLog()->getLog();
    return $runLog->getData();
  }

  /**
   * Schedule a progress run to sync the email sending status to the automation step.
   * Normally, a progress run is executed immediately after sending; we're scheduling
   * these runs to poll for the status if sync fails or email never sends (timeout),
   * or if we need to wait for subscriber opt-in.
   */
  private function rerunLater(int $runNumber, StepRunController $controller, NewsletterEntity $newsletter, SubscriberEntity $subscriber): void {
    $nextInterval = self::POLL_INTERVALS[$runNumber - 1] ?? 0;

    // Use different intervals when retrying for opt-in.
    if ($this->isOptInRequired($newsletter, $subscriber)) {
      if ($runNumber > count(self::OPTIN_RETRY_INTERVALS)) {
        $subscriberStatus = $subscriber->getStatus();
        // translators: %s is the subscriber's status.
        throw InvalidStateException::create()->withMessage(sprintf(__("Cannot send the email because the subscriber's status is '%s'.", 'mailpoet'), $subscriberStatus));
      }
      $nextInterval = self::OPTIN_RETRY_INTERVALS[$runNumber - 1];
    }

    $controller->scheduleProgress(time() + $nextInterval);
  }

  private function isOptInRequired(NewsletterEntity $newsletter, SubscriberEntity $subscriber): bool {
    $subscriberStatus = $subscriber->getStatus();
    if ($newsletter->getType() === NewsletterEntity::TYPE_AUTOMATION_TRANSACTIONAL) return false;
    return $subscriberStatus !== SubscriberEntity::STATUS_SUBSCRIBED;
  }

  /** @param mixed $data */
  public function handleEmailSent($data): void {
    if (!is_array($data)) {
      throw InvalidStateException::create()->withMessage(
      // translators: %s is the type of $data.
        sprintf(__('Invalid automation step data. Array expected, got: %s', 'mailpoet'), gettype($data))
      );
    }

    $runId = $data['run_id'] ?? null;
    if (!is_int($runId)) {
      throw InvalidStateException::create()->withMessage(
      // translators: %s is the type of $runId.
        sprintf(__("Invalid automation step data. Expected 'run_id' to be an integer, got: %s", 'mailpoet'), gettype($runId))
      );
    }

    $stepId = $data['step_id'] ?? null;
    if (!is_string($stepId)) {
      throw InvalidStateException::create()->withMessage(
        // translators: %s is the type of $runId.
        sprintf(__("Invalid automation step data. Expected 'step_id' to be a string, got: %s", 'mailpoet'), gettype($runId))
      );
    }

    $this->automationController->enqueueProgress($runId, $stepId);
  }

  private function checkSendingStatus(StepRunArgs $args, NewsletterEntity $newsletter, SubscriberEntity $subscriber): bool {
    $scheduledTaskSubscriber = $this->automationEmailScheduler->getScheduledTaskSubscriber($newsletter, $subscriber, $args->getAutomationRun());
    if (!$scheduledTaskSubscriber) {
      throw InvalidStateException::create()->withMessage(__('Email failed to schedule.', 'mailpoet'));
    }

    // email sending failed
    if ($scheduledTaskSubscriber->getFailed() === ScheduledTaskSubscriberEntity::FAIL_STATUS_FAILED) {
      throw InvalidStateException::create()->withMessage(
        // translators: %s is the error message.
        sprintf(__('Email failed to send. Error: %s', 'mailpoet'), $scheduledTaskSubscriber->getError() ?: 'Unknown error')
      );
    }

    $wasSent = $scheduledTaskSubscriber->getProcessed() === ScheduledTaskSubscriberEntity::STATUS_PROCESSED;
    $isLastRun = $args->getRunNumber() >= 1 + count(self::POLL_INTERVALS);

    // email was never sent
    if (!$wasSent && $isLastRun) {
      $error = __('Email sending process timed out.', 'mailpoet');
      $this->automationEmailScheduler->saveError($scheduledTaskSubscriber, $error);
      throw InvalidStateException::create()->withMessage($error);
    }

    return $wasSent;
  }

  private function getNewsletterMeta(StepRunArgs $args): array {
    $meta = [
      'automation' => [
        'id' => $args->getAutomation()->getId(),
        'run_id' => $args->getAutomationRun()->getId(),
        'step_id' => $args->getStep()->getId(),
        'run_number' => $args->getRunNumber(),
      ],
    ];

    if ($this->automationHasAbandonedCartTrigger($args->getAutomation())) {
      $payload = $args->getSinglePayloadByClass(AbandonedCartPayload::class);
      $meta[AbandonedCart::TASK_META_NAME] = $payload->getProductIds();
    }

    return $meta;
  }

  private function getSubscriber(StepRunArgs $args): SubscriberEntity {
    $subscriberId = $args->getSinglePayloadByClass(SubscriberPayload::class)->getId();
    try {
      $segmentId = $args->getSinglePayloadByClass(SegmentPayload::class)->getId();
    } catch (NotFoundException $e) {
      $segmentId = null;
    }

    // Without segment, fetch subscriber by ID (needed e.g. for "mailpoet:custom-trigger").
    // Transactional emails don't need to be checked against segment, no matter if it's set.
    if (!$segmentId || $this->isTransactional($args->getStep(), $args->getAutomation())) {
      $subscriber = $this->subscribersRepository->findOneById($subscriberId);
      if (!$subscriber) {
        throw InvalidStateException::create();
      }
      return $subscriber;
    }

    // With segment, fetch subscriber segment and check if they are subscribed.
    $subscriberSegment = $this->subscriberSegmentRepository->findOneBy([
      'subscriber' => $subscriberId,
      'segment' => $segmentId,
      'status' => SubscriberEntity::STATUS_SUBSCRIBED,
    ]);

    if (!$subscriberSegment) {
      $segment = $this->segmentsRepository->findOneById($segmentId);
      if (!$segment) { // This state should not happen because it is checked in the validation.
        throw InvalidStateException::create()->withMessage(__('Cannot send the email because the list was not found.', 'mailpoet'));
      }
      // translators: %s is the name of the list.
      throw InvalidStateException::create()->withMessage(sprintf(__("Cannot send the email because the subscriber is not subscribed to the '%s' list.", 'mailpoet'), $segment->getName()));
    }

    $subscriber = $subscriberSegment->getSubscriber();
    if (!$subscriber) {
      throw InvalidStateException::create();
    }
    return $subscriber;
  }

  public function saveEmailSettings(Step $step, Automation $automation): void {
    $args = $step->getArgs();
    if (!isset($args['email_id']) || !$args['email_id']) {
      return;
    }

    $email = $this->getEmailForStep($step);
    $email->setType($this->isTransactional($step, $automation) ? NewsletterEntity::TYPE_AUTOMATION_TRANSACTIONAL : NewsletterEntity::TYPE_AUTOMATION);
    $email->setStatus(NewsletterEntity::STATUS_ACTIVE);
    $email->setSubject($args['subject'] ?? '');
    $email->setPreheader($args['preheader'] ?? '');
    $email->setSenderName($args['sender_name'] ?? '');
    $email->setSenderAddress($args['sender_address'] ?? '');
    $email->setReplyToName($args['reply_to_name'] ?? '');
    $email->setReplyToAddress($args['reply_to_address'] ?? '');
    $email->setGaCampaign($args['ga_campaign'] ?? '');
    $this->storeNewsletterOption(
      $email,
      NewsletterOptionFieldEntity::NAME_GROUP,
      $this->automationHasWooCommerceTrigger($automation) ? 'woocommerce' : null
    );
    $this->storeNewsletterOption(
      $email,
      NewsletterOptionFieldEntity::NAME_EVENT,
      $this->automationHasAbandonedCartTrigger($automation) ? 'woocommerce_abandoned_shopping_cart' : null
    );

    $this->newslettersRepository->persist($email);
    $this->newslettersRepository->flush();
  }

  private function storeNewsletterOption(NewsletterEntity $newsletter, string $optionName, string $optionValue = null): void {
    $options = $newsletter->getOptions()->toArray();
    foreach ($options as $key => $option) {
      if ($option->getName() === $optionName) {
        if ($optionValue) {
          $option->setValue($optionValue);
          return;
        }
        $newsletter->getOptions()->remove($key);
        $this->newsletterOptionsRepository->remove($option);
        return;
      }
    }

    if (!$optionValue) {
      return;
    }

    $field = $this->newsletterOptionFieldsRepository->findOneBy([
      'name' => $optionName,
      'newsletterType' => $newsletter->getType(),
    ]);
    if (!$field) {
      return;
    }
    $option = new NewsletterOptionEntity($newsletter, $field);
    $option->setValue($optionValue);
    $this->newsletterOptionsRepository->persist($option);
    $newsletter->getOptions()->add($option);
  }

  private function isTransactional(Step $step, Automation $automation): bool {
    $triggers = $automation->getTriggers();
    $transactionalTriggers = array_filter(
      $triggers,
      function(Step $step): bool {
        return in_array($step->getKey(), self::TRANSACTIONAL_TRIGGERS, true);
      }
    );

    if (!$triggers || count($transactionalTriggers) !== count($triggers)) {
      return false;
    }

    foreach ($transactionalTriggers as $trigger) {
      if (!in_array($step->getId(), $trigger->getNextStepIds(), true)) {
        return false;
      }
    }
    return true;
  }

  private function automationHasWooCommerceTrigger(Automation $automation): bool {
    return (bool)array_filter(
      $automation->getTriggers(),
      function(Step $step): bool {
        return strpos($step->getKey(), 'woocommerce:') === 0;
      }
    );
  }

  private function automationHasAbandonedCartTrigger(Automation $automation): bool {
    return (bool)array_filter(
      $automation->getTriggers(),
      function(Step $step): bool {
        return in_array($step->getKey(), ['woocommerce:abandoned-cart'], true);
      }
    );
  }

  private function getEmailForStep(Step $step): NewsletterEntity {
    $emailId = $step->getArgs()['email_id'] ?? null;
    if (!$emailId) {
      throw InvalidStateException::create();
    }

    $email = $this->newslettersRepository->findOneBy([
      'id' => $emailId,
    ]);
    if (!$email || !in_array($email->getType(), [NewsletterEntity::TYPE_AUTOMATION, NewsletterEntity::TYPE_AUTOMATION_TRANSACTIONAL], true)) {
      throw InvalidStateException::create()->withMessage(
        // translators: %s is the ID of email.
        sprintf(__("Automation email with ID '%s' not found.", 'mailpoet'), $emailId)
      );
    }
    return $email;
  }
}
