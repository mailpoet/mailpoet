<?php
namespace MailPoet\Mailer\Methods;

use MailPoet\Mailer\Mailer;

if(!defined('ABSPATH')) exit;

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

  function send($newsletter, $subscriber) {
    try {
      $message = $this->createMessage($newsletter, $subscriber);
      $result = $this->mailer->send($message);
    } catch(\Exception $e) {
      return Mailer::formatMailerSendErrorResult($e->getMessage());
    }
    return ($result === 1) ?
      Mailer::formatMailerSendSuccessResult() :
      Mailer::formatMailerSendErrorResult(
        sprintf(__('%s has returned an unknown error.', 'mailpoet'), Mailer::METHOD_PHPMAIL)
      );
  }

  function buildMailer() {
    $transport = \Swift_MailTransport::newInstance();
    return \Swift_Mailer::newInstance($transport);
  }

  function createMessage($newsletter, $subscriber) {
    $message = \Swift_Message::newInstance()
      ->setTo($this->processSubscriber($subscriber))
      ->setFrom(array(
          $this->sender['from_email'] => $this->sender['from_name']
        ))
      ->setReplyTo(array(
          $this->reply_to['reply_to_email'] =>  $this->reply_to['reply_to_name']
        ))
      ->setReturnPath($this->return_path)
      ->setSubject($newsletter['subject']);
    if(!empty($newsletter['body']['html'])) {
      $message = $message->setBody($newsletter['body']['html'], 'text/html');
    }
    if(!empty($newsletter['body']['text'])) {
      $message = $message->addPart($newsletter['body']['text'], 'text/plain');
    }
    return $message;
  }

  function processSubscriber($subscriber) {
    preg_match('!(?P<name>.*?)\s<(?P<email>.*?)>!', $subscriber, $subscriber_data);
    if(!isset($subscriber_data['email'])) {
      $subscriber_data = array(
        'email' => $subscriber,
      );
    }
    return array(
      $subscriber_data['email'] =>
        (isset($subscriber_data['name'])) ? $subscriber_data['name'] : ''
    );
  }
}