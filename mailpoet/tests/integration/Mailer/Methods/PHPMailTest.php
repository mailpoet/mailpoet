<?php declare(strict_types = 1);

namespace MailPoet\Test\Mailer\Methods;

use Codeception\Stub;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\Methods\Common\BlacklistCheck;
use MailPoet\Mailer\Methods\ErrorMappers\PHPMailMapper;
use MailPoet\Mailer\Methods\PHPMail;
use PHPMailer\PHPMailer\PHPMailer;

class PHPMailTest extends \MailPoetTest {
  public $extraParams;
  public $newsletter;
  public $subscriber;
  /** @var PHPMail */
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
    $this->subscriber = 'Recipient <blackhole@mailpoet.com>';
    $this->newsletter = [
      'subject' => 'testing local method (PHP mail) … © & ěščřžýáíéůėę€żąß∂ 😊👨‍👩‍👧‍👧', // try some special chars
      'body' => [
        'html' => 'HTML body',
        'text' => 'TEXT body',
      ],
    ];
    $this->extraParams = [
      'unsubscribe_url' => 'https://www.mailpoet.com',
    ];
  }

  public function testItCanBuildMailer() {
    $mailer = $this->mailer->buildMailer();
    expect($mailer)->isInstanceOf(PHPMailer::class);

    // uses PHP's mail() function
    verify($mailer->Mailer)->equals('mail'); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }

  public function testItCanConfigureMailerWithMessage() {
    $mailer = $this->mailer
      ->configureMailerWithMessage($this->newsletter, $this->subscriber, $this->extraParams);
    verify($mailer->CharSet)->equals('UTF-8'); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    verify($mailer->getToAddresses())->equals(
      [
        [
          'blackhole@mailpoet.com',
          'Recipient',
        ],
      ]
    );
    verify($mailer->getAllRecipientAddresses())
      ->equals(['blackhole@mailpoet.com' => true]);
    verify($mailer->From)->equals($this->sender['from_email']); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    verify($mailer->FromName)->equals($this->sender['from_name']); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    verify($mailer->getReplyToAddresses())->equals(
      [
        'reply-to@mailpoet.com' => [
          'reply-to@mailpoet.com',
          'Reply To',
        ],
      ]
    );
    verify($mailer->Sender)->equals($this->returnPath); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    verify($mailer->ContentType)->equals('text/html'); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    verify($mailer->Subject)->equals($this->newsletter['subject']); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    verify($mailer->Body) // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      ->equals($this->newsletter['body']['html']);
    verify($mailer->AltBody) // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      ->equals($this->newsletter['body']['text']);
    verify($mailer->getCustomHeaders())->equals(
      [
        [
          'List-Unsubscribe',
          '<https://www.mailpoet.com>',
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
    verify($mailer->ContentType)->equals('text/plain'); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    verify($mailer->Body)->equals('TEXT body'); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }

  public function testItCanProcessSubscriber() {
    verify($this->mailer->processSubscriber('test@test.com'))->equals(
      [
        'email' => 'test@test.com',
        'name' => '',
      ]);
    verify($this->mailer->processSubscriber('First <test@test.com>'))->equals(
      [
        'email' => 'test@test.com',
        'name' => 'First',
      ]);
    verify($this->mailer->processSubscriber('First Last <test@test.com>'))->equals(
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
    verify($result['response'])->false();
    expect($result['error'])->isInstanceOf(MailerError::class);
    expect($result['error']->getMessage())->stringContainsString('PHPMail has returned an unknown error.');
  }

  public function testItCanSend() {
    if (getenv('WP_TEST_MAILER_ENABLE_SENDING') !== 'true') $this->markTestSkipped();
    $result = $this->mailer->send(
      $this->newsletter,
      $this->subscriber
    );
    verify($result['response'])->true();
  }

  public function testItCanValidateEmailAddresses() {
    // a call to wp_mail can override PHPMailer's validator to is_email, which is
    // less strict. This ensures the default of 'php' is set in case a previous test
    // caused an override.
    $this->mailer->mailer::$validator = 'php';
    $result = $this->mailer->mailer::validateAddress('john.@doe.com');
    verify($result)->false();
  }
}
