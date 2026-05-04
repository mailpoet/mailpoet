<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Subscribers;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\Html2Text;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Shortcodes;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\MailerFactory;
use MailPoet\Mailer\MailerLog;
use MailPoet\Mailer\MetaInfo;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscription\SubscriptionUrlFactory;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class ConfirmationEmailMailer {

  const MAX_CONFIRMATION_EMAILS = 3;
  const ADMIN_CONFIRMATION_RESEND_INTERVAL_DAYS = 7;
  protected const WC_CONFIRMATION_UNAVAILABLE = 'unavailable';
  protected const WC_CONFIRMATION_SENT = 'sent';
  protected const WC_CONFIRMATION_FAILED = 'failed';

  /** @var MailerFactory */
  private $mailerFactory;

  /** @var WPFunctions */
  private $wp;

  /** @var SettingsController */
  private $settings;

  /** @var MetaInfo */
  private $mailerMetaInfo;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SubscriptionUrlFactory */
  private $subscriptionUrlFactory;

  /** @var ConfirmationEmailCustomizer */
  private $confirmationEmailCustomizer;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var LoggerFactory */
  private $loggerFactory;

  /** @var array Cache for confirmation emails sent within a request */
  private $sentEmails = [];

  public function __construct(
    MailerFactory $mailerFactory,
    SettingsController $settings,
    SubscribersRepository $subscribersRepository,
    SubscriptionUrlFactory $subscriptionUrlFactory,
    ConfirmationEmailCustomizer $confirmationEmailCustomizer,
    NewslettersRepository $newslettersRepository,
    ?WPFunctions $wp = null
  ) {
    $this->mailerFactory = $mailerFactory;
    $this->wp = $wp ?? new WPFunctions();
    $this->settings = $settings;
    $this->mailerMetaInfo = new MetaInfo;
    $this->subscriptionUrlFactory = $subscriptionUrlFactory;
    $this->subscribersRepository = $subscribersRepository;
    $this->confirmationEmailCustomizer = $confirmationEmailCustomizer;
    $this->newslettersRepository = $newslettersRepository;
    $this->loggerFactory = LoggerFactory::getInstance();
  }

  /**
   * Use this method if you want to make sure the confirmation email
   * is not sent multiple times within a single request
   * e.g. if sending confirmation emails from hooks
   * @param SubscriberEntity $subscriber The subscriber to send the confirmation email to.
   * @param int|null $confirmationEmailId Optional ID of a specific confirmation email newsletter to use.
   * @param int|null $confirmationPageId Optional ID of a specific page to use for the confirmation link.
   * @throws \Exception if unable to send the email.
   */
  public function sendConfirmationEmailOnce(SubscriberEntity $subscriber, ?int $confirmationEmailId = null, ?int $confirmationPageId = null, bool $isPublicFormSend = false): bool {
    if (isset($this->sentEmails[$subscriber->getId()])) {
      return true;
    }
    return $this->sendConfirmationEmail($subscriber, $confirmationEmailId, $confirmationPageId, $isPublicFormSend);
  }

  public function clearSentEmailsCache(): void {
    $this->sentEmails = [];
  }

  /**
   * Send confirmation email using WooCommerce email system.
   *
   * @return string
   */
  protected function sendWCConfirmationEmail(SubscriberEntity $subscriber, ?int $confirmationPageId = null): string {
    try {
      if (!function_exists('WC')) {
        return self::WC_CONFIRMATION_UNAVAILABLE;
      }

      $wc = WC();
      if (!$wc || !method_exists($wc, 'mailer')) {
        return self::WC_CONFIRMATION_UNAVAILABLE;
      }

      $mailer = $wc->mailer();
      $emails = $mailer->get_emails();

      if (!isset($emails['mailpoet_marketing_confirmation'])) {
        return self::WC_CONFIRMATION_UNAVAILABLE;
      }

      /** @var \MailPoet\WooCommerce\Emails\MarketingConfirmation $email */
      $email = $emails['mailpoet_marketing_confirmation'];

      $subscriber_email = $subscriber->getEmail();
      $activation_link = $this->subscriptionUrlFactory->getConfirmationUrl($subscriber, $confirmationPageId);
      $subscriber_firstname = $subscriber->getFirstName() ?: '';

      if (!$email->trigger($subscriber_email, $activation_link, $subscriber_firstname)) {
        return self::WC_CONFIRMATION_FAILED;
      }

      return self::WC_CONFIRMATION_SENT;

    } catch (\Exception $e) {
      $this->loggerFactory->getLogger(LoggerFactory::TOPIC_SENDING)->error(
        'MailPoet WC Marketing Confirmation Email Error: ' . $e->getMessage(),
        ['error' => $e, 'subscriber_id' => $subscriber->getId()]
      );
      return self::WC_CONFIRMATION_FAILED;
    }
  }

  public function buildEmailData(string $subject, string $html, string $text): array {
    return [
      'subject' => $subject,
      'body' => [
        'html' => $html,
        'text' => $text,
      ],
    ];
  }

  public function getMailBody(array $signupConfirmation, SubscriberEntity $subscriber, array $segmentNames, ?int $confirmationPageId = null): array {
    $body = nl2br($signupConfirmation['body']);

    // replace list of segments shortcode
    $body = str_replace(
      '[lists_to_confirm]',
      '<strong>' . join(', ', $segmentNames) . '</strong>',
      $body
    );

    // replace activation link
    $body = Helpers::replaceLinkTags(
      $body,
      $this->subscriptionUrlFactory->getConfirmationUrl($subscriber, $confirmationPageId),
      ['target' => '_blank'],
      'activation_link'
    );

    $subject = Shortcodes::process($signupConfirmation['subject'], null, null, $subscriber, null);

    $body = Shortcodes::process($body, null, null, $subscriber, null);

    //create a text version. @ is important here, Html2Text throws warnings
    $text = @Html2Text::convert(
      (mb_detect_encoding($body, 'UTF-8', true)) ? $body : mb_convert_encoding($body, 'UTF-8', mb_list_encodings()),
      true
    );

    return $this->buildEmailData($subject, $body, $text);
  }

  public function getMailBodyWithCustomizer(SubscriberEntity $subscriber, array $segmentNames, ?NewsletterEntity $newsletter = null, ?int $confirmationPageId = null): array {
    if ($newsletter === null) {
      $newsletter = $this->confirmationEmailCustomizer->getNewsletter();
    }

    $renderedNewsletter = $this->confirmationEmailCustomizer->render($newsletter);

    $stringBody = Helpers::joinObject($renderedNewsletter);

    // replace list of segments shortcode
    $body = (string)str_replace(
      '[lists_to_confirm]',
      join(', ', $segmentNames),
      $stringBody
    );

    // replace activation link
    $body = (string)str_replace(
      [
        'http://[activation_link]', // See MAILPOET-5253
        '[activation_link]',
      ],
      $this->subscriptionUrlFactory->getConfirmationUrl($subscriber, $confirmationPageId),
      $body
    );

    [
      $html,
      $text,
      $subject,
    ] = Helpers::splitObject(Shortcodes::process($body, null, $newsletter, $subscriber, null));

    // Fallback to newsletter subject if extracted subject is empty
    if (empty($subject)) {
      $subject = $newsletter->getSubject();
    }
    // Final fallback to default subject if still empty
    if (empty($subject)) {
      $subject = $this->settings->get('signup_confirmation.subject', __('Confirm your subscription', 'mailpoet'));
    }

    return $this->buildEmailData($subject, $html, $text);
  }

  /**
   * @param SubscriberEntity $subscriber The subscriber to send the confirmation email to.
   * @param int|null $confirmationEmailId Optional ID of a specific confirmation email newsletter to use.
   * @param int|null $confirmationPageId Optional ID of a specific page to use for the confirmation link.
   * @throws \Exception if unable to send the email.
   */
  public function sendConfirmationEmail(SubscriberEntity $subscriber, ?int $confirmationEmailId = null, ?int $confirmationPageId = null, bool $isPublicFormSend = false) {
    $signupConfirmation = $this->settings->get('signup_confirmation');
    if ((bool)$signupConfirmation['enabled'] === false) {
      return false;
    }

    if ($isPublicFormSend) {
      return $this->subscribersRepository->sendPublicConfirmationEmailWithCap(
        $subscriber,
        self::MAX_CONFIRMATION_EMAILS,
        function() use ($subscriber, $signupConfirmation, $confirmationEmailId, $confirmationPageId): bool {
          $sent = $this->sendConfirmationEmailMessage($subscriber, $signupConfirmation, $confirmationEmailId, $confirmationPageId);
          if ($sent) {
            $this->sentEmails[$subscriber->getId()] = true;
          }
          return $sent;
        }
      );
    }

    if (
      !$this->wp->isUserLoggedIn()
      && $subscriber->getConfirmationsCount() >= self::MAX_CONFIRMATION_EMAILS
    ) {
      return false;
    }

    $sent = $this->sendConfirmationEmailMessage($subscriber, $signupConfirmation, $confirmationEmailId, $confirmationPageId);
    if (!$sent) {
      return false;
    }

    $this->recordSuccessfulConfirmationSend($subscriber);
    $this->sentEmails[$subscriber->getId()] = true;

    return true;
  }

  /**
   * @return array{status: 'sent'|'skipped'|'send_failed', reason?: string}
   * @throws \Exception if unable to send the email.
   */
  public function sendAdminConfirmationEmail(SubscriberEntity $subscriber, ?\DateTimeInterface $oldestLifecycleDate = null): array {
    $signupConfirmation = $this->settings->get('signup_confirmation');
    if ((bool)$signupConfirmation['enabled'] === false) {
      return ['status' => 'skipped', 'reason' => 'confirmation_disabled'];
    }

    $claim = $this->subscribersRepository->claimAdminConfirmationEmailResend(
      $subscriber,
      self::MAX_CONFIRMATION_EMAILS,
      Carbon::now()->subDays(self::ADMIN_CONFIRMATION_RESEND_INTERVAL_DAYS)->millisecond(0),
      $oldestLifecycleDate
    );
    if (!$claim['claimed']) {
      return ['status' => 'skipped', 'reason' => $claim['reason'] ?? 'not_found'];
    }

    try {
      $sent = $this->sendConfirmationEmailMessage($subscriber, $signupConfirmation);
    } catch (\Throwable $throwable) {
      $this->releaseAdminConfirmationEmailClaim($subscriber, $claim);
      throw $throwable;
    }

    if (!$sent) {
      $this->releaseAdminConfirmationEmailClaim($subscriber, $claim);
      return ['status' => 'send_failed', 'reason' => 'sending_method'];
    }

    $this->completeAdminConfirmationEmailClaim($subscriber, $claim);
    $this->sentEmails[$subscriber->getId()] = true;
    return ['status' => 'sent'];
  }

  /** @param array{claim_time?: string, previous_last_confirmation_email_sent_at?: string|null, previous_count_confirmations?: int} $claim */
  private function releaseAdminConfirmationEmailClaim(SubscriberEntity $subscriber, array $claim): void {
    if (!isset($claim['claim_time'], $claim['previous_count_confirmations'])) {
      return;
    }
    $this->subscribersRepository->releaseAdminConfirmationEmailResendClaim(
      $subscriber,
      (string)$claim['claim_time'],
      $claim['previous_last_confirmation_email_sent_at'] ?? null,
      (int)$claim['previous_count_confirmations']
    );
  }

  /** @param array{claim_time?: string, previous_last_confirmation_email_sent_at?: string|null, previous_count_confirmations?: int} $claim */
  private function completeAdminConfirmationEmailClaim(SubscriberEntity $subscriber, array $claim): void {
    if (!isset($claim['claim_time'], $claim['previous_count_confirmations'])) {
      return;
    }
    $this->subscribersRepository->completeAdminConfirmationEmailResendClaim(
      $subscriber,
      (string)$claim['claim_time'],
      $claim['previous_last_confirmation_email_sent_at'] ?? null,
      (int)$claim['previous_count_confirmations']
    );
  }

  /**
   * @throws \Exception if unable to send the email.
   */
  private function sendConfirmationEmailMessage(SubscriberEntity $subscriber, array $signupConfirmation, ?int $confirmationEmailId = null, ?int $confirmationPageId = null): bool {
    $authorizationEmailsValidation = $this->settings->get(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING);
    $unauthorizedSenderEmail = isset($authorizationEmailsValidation['invalid_sender_address']);
    if (Bridge::isMPSendingServiceEnabled() && $unauthorizedSenderEmail) {
      return false;
    }

    // Try to send using WooCommerce email first. Only available in Garden environment.
    // Skip WC path when a per-list confirmation email is set, since WC doesn't support custom templates.
    if ($confirmationEmailId === null) {
      $wcConfirmationEmailResult = $this->sendWCConfirmationEmail($subscriber, $confirmationPageId);
      if ($wcConfirmationEmailResult === self::WC_CONFIRMATION_SENT) {
        return true;
      }
    }

    $segments = $subscriber->getSegments()->toArray();
    $segmentNames = array_map(function(SegmentEntity $segment) {
      return $segment->getName();
    }, $segments);

    $email = $this->getConfirmationEmailBody($signupConfirmation, $subscriber, $segmentNames, $confirmationEmailId, $confirmationPageId);

    // send email
    $extraParams = [
      'meta' => $this->mailerMetaInfo->getConfirmationMetaInfo($subscriber),
    ];

    // Don't attempt to send confirmation email when sending is paused
    $confirmationEmailErrorMessage = __('There was an error when sending a confirmation email for your subscription. Please contact the website owner.', 'mailpoet');
    if (MailerLog::isSendingPaused()) {
      throw new \Exception($confirmationEmailErrorMessage);
    }

    try {
      $defaultMailer = $this->mailerFactory->getDefaultMailer();
      $result = $defaultMailer->send($email, $subscriber, $extraParams);
    } catch (\Exception $e) {
      MailerLog::processTransactionalEmailError(MailerError::OPERATION_CONNECT, $e->getMessage(), $e->getCode());
      throw new \Exception($confirmationEmailErrorMessage);
    }

    if ($result['response'] === false) {
      if ($result['error'] instanceof MailerError && $result['error']->getLevel() === MailerError::LEVEL_HARD) {
        MailerLog::processTransactionalEmailError($result['error']->getOperation(), (string)$result['error']->getMessage());
      }
      throw new \Exception($confirmationEmailErrorMessage);
    };

    // E-mail was successfully sent we need to update the MailerLog
    MailerLog::incrementSentCount();

    return true;
  }

  private function recordSuccessfulConfirmationSend(SubscriberEntity $subscriber): void {
    if ($this->wp->isUserLoggedIn()) {
      return;
    }
    $subscriber->setConfirmationsCount($subscriber->getConfirmationsCount() + 1);
    $this->subscribersRepository->persist($subscriber);
    $this->subscribersRepository->flush();
  }

  /**
   * Determines which confirmation email body to use based on settings and optional override.
   */
  private function getConfirmationEmailBody(array $signupConfirmation, SubscriberEntity $subscriber, array $segmentNames, ?int $confirmationEmailId = null, ?int $confirmationPageId = null): array {
    // If a specific confirmation email ID is provided, try to use it
    if ($confirmationEmailId !== null) {
      $newsletter = $this->newslettersRepository->findOneById($confirmationEmailId);
      if ($newsletter !== null && $newsletter->getType() === NewsletterEntity::TYPE_CONFIRMATION_EMAIL_CUSTOMIZER) {
        return $this->getMailBodyWithCustomizer($subscriber, $segmentNames, $newsletter, $confirmationPageId);
      }
    }

    // Fall back to global settings
    $IsConfirmationEmailCustomizerEnabled = (bool)$this->settings->get(ConfirmationEmailCustomizer::SETTING_ENABLE_EMAIL_CUSTOMIZER, false);

    return $IsConfirmationEmailCustomizerEnabled ?
      $this->getMailBodyWithCustomizer($subscriber, $segmentNames, null, $confirmationPageId) :
      $this->getMailBody($signupConfirmation, $subscriber, $segmentNames, $confirmationPageId);
  }
}
