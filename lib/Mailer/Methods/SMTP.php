<?php
namespace MailPoet\Mailer\Methods;

use MailPoet\Mailer\Mailer;

if(!defined('ABSPATH')) exit;

class SMTP {
  public $host;
  public $port;
  public $authentication;
  public $login;
  public $password;
  public $encryption;
  public $sender;
  public $reply_to;
  public $mailer;

  function __construct(
    $host, $port, $authentication, $login = null, $password = null, $encryption,
    $sender, $reply_to) {
    $this->host = $host;
    $this->port = $port;
    $this->authentication = $authentication;
    $this->login = $login;
    $this->password = $password;
    $this->encryption = $encryption;
    $this->sender = $sender;
    $this->reply_to = $reply_to;
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
        sprintf(__('%s has returned an unknown error.', 'mailpoet'), Mailer::METHOD_SMTP)
      );
  }

  function buildMailer() {
    $transport = \Swift_SmtpTransport::newInstance(
      $this->host, $this->port, $this->encryption);
    $transport->setTimeout(10);
    if($this->authentication) {
      $transport
        ->setUsername($this->login)
        ->setPassword($this->password);
    }
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