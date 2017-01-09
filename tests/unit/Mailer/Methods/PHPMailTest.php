<?php

use MailPoet\Mailer\Methods\PHPMail;

class PHPMailTest extends MailPoetTest {
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
    $this->return_path = 'bounce@mailpoet.com';
    $this->mailer = new PHPMail(
      $this->sender,
      $this->reply_to,
      $this->return_path
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

  function testItCanBuildMailer() {
    $mailer = $this->mailer->buildMailer();
    expect($mailer->getTransport() instanceof \Swift_MailTransport)->true();
  }

  function testWhenReturnPathIsNullItIsSetToSenderEmail() {
    $mailer = new PHPMail(
      $this->sender,
      $this->reply_to,
      $return_path = false
    );
    expect($mailer->return_path)->equals($this->sender['from_email']);
  }

  function testItCanCreateMessage() {
    $message = $this->mailer->createMessage($this->newsletter, $this->subscriber);
    expect($message->getTo())
      ->equals(array('mailpoet-phoenix-test@mailinator.com' => 'Recipient'));
    expect($message->getFrom())
      ->equals(array($this->sender['from_email'] => $this->sender['from_name']));
    expect($message->getSender())
      ->equals(array($this->sender['from_email'] => null));
    expect($message->getReplyTo())
      ->equals(array($this->reply_to['reply_to_email'] => $this->reply_to['reply_to_name']));
    expect($message->getSubject())
      ->equals($this->newsletter['subject']);
    expect($message->getBody())
      ->equals($this->newsletter['body']['html']);
    expect($message->getChildren()[0]->getContentType())
      ->equals('text/plain');
  }

  function testItCanProcessSubscriber() {
    expect($this->mailer->processSubscriber('test@test.com'))
      ->equals(array('test@test.com' => ''));
    expect($this->mailer->processSubscriber('First <test@test.com>'))
      ->equals(array('test@test.com' => 'First'));
    expect($this->mailer->processSubscriber('First Last <test@test.com>'))
      ->equals(array('test@test.com' => 'First Last'));
  }

  function testItCanSend() {
    if(getenv('WP_TEST_MAILER_ENABLE_SENDING') !== 'true') return;
    $result = $this->mailer->send(
      $this->newsletter,
      $this->subscriber
    );
    expect($result['response'])->true();
  }
}