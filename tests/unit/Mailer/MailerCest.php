<?php
use MailPoet\Mailer\Mailer;

class MailerCest {
  function _before() {
    $this->sender = array(
      'name' => 'Sender',
      'address' => 'staff@mailinator.com'
    );
    $this->replyTo = array(
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

  function itRequiresMailerMethod() {
    try {
      $mailer = new Mailer();
    } catch (Exception $e) {
      expect($e->getMessage())->equals('Mailer is not configured.');
    }
  }

  function itRequiresSender() {
    try {
      $mailer = new Mailer($mailer = $this->mailer);
    } catch (Exception $e) {
      expect($e->getMessage())->equals('Sender name and email are not configured.');
    }
  }

  function itCanConstruct() {
    $mailer = new Mailer($this->mailer, $this->sender, $this->replyTo);
    expect($mailer->sender['fromName'])->equals($this->sender['name']);
    expect($mailer->sender['fromEmail'])->equals($this->sender['address']);
    expect($mailer->replyTo['replyToName'])->equals($this->replyTo['name']);
    expect($mailer->replyTo['replyToEmail'])->equals($this->replyTo['address']);
  }

  function itCanBuildMailerInstance() {
    $mailer = new Mailer($this->mailer, $this->sender);
    expect(get_class($mailer->mailerInstance))
      ->equals('MailPoet\Mailer\Methods\MailPoet');
  }

  function itCanAbortWhenMethodDoesNotExist() {
    try {
      $mailer = new Mailer(array('method' => 'test'), $this->sender);
    } catch (Exception $e) {
      expect($e->getMessage())->equals('Mailing method does not exist.');
    }
  }

  function itCanTransformSubscriber() {
    $mailer = new Mailer($this->mailer, $this->sender, $this->replyTo);
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

  function itCanSend() {
    $mailer = new Mailer($this->mailer, $this->sender, $this->replyTo);
    expect($mailer->send($this->newsletter, $this->subscriber))->true();
  }
}