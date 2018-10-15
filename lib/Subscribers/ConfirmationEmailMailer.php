<?php

namespace MailPoet\Subscribers;

use MailPoet\Mailer\Mailer;
use MailPoet\Models\Setting;
use MailPoet\Models\Subscriber;
use MailPoet\Subscription\Url;
use MailPoet\Util\Helpers;

class ConfirmationEmailMailer {

  /** @var Mailer */
  private $mailer;

  /**
   * @param Mailer|null $mailer
   */
  function __construct($mailer = null) {
    if($mailer) {
      $this->mailer = $mailer;
    }
  }

  function sendConfirmationEmail(Subscriber $subscriber) {
    $signup_confirmation = Setting::getValue('signup_confirmation');

    if((bool)$signup_confirmation['enabled'] === false) {
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
      '<strong>'.join(', ', $segment_names).'</strong>',
      $body
    );

    // replace activation link
    $body = Helpers::replaceLinkTags(
      $body,
      Url::getConfirmationUrl($subscriber),
      array('target' => '_blank'),
      'activation_link'
    );

    // build email data
    $email = array(
      'subject' => $signup_confirmation['subject'],
      'body' => array(
        'html' => $body,
        'text' => $body
      )
    );

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
      if(!$this->mailer) {
        $this->mailer = new Mailer(false, $from, $reply_to);
      }
      $this->mailer->getSenderNameAndAddress($from);
      $this->mailer->getReplyToNameAndAddress($reply_to);
      return $this->mailer->send($email, $subscriber);
    } catch(\Exception $e) {
      $subscriber->setError($e->getMessage());
      return false;
    }
  }

}
