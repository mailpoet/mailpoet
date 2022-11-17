<?php declare(strict_types = 1);

namespace MailPoet\Test\Mailer;

use MailPoet\Mailer\MailerFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;

class MailerTest extends \MailPoetTest {
  public $newsletter;
  public $subscriber;
  public $mailer;
  public $returnPath;
  public $replyTo;
  public $sender;
  public $availableMailerMethods;

  /** @var MailerFactory */
  private $mailerFactory;

  public function _before() {
    parent::_before();
    $this->mailerFactory = $this->diContainer->get(MailerFactory::class);
    $this->sender = [
      'name' => 'Sender',
      'address' => 'staff@mailpoet.com',
    ];
    $this->replyTo = [
      'name' => 'Reply To',
      'address' => 'staff@mailpoet.com',
    ];
    $this->returnPath = 'bounce@test.com';
    $this->mailer = [
      'method' => 'MailPoet',
      'mailpoet_api_key' => getenv('WP_TEST_MAILER_MAILPOET_API') ?
        getenv('WP_TEST_MAILER_MAILPOET_API') :
        '1234567890',
    ];
    $this->subscriber = 'Recipient <blackhole@mailpoet.com>';
    $this->newsletter = [
      'subject' => 'testing Mailer',
      'body' => [
        'html' => 'HTML body',
        'text' => 'TEXT body',
      ],
    ];
  }

  public function testItCanTransformSubscriber() {
    $mailer = $this->mailerFactory->buildMailer($this->mailer, $this->sender, $this->replyTo);
    expect($mailer->formatSubscriberNameAndEmailAddress('test@email.com'))
      ->equals('test@email.com');
    expect($mailer->formatSubscriberNameAndEmailAddress(
      [
        'email' => 'test@email.com',
      ])
    )->equals('test@email.com');
    expect($mailer->formatSubscriberNameAndEmailAddress(
      [
        'first_name' => 'First',
        'email' => 'test@email.com',
      ])
    )->equals('First <test@email.com>');
    expect($mailer->formatSubscriberNameAndEmailAddress(
      [
        'last_name' => 'Last',
        'email' => 'test@email.com',
      ])
    )->equals('Last <test@email.com>');
    expect($mailer->formatSubscriberNameAndEmailAddress(
      [
        'first_name' => 'First',
        'last_name' => 'Last',
        'email' => 'test@email.com',
      ])
    )->equals('First Last <test@email.com>');
    expect($mailer->formatSubscriberNameAndEmailAddress(
      [
        'full_name' => 'First Last',
        'email' => 'test@email.com',
      ])
    )->equals('First Last <test@email.com>');
  }

  public function testItCanSend() {
    if (getenv('WP_TEST_MAILER_ENABLE_SENDING') !== 'true') $this->markTestSkipped();
    $this->sender['address'] = 'staff@mailpoet.com';
    $mailer = $this->mailerFactory->buildMailer($this->mailer, $this->sender, $this->replyTo);
    $result = $mailer->send($this->newsletter, $this->subscriber);
    expect($result['response'])->true();
  }

  public function testItCanSendWhenSubscriberEntityIsPassed() {
    if (getenv('WP_TEST_MAILER_ENABLE_SENDING') !== 'true') {
      $this->markTestSkipped();
    }

    $subscriberFactory = new SubscriberFactory();
    $subscriber = $subscriberFactory
      ->withEmail('blackhole@mailpoet.com')
      ->withFirstName('Recipient')
      ->create();
    $this->sender['address'] = 'staff@mailpoet.com';
    $mailer = $this->mailerFactory->buildMailer($this->mailer, $this->sender, $this->replyTo);
    $result = $mailer->send($this->newsletter, $subscriber);
    expect($result['response'])->true();
  }
}
