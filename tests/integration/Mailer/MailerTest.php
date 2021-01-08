<?php

namespace MailPoet\Test\Mailer;

use MailPoet\Mailer\Mailer;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\SettingsRepository;

class MailerTest extends \MailPoetTest {
  public $newsletter;
  public $subscriber;
  public $mailer;
  public $returnPath;
  public $replyTo;
  public $sender;
  public $availableMailerMethods;

  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $this->availableMailerMethods = [
      [
        'method' => 'AmazonSES',
        'region' => 'us-west-2',
        'access_key' => '1234567890',
        'secret_key' => 'abcdefghijk',
      ],
      [
        'method' => 'MailPoet',
        'mailpoet_api_key' => 'abcdefghijk',
      ],
      [
        'method' => 'SendGrid',
        'api_key' => 'abcdefghijk',
      ],
      [
        'method' => 'PHPMail',
      ],
      [
        'method' => 'SMTP',
        'host' => 'example.com',
        'port' => 25,
        'authentication' => true,
        'login' => 'username',
        'password' => 'password',
        'encryption' => 'tls',
      ],
    ];
    $this->sender = [
      'name' => 'Sender',
      'address' => 'staff@mailinator.com',
    ];
    $this->replyTo = [
      'name' => 'Reply To',
      'address' => 'staff@mailinator.com',
    ];
    $this->returnPath = 'bounce@test.com';
    $this->mailer = [
      'method' => 'MailPoet',
      'mailpoet_api_key' => getenv('WP_TEST_MAILER_MAILPOET_API') ?
        getenv('WP_TEST_MAILER_MAILPOET_API') :
        '1234567890',
    ];
    $this->subscriber = 'Recipient <mailpoet-phoenix-test@mailinator.com>';
    $this->newsletter = [
      'subject' => 'testing Mailer',
      'body' => [
        'html' => 'HTML body',
        'text' => 'TEXT body',
      ],
    ];
    $this->settings = SettingsController::getInstance();
  }

  public function testItRequiresMailerMethod() {
    // reset mta settings so that we have no default mailer
    $this->settings->set('mta', null);
    try {
      $mailer = new Mailer();
      $mailer->init();
      $this->fail('Mailer did not throw an exception');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('Mailer is not configured.');
    }
  }

  public function testItRequiresSender() {
    try {
      $mailer = new Mailer();
      $mailer->init($mailer = $this->mailer);
      $this->fail('Mailer did not throw an exception');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('Sender name and email are not configured.');
    }
  }

  public function testItCanConstruct() {
    $mailer = new Mailer();
    $mailer->init($this->mailer, $this->sender, $this->replyTo, $this->returnPath);
    expect($mailer->sender['from_name'])->equals($this->sender['name']);
    expect($mailer->sender['from_email'])->equals($this->sender['address']);
    expect($mailer->replyTo['reply_to_name'])->equals($this->replyTo['name']);
    expect($mailer->replyTo['reply_to_email'])->equals($this->replyTo['address']);
    expect($mailer->returnPath)->equals($this->returnPath);
  }

  public function testItThrowsUnknownMailerException() {
    try {
      $mailer = new Mailer();
      $mailer->init(['method' => 'Unknown'], $this->sender);
      $this->fail('Mailer did not throw an exception');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('Mailing method does not exist.');
    }
  }

  public function testItSetsReplyToAddressWhenOnlyNameIsAvailable() {
    $replyTo = ['name' => 'test'];
    $mailer = new Mailer();
    $mailer->init($this->mailer, $this->sender, $replyTo);
    $replyTo = $mailer->getReplyToNameAndAddress();
    expect($replyTo['reply_to_email'])->equals($this->sender['address']);
  }

  public function testItGetsReturnPathAddress() {
    $mailer = new Mailer();
    $mailer->init($this->mailer, $this->sender, $this->replyTo);
    $returnPath = $mailer->getReturnPathAddress('bounce@test.com');
    expect($returnPath)->equals('bounce@test.com');
    $this->settings->set('bounce', ['address' => 'settngs_bounce@test.com']);
    $returnPath = $mailer->getReturnPathAddress($returnPath = false);
    expect($returnPath)->equals('settngs_bounce@test.com');
  }

  public function testItCanTransformSubscriber() {
    $mailer = new Mailer();
    $mailer->init($this->mailer, $this->sender, $this->replyTo);
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

  public function testItCanConvertNonASCIIEmailAddressString() {
    $mailer = new Mailer();
    $mailer->init($this->mailer, $this->sender, $this->replyTo);
    expect($mailer->sender['from_name'])->equals($this->sender['name']);
    expect($mailer->replyTo['reply_to_name'])->equals($this->replyTo['name']);
    $sender = [
      'name' => 'Sender Außergewöhnlichen тест системы',
      'address' => 'staff@mailinator.com',
    ];
    $replyTo = [
      'name' => 'Reply-To Außergewöhnlichen тест системы',
      'address' => 'staff@mailinator.com',
    ];
    $mailer = new Mailer();
    $mailer->init($this->mailer, $sender, $replyTo);
    expect($mailer->sender['from_name'])
      ->equals(sprintf('=?utf-8?B?%s?=', base64_encode($sender['name'])));
    expect($mailer->replyTo['reply_to_name'])
      ->equals(sprintf('=?utf-8?B?%s?=', base64_encode($replyTo['name'])));
  }

  public function testItCanSend() {
    if (getenv('WP_TEST_MAILER_ENABLE_SENDING') !== 'true') $this->markTestSkipped();
    $this->sender['address'] = 'staff@mailpoet.com';
    $mailer = new Mailer();
    $mailer->init($this->mailer, $this->sender, $this->replyTo);
    $result = $mailer->send($this->newsletter, $this->subscriber);
    expect($result['response'])->true();
  }

  public function testItIgnoresInvalidBounceAddress() {
    $this->settings->set('bounce.address', 'ok@address.com');
    $mailer = new Mailer();
    $mailer->init($this->mailer, $this->sender, $this->replyTo);
    expect($mailer->returnPath)->equals('ok@address.com');
    $this->settings->set('bounce.address', 'invalid');
    $mailer->init($this->mailer, $this->sender, $this->replyTo);
    expect($mailer->returnPath)->null();
  }

  public function _after() {
    $this->diContainer->get(SettingsRepository::class)->truncate();
  }
}
