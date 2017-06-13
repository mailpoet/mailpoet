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
    $this->extra_params = array(
      'unsubscribe_url' => 'http://www.mailpoet.com'
    );
  }

  function testItCanBuildMailer() {
    $mailer = $this->mailer->buildMailer();
    expect($mailer)->isInstanceOf('PHPMailer');
    expect($mailer->Mailer)->equals('mail'); // uses PHP's mail() function
  }

  function testWhenReturnPathIsNullItIsSetToSenderEmail() {
    $mailer = new PHPMail(
      $this->sender,
      $this->reply_to,
      $return_path = false
    );
    expect($mailer->return_path)->equals($this->sender['from_email']);
  }

  function testItCanConfigureMailerWithMessage() {
    $mailer = $this->mailer
      ->configureMailerWithMessage($this->newsletter, $this->subscriber, $this->extra_params);
    expect($mailer->CharSet)->equals('UTF-8');
    expect($mailer->getToAddresses())->equals(
      array(
        array(
          'mailpoet-phoenix-test@mailinator.com',
          'Recipient'
        )
      )
    );
    expect($mailer->getAllRecipientAddresses())
      ->equals(array('mailpoet-phoenix-test@mailinator.com' => true));
    expect($mailer->From)->equals($this->sender['from_email']);
    expect($mailer->FromName)->equals($this->sender['from_name']);
    expect($mailer->getReplyToAddresses())->equals(
      array(
        'reply-to@mailpoet.com' => array(
          'reply-to@mailpoet.com',
          'Reply To'
        )
      )
    );
    expect($mailer->Sender)->equals($this->return_path);
    expect($mailer->ContentType)->equals('text/html');
    expect($mailer->Subject)->equals($this->newsletter['subject']);
    expect($mailer->Body)
      ->equals($this->newsletter['body']['html']);
    expect($mailer->AltBody)
      ->equals($this->newsletter['body']['text']);
    expect($mailer->getCustomHeaders())->equals(
      array(
        array(
          'List-Unsubscribe',
          'http://www.mailpoet.com'
        )
      )
    );
  }

  function testItCanProcessSubscriber() {
    expect($this->mailer->processSubscriber('test@test.com'))->equals(
      array(
        'email' => 'test@test.com',
        'name' => ''
      ));
    expect($this->mailer->processSubscriber('First <test@test.com>'))->equals(
      array(
        'email' => 'test@test.com',
        'name' => 'First'
      ));
    expect($this->mailer->processSubscriber('First Last <test@test.com>'))->equals(
      array(
        'email' => 'test@test.com',
        'name' => 'First Last'
      ));
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