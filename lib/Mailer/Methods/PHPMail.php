<?php

namespace MailPoet\Mailer\Methods;

use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\Methods\ErrorMappers\PHPMailMapper;

if(!defined('ABSPATH')) exit;

require_once ABSPATH . WPINC . '/class-phpmailer.php';

class PHPMail {
  public $sender;
  public $reply_to;
  public $return_path;
  public $mailer;

  /** @var PHPMailMapper  */
  private $error_mapper;

  function __construct($sender, $reply_to, $return_path, PHPMailMapper $error_mapper) {
    $this->sender = $sender;
    $this->reply_to = $reply_to;
    $this->return_path = ($return_path) ?
      $return_path :
      $this->sender['from_email'];
    $this->mailer = $this->buildMailer();
    $this->error_mapper = $error_mapper;
  }

  function send($newsletter, $subscriber, $extra_params = array()) {
    try {
      $mailer = $this->configureMailerWithMessage($newsletter, $subscriber, $extra_params);
      $result = $mailer->send();
    } catch(\Exception $e) {
      return Mailer::formatMailerErrorResult($this->error_mapper->getErrorFromException($e, $subscriber));
    }
    if($result === true) {
      return Mailer::formatMailerSendSuccessResult();
    } else {
      $error = $this->error_mapper->getErrorForSubscriber($subscriber);
      return Mailer::formatMailerErrorResult($error);
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
