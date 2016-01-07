<?php
namespace MailPoet\Mailer\Methods;

if(!defined('ABSPATH')) exit;

class SMTP {
  public $host;
  public $port;
  public $authentication;
  public $login;
  public $password;
  public $encryption;
  public $from_name;
  public $from_email;
  public $mailer;
  
  function __construct(
    $host, $port, $authentication, $login = null, $password = null, $encryption,
    $from_email, $from_name) {
    $this->host = $host;
    $this->port = $port;
    $this->authentication = $authentication;
    $this->login = $login;
    $this->password = $password;
    $this->encryption = $encryption;
    $this->from_name = $from_name;
    $this->from_email = $from_email;
    $this->mailer = $this->buildMailer();
  }

  function send($newsletter, $subscriber) {
    try {
      $message = $this->createMessage($newsletter, $subscriber);
      $result = $this->mailer->send($message);
    } catch(\Exception $e) {
      $result = false;
    }
    return ($result === 1);
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
      ->setFrom(array($this->from_email => $this->from_name))
      ->setTo($this->processSubscriber($subscriber))
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