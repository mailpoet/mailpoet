<?php declare(strict_types = 1);

namespace MailPoet\Test\Mailer\Methods;

use Codeception\Stub;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\Methods\Common\BlacklistCheck;
use MailPoet\Mailer\Methods\ErrorMappers\SMTPMapper;
use MailPoet\Mailer\Methods\SMTP;
use MailPoet\WP\Functions as WPFunctions;

//phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

class SMTPTest extends \MailPoetTest {
  public $extraParams;
  public $newsletter;
  public $subscriber;
  /** @var SMTP */
  public $mailer;
  public $returnPath;
  public $replyTo;
  public $sender;
  public $settings;

  public function _before() {
    parent::_before();
    $this->settings = [
      'method' => 'SMTP',
      'host' => getenv('WP_TEST_MAILER_SMTP_HOST') ?
        getenv('WP_TEST_MAILER_SMTP_HOST') :
        'example.com',
      'port' => 587,
      'login' => getenv('WP_TEST_MAILER_SMTP_LOGIN') ?
        getenv('WP_TEST_MAILER_SMTP_LOGIN') :
        'example.com',
      'password' => getenv('WP_TEST_MAILER_SMTP_PASSWORD') ?
        getenv('WP_TEST_MAILER_SMTP_PASSWORD') :
        'example.com',
      'authentication' => '1',
      'encryption' => 'tls',
    ];
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
    $this->mailer = new SMTP(
      $this->settings['host'],
      $this->settings['port'],
      (int)$this->settings['authentication'],
      $this->settings['encryption'],
      $this->sender,
      $this->replyTo,
      $this->returnPath,
      new SMTPMapper(),
      $this->settings['login'],
      $this->settings['password']
    );
    $this->subscriber = 'Recipient <blackhole@mailpoet.com>';
    $this->newsletter = [
      'subject' => 'testing SMTP … © & ěščřžýáíéůėę€żąß∂ 😊👨‍👩‍👧‍👧', // try some special chars
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
    verify($mailer->Host)
      ->equals($this->settings['host']);
    verify($mailer->Port)
      ->equals($this->settings['port']);
    verify($mailer->Username)
      ->equals($this->settings['login']);
    verify($mailer->Password)
      ->equals($this->settings['password']);
    verify($mailer->SMTPSecure)
      ->equals($this->settings['encryption']);
  }

  public function testItCanCreateMessage() {
    $mailer = $this->mailer
      ->configureMailerWithMessage($this->newsletter, $this->subscriber, $this->extraParams);
    verify($mailer->CharSet)->equals('UTF-8'); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    verify($mailer->getToAddresses())->equals([
        ['blackhole@mailpoet.com', 'Recipient'],
    ]);
    verify($mailer->getAllRecipientAddresses())->equals(['blackhole@mailpoet.com' => true]);
    verify($mailer->From)->equals($this->sender['from_email']); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    verify($mailer->FromName)->equals($this->sender['from_name']); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    verify($mailer->getReplyToAddresses())->equals([
      'reply-to@mailpoet.com' => ['reply-to@mailpoet.com', 'Reply To'],
    ]);
    verify($mailer->Sender)->equals($this->returnPath); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    verify($mailer->ContentType)->equals('text/html'); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    verify($mailer->Subject)->equals($this->newsletter['subject']); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    verify($mailer->Body)->equals($this->newsletter['body']['html']); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    verify($mailer->AltBody)->equals($this->newsletter['body']['text']); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    verify($mailer->getCustomHeaders())->equals([['List-Unsubscribe', '<https://www.mailpoet.com>']]);
  }

  public function testItCanProcessSubscriber() {
    verify($this->mailer->processSubscriber('test@test.com'))
      ->equals([
        'email' => 'test@test.com',
        'name' => '',
      ]);
    verify($this->mailer->processSubscriber('First <test@test.com>'))
      ->equals([
        'email' => 'test@test.com',
        'name' => 'First',
      ]);
    verify($this->mailer->processSubscriber('First Last <test@test.com>'))
      ->equals([
        'email' => 'test@test.com',
        'name' => 'First Last',
      ]);
  }

  public function testItCantSendWithoutProperAuthentication() {
    if (getenv('WP_TEST_MAILER_ENABLE_SENDING') !== 'true') $this->markTestSkipped();
    $this->mailer->login = 'someone';
    $this->mailer->mailer = $this->mailer->buildMailer();
    $result = $this->mailer->send(
      $this->newsletter,
      $this->subscriber
    );
    expect($result['response'])->false();
  }

  public function testItAppliesSMTPOptionsFilter() {
    $mailer = $this->mailer->buildMailer();
    expect($mailer->SMTPOptions)->isEmpty();
    (new WPFunctions)->addFilter(
      'mailpoet_mailer_smtp_options',
      function() {
        return [
          'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
          ],
        ];
      }
    );
    $mailer = $this->mailer->buildMailer();
    verify($mailer->SMTPOptions)->equals(
      [
        'ssl' => [
          'verify_peer' => false,
          'verify_peer_name' => false,
        ],
      ]
    );
  }

  public function testItAppliesTimeoutFilter() {
    $mailer = $this->mailer->buildMailer();
    verify($mailer->Timeout)->equals(\MailPoet\Mailer\Methods\SMTP::SMTP_CONNECTION_TIMEOUT);
    (new WPFunctions)->addFilter(
      'mailpoet_mailer_smtp_connection_timeout',
      function() {
        return 20;
      }
    );
    $mailer = $this->mailer->buildMailer();
    verify($mailer->Timeout)->equals(20);
  }

  public function testItChecksBlacklistBeforeSending() {
    $blacklistedSubscriber = 'blacklist_test@example.com';
    $blacklist = Stub::make(new BlacklistCheck(), ['isBlacklisted' => true], $this);
    $mailer = Stub::make(
      $this->mailer,
      ['blacklist' => $blacklist, 'errorMapper' => new SMTPMapper()],
      $this
    );
    $result = $mailer->send(
      $this->newsletter,
      $blacklistedSubscriber
    );
    expect($result['response'])->false();
    expect($result['error'])->isInstanceOf(MailerError::class);
    expect($result['error']->getMessage())->stringContainsString('SMTP has returned an unknown error.');
  }

  public function testItCanSend() {
    if (getenv('WP_TEST_MAILER_ENABLE_SENDING') !== 'true') $this->markTestSkipped();
    $result = $this->mailer->send(
      $this->newsletter,
      $this->subscriber
    );
    verify($result['response'])->true();
  }

  public function testItAppliesSMTPFilters(): void {
    $wp = new WPFunctions();
    $wp->addFilter('mailpoet_mailer_smtp_host', function() {
      return 'filter_host';
    });
    $wp->addFilter('mailpoet_mailer_smtp_port', function() {
      return 'filter_port';
    });
    $wp->addFilter('mailpoet_mailer_smtp_encryption', function() {
      return 'filter_encryption';
    });
    $wp->addFilter('mailpoet_mailer_smtp_username', function() {
      return 'filter_username';
    });
    $wp->addFilter('mailpoet_mailer_smtp_password', function() {
      return 'filter_password';
    });

    $mailer = $this->mailer->buildMailer();
    verify($mailer->Host)->equals('filter_host');
    verify($mailer->Port)->equals('filter_port');
    verify($mailer->SMTPSecure)->equals('filter_encryption');
    verify($mailer->SMTPAuth)->equals(true);
    verify($mailer->Username)->equals('filter_username');
    verify($mailer->Password)->equals('filter_password');
  }
}
