<?php

use MailPoet\Mailer\SMTP\SMTP;

class SMTPCest {
  function _before() {
    $this->settings = array(
      'name' => 'SMTP',
      'type' => 'SMTP',
      'host' => 'email-smtp.us-west-2.amazonaws.com',
      'port' => 587,
      'authentication' => array(
        'login' => 'AKIAIGPBLH6JWG5VCBQQ',
        'password' => 'AudVHXHaYkvr54veCzqiqOxDiMMyfQW3/V6F1tYzGXY3'
      ),
      'encryption' => 'tls'
    );
    $this->fromEmail = 'staff@mailpoet.com';
    $this->fromName = 'Sender';
    $this->mailer = new SMTP(
      $this->settings['host'],
      $this->settings['port'],
      $this->settings['authentication'],
      $this->settings['encryption'],
      $this->fromEmail,
      $this->fromName
    );
    $this->subscriber = 'Recipient <mailpoet-phoenix-test@mailinator.com>';
    $this->newsletter = array(
      'subject' => 'testing SMTP',
      'body' => array(
        'html' => 'HTML body',
        'text' => 'TEXT body'
      )
    );
  }

  function itCanBuildMailer() {
    $mailer = $this->mailer->buildMailer();
    expect($mailer->getTransport()->getHost())
      ->equals($this->settings['host']);
    expect($mailer->getTransport()->getPort())
      ->equals($this->settings['port']);
    expect($mailer->getTransport()->getUsername())
      ->equals($this->settings['authentication']['login']);
    expect($mailer->getTransport()->getPassword())
      ->equals($this->settings['authentication']['password']);
    expect($mailer->getTransport()->getEncryption())
      ->equals($this->settings['encryption']);
  }

  function itCanCreateMessage() {
    $message = $this->mailer->createMessage($this->newsletter, $this->subscriber);
    expect($message->getTo())
      ->equals(array('mailpoet-phoenix-test@mailinator.com' => 'Recipient'));
    expect($message->getFrom())
      ->equals(array($this->fromEmail => $this->fromName));
    expect($message->getSubject())
      ->equals($this->newsletter['subject']);
    expect($message->getBody())
      ->equals($this->newsletter['body']['html']);
    expect($message->getChildren()[0]->getContentType())
      ->equals('text/plain');
  }

  function itCanProcessSubscriber() {
    expect($this->mailer->processSubscriber('test@test.com'))
      ->equals(array('test@test.com' => ''));
    expect($this->mailer->processSubscriber('First <test@test.com>'))
      ->equals(array('test@test.com' => 'First'));
    expect($this->mailer->processSubscriber('First Last <test@test.com>'))
      ->equals(array('test@test.com' => 'First Last'));
  }

  function itCantSentWithoutProperAuthentication() {
    $this->mailer->authentication['login'] = 'someone';
    $this->mailer->mailer = $this->mailer->buildMailer();
    $result = $this->mailer->send(
      $this->newsletter,
      $this->subscriber
    );
    expect($result)->false();
  }

  function itCanSend() {
    $result = $this->mailer->send(
      $this->newsletter,
      $this->subscriber
    );
    expect($result)->true();
  }
}