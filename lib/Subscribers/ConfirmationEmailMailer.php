<?php

namespace MailPoet\Subscribers;

use Html2Text\Html2Text;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MetaInfo;
use MailPoet\Models\Subscriber;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscription\SubscriptionUrlFactory;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;

class ConfirmationEmailMailer {

  const MAX_CONFIRMATION_EMAILS = 3;

  /** @var Mailer */
  private $mailer;

  /** @var WPFunctions */
  private $wp;

  /** @var SettingsController */
  private $settings;

  /** @var MetaInfo */
  private $mailerMetaInfo;

  /** @var SubscriptionUrlFactory */
  private $subscriptionUrlFactory;

  /** @var array Cache for confirmation emails sent within a request */
  private $sentEmails = [];

  public function __construct(Mailer $mailer, WPFunctions $wp, SettingsController $settings, SubscriptionUrlFactory $subscriptionUrlFactory) {
    $this->mailer = $mailer;
    $this->wp = $wp;
    $this->settings = $settings;
    $this->mailerMetaInfo = new MetaInfo;
    $this->subscriptionUrlFactory = $subscriptionUrlFactory;
  }

  /**
   * Use this method if you want to make sure the confirmation email
   * is not sent multiple times within a single request
   * e.g. if sending confirmation emails from hooks
   */
  public function sendConfirmationEmailOnce(Subscriber $subscriber): bool {
    if (isset($this->sentEmails[$subscriber->id])) {
      return true;
    }
    return $this->sendConfirmationEmail($subscriber);
  }

  public function sendConfirmationEmail(Subscriber $subscriber) {
    $signupConfirmation = $this->settings->get('signup_confirmation');
    if ((bool)$signupConfirmation['enabled'] === false) {
      return false;
    }
    if (!$this->wp->isUserLoggedIn() && $subscriber->countConfirmations >= self::MAX_CONFIRMATION_EMAILS) {
      return false;
    }

    $authorizationEmailsValidation = $this->settings->get(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING);
    $unauthorizedSenderEmail = isset($authorizationEmailsValidation['invalid_sender_address']);
    if (Bridge::isMPSendingServiceEnabled() && $unauthorizedSenderEmail) {
      return false;
    }

    $segments = $subscriber->segments()->findMany();
    $segmentNames = array_map(function($segment) {
      return $segment->name;
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

    //create a text version. @ is important here, Html2Text throws warnings
    $text = @Html2Text::convert((mb_detect_encoding($body, 'UTF-8', true)) ? $body : utf8_encode($body));

    // build email data
    $email = [
      'subject' => $signupConfirmation['subject'],
      'body' => [
        'html' => $body,
        'text' => $text,
      ],
    ];

    // send email
    try {
      $extraParams = [
        'meta' => $this->mailerMetaInfo->getConfirmationMetaInfo($subscriber),
      ];
      $result = $this->mailer->send($email, $subscriber, $extraParams);
      if ($result['response'] === false) {
        $subscriber->setError(__('Something went wrong with your subscription. Please contact the website owner.', 'mailpoet'));
        return false;
      };

      if (!$this->wp->isUserLoggedIn()) {
        $subscriber->countConfirmations++;
        $subscriber->save();
      }
      $this->sentEmails[$subscriber->id] = true;
      return true;
    } catch (\Exception $e) {
      $subscriber->setError(__('Something went wrong with your subscription. Please contact the website owner.', 'mailpoet'));
      return false;
    }
  }
}
