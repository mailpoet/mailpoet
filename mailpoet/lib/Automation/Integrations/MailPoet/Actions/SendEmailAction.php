<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Actions;

use MailPoet\AutomaticEmails\WooCommerce\Events\AbandonedCart;
use MailPoet\Automation\Engine\Control\StepRunController;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Data\StepValidationArgs;
use MailPoet\Automation\Engine\Integration\Action;
use MailPoet\Automation\Engine\Integration\ValidationException;
use MailPoet\Automation\Integrations\MailPoet\Payloads\SegmentPayload;
use MailPoet\Automation\Integrations\MailPoet\Payloads\SubscriberPayload;
use MailPoet\Automation\Integrations\WooCommerce\Payloads\AbandonedCartPayload;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\InvalidStateException;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Options\NewsletterOptionFieldsRepository;
use MailPoet\Newsletter\Options\NewsletterOptionsRepository;
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

  /** @var NewsletterOptionsRepository */
  private $newsletterOptionsRepository;

  /** @var NewsletterOptionFieldsRepository */
  private $newsletterOptionFieldsRepository;

  public function __construct(
    SettingsController $settings,
    NewslettersRepository $newslettersRepository,
    SubscriberSegmentRepository $subscriberSegmentRepository,
    SubscribersRepository $subscribersRepository,
    AutomationEmailScheduler $automationEmailScheduler,
    NewsletterOptionsRepository $newsletterOptionsRepository,
    NewsletterOptionFieldsRepository $newsletterOptionFieldsRepository
  ) {
    $this->settings = $settings;
    $this->newslettersRepository = $newslettersRepository;
    $this->subscriberSegmentRepository = $subscriberSegmentRepository;
    $this->subscribersRepository = $subscribersRepository;
    $this->automationEmailScheduler = $automationEmailScheduler;
    $this->newsletterOptionsRepository = $newsletterOptionsRepository;
    $this->newsletterOptionFieldsRepository = $newsletterOptionFieldsRepository;
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

  public function run(StepRunArgs $args, StepRunController $controller): void {
    $newsletter = $this->getEmailForStep($args->getStep());
    $segmentId = $args->getSinglePayloadByClass(SegmentPayload::class)->getId();
    $subscriberId = $args->getSinglePayloadByClass(SubscriberPayload::class)->getId();

    $subscriberSegment = $this->subscriberSegmentRepository->findOneBy([
      'subscriber' => $subscriberId,
      'segment' => $segmentId,
      'status' => SubscriberEntity::STATUS_SUBSCRIBED,
    ]);

    if ($newsletter->getType() !== NewsletterEntity::TYPE_AUTOMATION_TRANSACTIONAL && !$subscriberSegment) {
      throw InvalidStateException::create()->withMessage(sprintf("Subscriber ID '%s' is not subscribed to segment ID '%s'.", $subscriberId, $segmentId));
    }

    $subscriber = $subscriberSegment ? $subscriberSegment->getSubscriber() : $this->subscribersRepository->findOneById($subscriberId);
    if (!$subscriber) {
      throw InvalidStateException::create();
    }

    $subscriberStatus = $subscriber->getStatus();
    if ($newsletter->getType() !== NewsletterEntity::TYPE_AUTOMATION_TRANSACTIONAL && $subscriberStatus !== SubscriberEntity::STATUS_SUBSCRIBED) {
      throw InvalidStateException::create()->withMessage(sprintf("Cannot schedule a newsletter for subscriber ID '%s' because their status is '%s'.", $subscriberId, $subscriberStatus));
    }

    if ($subscriberStatus === SubscriberEntity::STATUS_BOUNCED) {
      throw InvalidStateException::create()->withMessage(sprintf("Cannot schedule an email for subscriber ID '%s' because their status is '%s'.", $subscriberId, $subscriberStatus));
    }

    $meta = $this->getNewsletterMeta($args);
    try {
      $this->automationEmailScheduler->createSendingTask($newsletter, $subscriber, $meta);
    } catch (Throwable $e) {
      throw InvalidStateException::create()->withMessage('Could not create sending task.');
    }
  }

  private function getNewsletterMeta(StepRunArgs $args): array {
    if (!$this->automationHasAbandonedCartTrigger($args->getAutomation())) {
      return [];
    }

    $payload = $args->getSinglePayloadByClass(AbandonedCartPayload::class);
    return [AbandonedCart::TASK_META_NAME => $payload->getProductIds()];
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

  private function automationHasWooCommerceTrigger(Automation $automation): bool {
    return (bool)array_filter(
      $automation->getTriggers(),
      function(Step $step): bool {
        return in_array($step->getKey(), ['woocommerce:order-status-changed', 'woocommerce:abandoned-cart'], true);
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
