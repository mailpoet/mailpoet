<?php

use MailPoet\Mailer\Methods\PHPMail;

class PHPMailCest {
  function _before() {
    $this->sender = array(
      'from_name' => 'Sender',
      'from_email' => 'staff@mailpoet.com',
      'from_name_email' => 'Sender <staff@mailpoet.com>'
    );
    $this->reply_to = array(
      'reply_to_name' => 'Reply To',
      'reply_to_email' => 'reply-to@mailpoet.com',
      'reply_to_name_email' => 'Reply To <reply-to@mailpoet.com>'
    );
    $this->mailer = new PHPMail(
      $this->sender,
      $this->reply_to
    );
    $this->subscriber = 'Recipient <mailpoet-phoenix-test@mailinator.com>';
    $this->newsletter = array(
      'subject' => 'testing local method (PHP mail)',
      'body' => array(
        'html' => 'HTML body',
        'text' => 'TEXT body'
      )
    );
  }

  function itCanBuildMailer() {
    $mailer = $this->mailer->buildMailer();
    expect($mailer->getTransport() instanceof \Swift_MailTransport)->true();
  }

  function itCanCreateMessage() {
    $message = $this->mailer->createMessage($this->newsletter, $this->subscriber);
    expect($message->getTo())
      ->equals(array('mailpoet-phoenix-test@mailinator.com' => 'Recipient'));
    expect($message->getFrom())
      ->equals(array($this->sender['from_email'] => $this->sender['from_name']));
    expect($message->getReplyTo())
      ->equals(array($this->reply_to['reply_to_email'] => $this->reply_to['reply_to_name']));
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

  function itCanSend() {
    if(getenv('WP_TEST_MAILER_ENABLE_SENDING') !== 'true') return;
    $result = $this->mailer->send(
      $this->newsletter,
      $this->subscriber
    );
    expect($result)->true();
  }
}