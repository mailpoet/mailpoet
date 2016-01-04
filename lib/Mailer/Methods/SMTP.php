<?php
namespace MailPoet\Mailer\Methods;

if(!defined('ABSPATH')) exit;

class SMTP {
  function __construct($host, $port, $authentication, $login = null, $password = null, $encryption,
    $fromEmail, $fromName) {
    $this->host = $host;
    $this->port = $port;
    $this->authentication = $authentication;
    $this->login = $login;
    $this->password = $password;
    $this->encryption = $encryption;
    $this->fromName = $fromName;
    $this->fromEmail = $fromEmail;
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
      ->setFrom(array($this->fromEmail => $this->fromName))
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
    preg_match('!(?P<name>.*?)\s<(?P<email>.*?)>!', $subscriber, $subscriberData);
    if(!isset($subscriberData['email'])) {
      $subscriberData = array(
        'email' => $subscriber,
      );
    }
    return array(
      $subscriberData['email'] =>
        (isset($subscriberData['name'])) ? $subscriberData['name'] : ''
    );
  }
}