<?php

namespace MailPoet\Test\Mailer\Methods;

use Codeception\Stub;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\Methods\Common\BlacklistCheck;
use MailPoet\Mailer\Methods\ErrorMappers\PHPMailMapper;
use MailPoet\Mailer\Methods\PHPMail;

class PHPMailTest extends \MailPoetTest {
  public $extraParams;
  public $newsletter;
  public $subscriber;
  public $mailer;
  public $returnPath;
  public $replyTo;
  public $sender;

  public function _before() {
    parent::_before();
    $this->sender = [
      'from_name' => 'Sender',
      'from_email' => 'staff@mailpoet.com',
      'from_name_email' => 'Sender <staff@mailpoet.com>',
    ];
    $this->replyTo = [
      'reply_to_name' => 'Reply To',
      'reply_to_email' => 'reply-to@mailpoet.com',
      'reply_to_name_email' => 'Reply To <reply-to@mailpoet.com>',
    ];
    $this->returnPath = 'bounce@mailpoet.com';
    $this->mailer = new PHPMail(
      $this->sender,
      $this->replyTo,
      $this->returnPath,
      new PHPMailMapper()
    );
    $this->subscriber = 'Recipient <mailpoet-phoenix-test@mailinator.com>';
    $this->newsletter = [
      'subject' => 'testing local method (PHP mail) … © & ěščřžýáíéůėę€żąß∂ 😊👨‍👩‍👧‍👧', // try some special chars
      'body' => [
        'html' => 'HTML body',
        'text' => 'TEXT body',
      ],
    ];
    $this->extraParams = [
      'unsubscribe_url' => 'http://www.mailpoet.com',
    ];
  }

  public function testItCanBuildMailer() {
    $mailer = $this->mailer->buildMailer();
    expect($mailer)->isInstanceOf('PHPMailer');

    // uses PHP's mail() function
    expect($mailer->Mailer)->equals('mail'); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
  }

  public function testWhenReturnPathIsNullItIsSetToSenderEmail() {
    $mailer = new PHPMail(
      $this->sender,
      $this->replyTo,
      $returnPath = false,
      new PHPMailMapper()
    );
    expect($mailer->returnPath)->equals($this->sender['from_email']);
  }

  public function testItCanConfigureMailerWithMessage() {
    $mailer = $this->mailer
      ->configureMailerWithMessage($this->newsletter, $this->subscriber, $this->extraParams);
    expect($mailer->CharSet)->equals('UTF-8'); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    expect($mailer->getToAddresses())->equals(
      [
        [
          'mailpoet-phoenix-test@mailinator.com',
          'Recipient',
        ],
      ]
    );
    expect($mailer->getAllRecipientAddresses())
      ->equals(['mailpoet-phoenix-test@mailinator.com' => true]);
    expect($mailer->From)->equals($this->sender['from_email']); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    expect($mailer->FromName)->equals($this->sender['from_name']); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    expect($mailer->getReplyToAddresses())->equals(
      [
        'reply-to@mailpoet.com' => [
          'reply-to@mailpoet.com',
          'Reply To',
        ],
      ]
    );
    expect($mailer->Sender)->equals($this->returnPath); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    expect($mailer->ContentType)->equals('text/html'); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    expect($mailer->Subject)->equals($this->newsletter['subject']); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    expect($mailer->Body) // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
      ->equals($this->newsletter['body']['html']);
    expect($mailer->AltBody) // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
      ->equals($this->newsletter['body']['text']);
    expect($mailer->getCustomHeaders())->equals(
      [
        [
          'List-Unsubscribe',
          'http://www.mailpoet.com',
        ],
      ]
    );
  }

  public function testItCanConfigureMailerWithTextEmail() {
    $mailer = $this->mailer
      ->configureMailerWithMessage([
        'subject' => 'testing local method (PHP mail)',
        'body' => [
          'text' => 'TEXT body',
        ],
      ], $this->subscriber);
    expect($mailer->ContentType)->equals('text/plain'); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    expect($mailer->Body)->equals('TEXT body'); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
  }

  public function testItCanProcessSubscriber() {
    expect($this->mailer->processSubscriber('test@test.com'))->equals(
      [
        'email' => 'test@test.com',
        'name' => '',
      ]);
    expect($this->mailer->processSubscriber('First <test@test.com>'))->equals(
      [
        'email' => 'test@test.com',
        'name' => 'First',
      ]);
    expect($this->mailer->processSubscriber('First Last <test@test.com>'))->equals(
      [
        'email' => 'test@test.com',
        'name' => 'First Last',
      ]);
  }

  public function testItChecksBlacklistBeforeSending() {
    $blacklistedSubscriber = 'blacklist_test@example.com';
    $blacklist = Stub::make(new BlacklistCheck(), ['isBlacklisted' => true], $this);
    $mailer = Stub::make(
      $this->mailer,
      ['blacklist' => $blacklist, 'errorMapper' => new PHPMailMapper()],
      $this
    );
    $result = $mailer->send(
      $this->newsletter,
      $blacklistedSubscriber
    );
    expect($result['response'])->false();
    expect($result['error'])->isInstanceOf(MailerError::class);
    expect($result['error']->getMessage())->contains('PHPMail has returned an unknown error.');
  }

  public function testItCanSend() {
    if (getenv('WP_TEST_MAILER_ENABLE_SENDING') !== 'true') $this->markTestSkipped();
    $result = $this->mailer->send(
      $this->newsletter,
      $this->subscriber
    );
    expect($result['response'])->true();
  }
}
