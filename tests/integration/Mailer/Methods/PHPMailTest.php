<?php

namespace MailPoet\Test\Mailer\Methods;

use Codeception\Stub;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\Methods\Common\BlacklistCheck;
use MailPoet\Mailer\Methods\ErrorMappers\PHPMailMapper;
use MailPoet\Mailer\Methods\PHPMail;

class PHPMailTest extends \MailPoetTest {
  public $extra_params;
  public $newsletter;
  public $subscriber;
  public $mailer;
  public $return_path;
  public $reply_to;
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
    expect($mailer->mailer)->equals('mail'); // uses PHP's mail() function
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
    expect($mailer->charSet)->equals('UTF-8');
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
    expect($mailer->from)->equals($this->sender['from_email']);
    expect($mailer->fromName)->equals($this->sender['from_name']);
    expect($mailer->getReplyToAddresses())->equals(
      [
        'reply-to@mailpoet.com' => [
          'reply-to@mailpoet.com',
          'Reply To',
        ],
      ]
    );
    expect($mailer->sender)->equals($this->returnPath);
    expect($mailer->contentType)->equals('text/html');
    expect($mailer->subject)->equals($this->newsletter['subject']);
    expect($mailer->body)
      ->equals($this->newsletter['body']['html']);
    expect($mailer->altBody)
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
    expect($mailer->contentType)->equals('text/plain');
    expect($mailer->body)->equals('TEXT body');
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
      ['blacklist' => $blacklist, 'error_mapper' => new PHPMailMapper()],
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
