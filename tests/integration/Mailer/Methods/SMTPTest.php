<?php

namespace MailPoet\Test\Mailer\Methods;

use Codeception\Stub;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\Methods\Common\BlacklistCheck;
use MailPoet\Mailer\Methods\ErrorMappers\SMTPMapper;
use MailPoet\Mailer\Methods\SMTP;
use MailPoet\WP\Functions as WPFunctions;

class SMTPTest extends \MailPoetTest {
  function _before() {
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
    $this->reply_to = [
      'reply_to_name' => 'Reply To',
      'reply_to_email' => 'reply-to@mailpoet.com',
      'reply_to_name_email' => 'Reply To <reply-to@mailpoet.com>',
    ];
    $this->return_path = 'bounce@mailpoet.com';
    $this->mailer = new SMTP(
      $this->settings['host'],
      $this->settings['port'],
      $this->settings['authentication'],
      $this->settings['login'],
      $this->settings['password'],
      $this->settings['encryption'],
      $this->sender,
      $this->reply_to,
      $this->return_path,
      new SMTPMapper()
    );
    $this->subscriber = 'Recipient <mailpoet-phoenix-test@mailinator.com>';
    $this->newsletter = [
      'subject' => 'testing SMTP … © & ěščřžýáíéůėę€żąß∂ 😊👨‍👩‍👧‍👧', // try some special chars
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
    expect($mailer->getTransport()->getHost())
      ->equals($this->settings['host']);
    expect($mailer->getTransport()->getPort())
      ->equals($this->settings['port']);
    expect($mailer->getTransport()->getUsername())
      ->equals($this->settings['login']);
    expect($mailer->getTransport()->getPassword())
      ->equals($this->settings['password']);
    expect($mailer->getTransport()->getEncryption())
      ->equals($this->settings['encryption']);
  }

  function testWhenReturnPathIsNullItIsSetToSenderEmail() {
    $mailer = new SMTP(
      $this->settings['host'],
      $this->settings['port'],
      $this->settings['authentication'],
      $this->settings['login'],
      $this->settings['password'],
      $this->settings['encryption'],
      $this->sender,
      $this->reply_to,
      $return_path = false,
      new SMTPMapper()
    );
    expect($mailer->return_path)->equals($this->sender['from_email']);
  }

  function testItCanCreateMessage() {
    $message = $this->mailer
      ->createMessage($this->newsletter, $this->subscriber, $this->extra_params);
    expect($message->getTo())
      ->equals(['mailpoet-phoenix-test@mailinator.com' => 'Recipient']);
    expect($message->getFrom())
      ->equals([$this->sender['from_email'] => $this->sender['from_name']]);
    expect($message->getSender())
      ->equals([$this->sender['from_email'] => null]);
    expect($message->getReplyTo())
      ->equals([$this->reply_to['reply_to_email'] => $this->reply_to['reply_to_name']]);
    expect($message->getSubject())
      ->equals($this->newsletter['subject']);
    expect($message->getBody())
      ->equals($this->newsletter['body']['html']);
    expect($message->getChildren()[0]->getContentType())
      ->equals('text/plain');
    expect($message->getHeaders()->get('List-Unsubscribe')->getValue())
      ->equals('<' . $this->extra_params['unsubscribe_url'] . '>');
  }

  function testItCanProcessSubscriber() {
    expect($this->mailer->processSubscriber('test@test.com'))
      ->equals(['test@test.com' => '']);
    expect($this->mailer->processSubscriber('First <test@test.com>'))
      ->equals(['test@test.com' => 'First']);
    expect($this->mailer->processSubscriber('First Last <test@test.com>'))
      ->equals(['test@test.com' => 'First Last']);
  }

  function testItCantSendWithoutProperAuthentication() {
    if (getenv('WP_TEST_MAILER_ENABLE_SENDING') !== 'true') $this->markTestSkipped();
    $this->mailer->login = 'someone';
    $this->mailer->mailer = $this->mailer->buildMailer();
    $result = $this->mailer->send(
      $this->newsletter,
      $this->subscriber
    );
    expect($result['response'])->false();
  }

  function testItAppliesTransportFilter() {
    $mailer = $this->mailer->buildMailer();
    expect($mailer->getTransport()->getStreamOptions())->isEmpty();
    (new WPFunctions)->addFilter(
      'mailpoet_mailer_smtp_transport_agent',
      function($transport) {
        $transport->setStreamOptions(
          [
            'ssl' => [
              'verify_peer' => false,
              'verify_peer_name' => false,
            ],
          ]
        );
        return $transport;
      }
    );
    $mailer = $this->mailer->buildMailer();
    expect($mailer->getTransport()->getStreamOptions())->equals(
      [
        'ssl' => [
          'verify_peer' => false,
          'verify_peer_name' => false,
        ],
      ]
    );
  }

  function testItAppliesTimeoutFilter() {
    $mailer = $this->mailer->buildMailer();
    expect($mailer->getTransport()->getTimeout())->equals(\MailPoet\Mailer\Methods\SMTP::SMTP_CONNECTION_TIMEOUT);
    (new WPFunctions)->addFilter(
      'mailpoet_mailer_smtp_connection_timeout',
      function() {
        return 20;
      }
    );
    $mailer = $this->mailer->buildMailer();
    expect($mailer->getTransport()->getTimeout())->equals(20);
  }

  function testItChecksBlacklistBeforeSending() {
    $blacklisted_subscriber = 'blacklist_test@example.com';
    $blacklist = Stub::make(new BlacklistCheck(), ['isBlacklisted' => true], $this);
    $mailer = Stub::make(
      $this->mailer,
      ['blacklist' => $blacklist, 'error_mapper' => new SMTPMapper()],
      $this
    );
    $result = $mailer->send(
      $this->newsletter,
      $blacklisted_subscriber
    );
    expect($result['response'])->false();
    expect($result['error'])->isInstanceOf(MailerError::class);
    expect($result['error']->getMessage())->contains('SMTP has returned an unknown error.');
  }

  function testItCanSend() {
    if (getenv('WP_TEST_MAILER_ENABLE_SENDING') !== 'true') $this->markTestSkipped();
    $result = $this->mailer->send(
      $this->newsletter,
      $this->subscriber
    );
    expect($result['response'])->true();
  }

  function _after() {
  }
}
