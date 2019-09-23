<?php

namespace MailPoet\Subscribers;

use Html2Text\Html2Text;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MetaInfo;
use MailPoet\Models\Subscriber;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscription\Url;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;

class ConfirmationEmailMailer {

  const MAX_CONFIRMATION_EMAILS = 3;

  /** @var Mailer|null */
  private $mailer;

  /** @var WPFunctions */
  private $wp;

  /** @var SettingsController */
  private $settings;

  /** @var MetaInfo */
  private $mailerMetaInfo;

  /** @var Url */
  private $subscription_url_factory;

  /**
   * @param Mailer|null $mailer
   */
  function __construct($mailer = null, WPFunctions $wp = null) {
    if ($mailer) {
      $this->mailer = $mailer;
    }
    if ($wp) {
      $this->wp = $wp;
    } else {
      $this->wp = new WPFunctions;
    }
    $this->settings = new SettingsController();
    $this->mailerMetaInfo = new MetaInfo;
    $this->subscription_url_factory = new Url($this->wp, $this->settings);
  }

  function sendConfirmationEmail(Subscriber $subscriber) {
    $signup_confirmation = $this->settings->get('signup_confirmation');
    if ((bool)$signup_confirmation['enabled'] === false) {
      return false;
    }

    if (!$this->wp->isUserLoggedIn() && $subscriber->count_confirmations >= self::MAX_CONFIRMATION_EMAILS) {
      return false;
    }

    $authorization_emails_validation = $this->settings->get(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING);
    $unauthorized_sender_email = isset($authorization_emails_validation['invalid_sender_address']);
    if (Bridge::isMPSendingServiceEnabled() && $unauthorized_sender_email) {
      return false;
    }

    $segments = $subscriber->segments()->findMany();
    $segment_names = array_map(function($segment) {
      return $segment->name;
    }, $segments);

    $body = nl2br($signup_confirmation['body']);

    // replace list of segments shortcode
    $body = str_replace(
      '[lists_to_confirm]',
      '<strong>' . join(', ', $segment_names) . '</strong>',
      $body
    );

    // replace activation link
    $body = Helpers::replaceLinkTags(
      $body,
      $this->subscription_url_factory->getConfirmationUrl($subscriber),
      ['target' => '_blank'],
      'activation_link'
    );

    //create a text version. @ is important here, Html2Text throws warnings
    $text = @Html2Text::convert((mb_detect_encoding($body, 'UTF-8', true)) ? $body : utf8_encode($body));

    // build email data
    $email = [
      'subject' => $signup_confirmation['subject'],
      'body' => [
        'html' => $body,
        'text' => $text,
      ],
    ];

    // send email
    try {
      if (!$this->mailer) {
        $this->mailer = new Mailer();
      }
      $this->mailer->init();
      $extra_params = [
        'meta' => $this->mailerMetaInfo->getConfirmationMetaInfo($subscriber),
      ];
      $result = $this->mailer->send($email, $subscriber, $extra_params);
      if ($result['response'] === false) {
        $subscriber->setError(__('Something went wrong with your subscription. Please contact the website owner.', 'mailpoet'));
        return false;
      };
      if (!$this->wp->isUserLoggedIn()) {
        $subscriber->count_confirmations++;
        $subscriber->save();
      }
      return true;
    } catch (\Exception $e) {
      $subscriber->setError(__('Something went wrong with your subscription. Please contact the website owner.', 'mailpoet'));
      return false;
    }
  }

}
