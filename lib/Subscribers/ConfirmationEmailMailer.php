<?php

namespace MailPoet\Subscribers;

use Html2Text\Html2Text;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerError;
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
  }

  function sendConfirmationEmail(Subscriber $subscriber) {
    $signup_confirmation = $this->settings->get('signup_confirmation');

    if ((bool)$signup_confirmation['enabled'] === false) {
      return false;
    }

    $subscriber->count_confirmations++;
    $subscriber->save();
    if (!$this->wp->isUserLoggedIn() && $subscriber->count_confirmations > self::MAX_CONFIRMATION_EMAILS) {
      return false;
    }

    $authorization_emails_validation = $this->settings->get(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING);
    $unauthorized_confirmation_email = isset($authorization_emails_validation['invalid_confirmation_address']);
    if (Bridge::isMPSendingServiceEnabled() && $unauthorized_confirmation_email) {
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
      Url::getConfirmationUrl($subscriber),
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

    // set from
    $from = (
      !empty($signup_confirmation['from'])
      && !empty($signup_confirmation['from']['address'])
    ) ? $signup_confirmation['from']
      : false;

    // set reply to
    $reply_to = (
      !empty($signup_confirmation['reply_to'])
      && !empty($signup_confirmation['reply_to']['address'])
    ) ? $signup_confirmation['reply_to']
      : false;

    // send email
    try {
      if (!$this->mailer) {
        $this->mailer = new Mailer(false, $from, $reply_to);
      }
      $this->mailer->getSenderNameAndAddress($from);
      $this->mailer->getReplyToNameAndAddress($reply_to);
      $result = $this->mailer->send($email, $subscriber);
      if ($result['response'] === false) {
        $subscriber->setError($result['error'] instanceof MailerError ? $result['error']->getMessage() : 'Unknown Error.');
        return false;
      };
      return true;
    } catch (\Exception $e) {
      $subscriber->setError($e->getMessage());
      return false;
    }
  }

}
