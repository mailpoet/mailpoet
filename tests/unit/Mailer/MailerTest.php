<?php
use MailPoet\Mailer\Mailer;

class MailerTest extends MailPoetTest {
  function _before() {
    $this->sender = array(
      'name' => 'Sender',
      'address' => 'staff@mailinator.com'
    );
    $this->reply_to = array(
      'name' => 'Reply To',
      'address' => 'staff@mailinator.com'
    );
    $this->mailer = array(
      'method' => 'MailPoet',
      'mailpoet_api_key' => 'dhNSqj1XHkVltIliyQDvMiKzQShOA5rs0m_DdRUVZHU'
    );
    $this->subscriber = 'Recipient <mailpoet-phoenix-test@mailinator.com>';
    $this->newsletter = array(
      'subject' => 'testing Mailer',
      'body' => array(
        'html' => 'HTML body',
        'text' => 'TEXT body'
      )
    );
  }

  function testItRequiresMailerMethod() {
    try {
      $mailer = new Mailer();
    } catch (Exception $e) {
      expect($e->getMessage())->equals('Mailer is not configured.');
    }
  }

  function testItRequiresSender() {
    try {
      $mailer = new Mailer($mailer = $this->mailer);
    } catch (Exception $e) {
      expect($e->getMessage())->equals('Sender name and email are not configured.');
    }
  }

  function testItCanConstruct() {
    $mailer = new Mailer($this->mailer, $this->sender, $this->reply_to);
    expect($mailer->sender['from_name'])->equals($this->sender['name']);
    expect($mailer->sender['from_email'])->equals($this->sender['address']);
    expect($mailer->reply_to['reply_to_name'])->equals($this->reply_to['name']);
    expect($mailer->reply_to['reply_to_email'])->equals($this->reply_to['address']);
  }

  function testItCanBuildMailerInstance() {
    $mailer = new Mailer($this->mailer, $this->sender);
    expect(get_class($mailer->mailer_instance))
      ->equals('MailPoet\Mailer\Methods\MailPoet');
  }

  function testItCanAbortWhenMethodDoesNotExist() {
    try {
      $mailer = new Mailer(array('method' => 'test'), $this->sender);
    } catch (Exception $e) {
      expect($e->getMessage())->equals('Mailing method does not exist.');
    }
  }

  function testItCanTransformSubscriber() {
    $mailer = new Mailer($this->mailer, $this->sender, $this->reply_to);
    expect($mailer->transformSubscriber('test@email.com'))
      ->equals('test@email.com');
    expect($mailer->transformSubscriber(
      array(
        'email' => 'test@email.com'
      ))
    )->equals('test@email.com');
    expect($mailer->transformSubscriber(
      array(
        'first_name' => 'First',
        'email' => 'test@email.com'
      ))
    )->equals('First <test@email.com>');
    expect($mailer->transformSubscriber(
      array(
        'last_name' => 'Last',
        'email' => 'test@email.com'
      ))
    )->equals('Last <test@email.com>');
    expect($mailer->transformSubscriber(
      array(
        'first_name' => 'First',
        'last_name' => 'Last',
        'email' => 'test@email.com'
      ))
    )->equals('First Last <test@email.com>');
  }

  function testItCanSend() {
    if(getenv('WP_TEST_MAILER_ENABLE_SENDING') !== 'true') return;
    $mailer = new Mailer($this->mailer, $this->sender, $this->reply_to);
    expect($mailer->send($this->newsletter, $this->subscriber))->true();
  }
}