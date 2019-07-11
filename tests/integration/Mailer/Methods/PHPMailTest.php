<?php
namespace MailPoet\Test\Mailer\Methods;

use Codeception\Stub;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\Methods\ErrorMappers\PHPMailMapper;
use MailPoet\Mailer\Methods\PHPMail;
use MailPoet\Subscription\Blacklist;

class PHPMailTest extends \MailPoetTest {
  function _before() {
    parent::_before();
    $this->sender = [
      'from_name' => 'Sender',
      'from_email' => 'staff@mailpoet.com',
      'from_name_email' => 'Sender <staff@mailpoet.com>',
    ];
    $this->reply_to = [
      'reply_to_name' => 'Reply To',
      'reply_to_email' => 'reply-to@mailpoet.com',
      'reply_to_name_email' => 'Reply To <reply-to@mailpoet.com>',
    ];
    $this->return_path = 'bounce@mailpoet.com';
    $this->mailer = new PHPMail(
      $this->sender,
      $this->reply_to,
      $this->return_path,
      new PHPMailMapper()
    );
    $this->subscriber = 'Recipient <mailpoet-phoenix-test@mailinator.com>';
    $this->newsletter = [
      'subject' => 'testing local method (PHP mail)',
      'body' => [
        'html' => 'HTML body',
        'text' => 'TEXT body',
      ],
    ];
    $this->extra_params = [
      'unsubscribe_url' => 'http://www.mailpoet.com',
    ];
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
      $return_path = false,
      new PHPMailMapper()
    );
    expect($mailer->return_path)->equals($this->sender['from_email']);
  }

  function testItCanConfigureMailerWithMessage() {
    $mailer = $this->mailer
      ->configureMailerWithMessage($this->newsletter, $this->subscriber, $this->extra_params);
    expect($mailer->CharSet)->equals('UTF-8');
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
    expect($mailer->From)->equals($this->sender['from_email']);
    expect($mailer->FromName)->equals($this->sender['from_name']);
    expect($mailer->getReplyToAddresses())->equals(
      [
        'reply-to@mailpoet.com' => [
          'reply-to@mailpoet.com',
          'Reply To',
        ],
      ]
    );
    expect($mailer->Sender)->equals($this->return_path);
    expect($mailer->ContentType)->equals('text/html');
    expect($mailer->Subject)->equals($this->newsletter['subject']);
    expect($mailer->Body)
      ->equals($this->newsletter['body']['html']);
    expect($mailer->AltBody)
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

  function testItCanProcessSubscriber() {
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

  function testItChecksBlacklistBeforeSending() {
    $blacklisted_subscriber = 'blacklist_test@example.com';
    $blacklist = Stub::make(new Blacklist(), ['isBlacklisted' => true], $this);
    $mailer = Stub::make(
      $this->mailer,
      ['blacklist' => $blacklist, 'error_mapper' => new PHPMailMapper()],
      $this
    );
    $result = $mailer->send(
      $this->newsletter,
      $blacklisted_subscriber
    );
    expect($result['response'])->false();
    expect($result['error'])->isInstanceOf(MailerError::class);
    expect($result['error']->getMessage())->contains('PHPMail has returned an unknown error.');
  }

  function testItCanSend() {
    if (getenv('WP_TEST_MAILER_ENABLE_SENDING') !== 'true') return;
    $result = $this->mailer->send(
      $this->newsletter,
      $this->subscriber
    );
    expect($result['response'])->true();
  }
}
