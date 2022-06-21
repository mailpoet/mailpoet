<?php

namespace MailPoet\Subscribers;

use Html2Text\Html2Text;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Shortcodes;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Mailer\MailerFactory;
use MailPoet\Mailer\MetaInfo;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscription\SubscriptionUrlFactory;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;

class ConfirmationEmailMailer {

  const MAX_CONFIRMATION_EMAILS = 3;

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

  /** @var array Cache for confirmation emails sent within a request */
  private $sentEmails = [];

  public function __construct(
    MailerFactory $mailerFactory,
    WPFunctions $wp,
    SettingsController $settings,
    SubscribersRepository $subscribersRepository,
    SubscriptionUrlFactory $subscriptionUrlFactory
  ) {
    $this->mailerFactory = $mailerFactory;
    $this->wp = $wp;
    $this->settings = $settings;
    $this->mailerMetaInfo = new MetaInfo;
    $this->subscriptionUrlFactory = $subscriptionUrlFactory;
    $this->subscribersRepository = $subscribersRepository;
  }

  /**
   * Use this method if you want to make sure the confirmation email
   * is not sent multiple times within a single request
   * e.g. if sending confirmation emails from hooks
   * @throws \Exception if unable to send the email.
   */
  public function sendConfirmationEmailOnce(SubscriberEntity $subscriber): bool {
    if (isset($this->sentEmails[$subscriber->getId()])) {
      return true;
    }
    return $this->sendConfirmationEmail($subscriber);
  }

  /**
   * @throws \Exception if unable to send the email.
   */
  public function sendConfirmationEmail(SubscriberEntity $subscriber) {
    $signupConfirmation = $this->settings->get('signup_confirmation');
    if ((bool)$signupConfirmation['enabled'] === false) {
      return false;
    }
    if (!$this->wp->isUserLoggedIn() && $subscriber->getConfirmationsCount() >= self::MAX_CONFIRMATION_EMAILS) {
      return false;
    }

    $authorizationEmailsValidation = $this->settings->get(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING);
    $unauthorizedSenderEmail = isset($authorizationEmailsValidation['invalid_sender_address']);
    if (Bridge::isMPSendingServiceEnabled() && $unauthorizedSenderEmail) {
      return false;
    }

    $segments = $subscriber->getSegments()->toArray();
    $segmentNames = array_map(function(SegmentEntity $segment) {
      return $segment->getName();
    }, $segments);

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
      $this->subscriptionUrlFactory->getConfirmationUrl($subscriber),
      ['target' => '_blank'],
      'activation_link'
    );

    $subject = Shortcodes::process($signupConfirmation['subject'], null, null, $subscriber, null);

    $body = Shortcodes::process($body, null, null, $subscriber, null);

    //create a text version. @ is important here, Html2Text throws warnings
    $text = @Html2Text::convert(
      (mb_detect_encoding($body, 'UTF-8', true)) ? $body : utf8_encode($body),
      true
    );

    // build email data
    $email = [
      'subject' => $subject,
      'body' => [
        'html' => $body,
        'text' => $text,
      ],
    ];

    // send email
    $extraParams = [
      'meta' => $this->mailerMetaInfo->getConfirmationMetaInfo($subscriber),
    ];
    try {
      $defaultMailer = $this->mailerFactory->getDefaultMailer();
      $result = $defaultMailer->send($email, $subscriber, $extraParams);
    } catch (\Exception $e) {
      throw new \Exception(__('Something went wrong with your subscription. Please contact the website owner.', 'mailpoet'));
    }

    if ($result['response'] === false) {
      throw new \Exception(__('Something went wrong with your subscription. Please contact the website owner.', 'mailpoet'));
    };

    if (!$this->wp->isUserLoggedIn()) {
      $subscriber->setConfirmationsCount($subscriber->getConfirmationsCount() + 1);
      $this->subscribersRepository->persist($subscriber);
      $this->subscribersRepository->flush();
    }
    $this->sentEmails[$subscriber->getId()] = true;

    return true;
  }
}
