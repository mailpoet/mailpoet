<?php

namespace MailPoet\Mailer\Methods;

use MailPoet\Mailer\Mailer;

if(!defined('ABSPATH')) exit;

require_once ABSPATH . WPINC . '/class-phpmailer.php';

class PHPMail {
  public $sender;
  public $reply_to;
  public $return_path;
  public $mailer;

  function __construct($sender, $reply_to, $return_path) {
    $this->sender = $sender;
    $this->reply_to = $reply_to;
    $this->return_path = ($return_path) ?
      $return_path :
      $this->sender['from_email'];
    $this->mailer = $this->buildMailer();
  }

  function send($newsletter, $subscriber, $extra_params = array()) {
    try {
      $mailer = $this->configureMailerWithMessage($newsletter, $subscriber, $extra_params);
      $result = $mailer->send();
    } catch(\Exception $e) {
      return Mailer::formatMailerSendErrorResult($e->getMessage());
    }
    if($result === true) {
      return Mailer::formatMailerSendSuccessResult();
    } else {
      $result = sprintf(__('%s has returned an unknown error.', 'mailpoet'), Mailer::METHOD_PHPMAIL);
      if(empty($extra_params['test_email'])) {
        $result .= sprintf(' %s: %s', __('Unprocessed subscriber', 'mailpoet'), $subscriber);
      }
      return Mailer::formatMailerSendErrorResult($result);
    }
  }

  function buildMailer() {
    $mailer = new \PHPMailer(true);
    // send using PHP's mail() function
    $mailer->isMail();
    return $mailer;
  }

  function configureMailerWithMessage($newsletter, $subscriber, $extra_params = array()) {
    $mailer = $this->mailer;
    $mailer->clearAddresses();
    $mailer->clearCustomHeaders();
    $mailer->isHTML();
    $mailer->CharSet = 'UTF-8';
    $mailer->setFrom($this->sender['from_email'], $this->sender['from_name'], false);
    $mailer->addReplyTo($this->reply_to['reply_to_email'], $this->reply_to['reply_to_name']);
    $subscriber = $this->processSubscriber($subscriber);
    $mailer->addAddress($subscriber['email'], $subscriber['name']);
    $mailer->Subject = (!empty($newsletter['subject'])) ? $newsletter['subject'] : '';
    $mailer->Body = (!empty($newsletter['body']['html'])) ? $newsletter['body']['html'] : '';
    $mailer->AltBody = (!empty($newsletter['body']['text'])) ? $newsletter['body']['text'] : '';
    $mailer->Sender = $this->return_path;
    if(!empty($extra_params['unsubscribe_url'])) {
      $this->mailer->addCustomHeader('List-Unsubscribe', $extra_params['unsubscribe_url']);
    }
    return $mailer;
  }

  function processSubscriber($subscriber) {
    preg_match('!(?P<name>.*?)\s<(?P<email>.*?)>!', $subscriber, $subscriber_data);
    if(!isset($subscriber_data['email'])) {
      $subscriber_data = array(
        'email' => $subscriber,
      );
    }
    return array(
      'email' => $subscriber_data['email'],
      'name' => (isset($subscriber_data['name'])) ? $subscriber_data['name'] : ''
    );
  }
}