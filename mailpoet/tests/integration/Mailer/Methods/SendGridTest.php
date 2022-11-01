<?php

namespace MailPoet\Test\Mailer\Methods;

use Codeception\Stub;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\Methods\Common\BlacklistCheck;
use MailPoet\Mailer\Methods\ErrorMappers\SendGridMapper;
use MailPoet\Mailer\Methods\SendGrid;

class SendGridTest extends \MailPoetTest {
  public $extraParams;
  public $newsletter;
  public $subscriber;
  /** @var SendGrid */
  public $mailer;
  public $replyTo;
  public $sender;
  public $settings;

  public function _before() {
    parent::_before();
    $this->settings = [
      'method' => 'SendGrid',
      'api_key' => getenv('WP_TEST_MAILER_SENDGRID_API') ?
        getenv('WP_TEST_MAILER_SENDGRID_API') :
        '1234567890',
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
    $this->mailer = new SendGrid(
      $this->settings['api_key'],
      $this->sender,
      $this->replyTo,
      new SendGridMapper()
    );
    $this->subscriber = 'Recipient <blackhole@mailpoet.com>';
    $this->newsletter = [
      'subject' => 'testing SendGrid … © & ěščřžýáíéůėę€żąß∂ 😊👨‍👩‍👧‍👧', // try some special chars
      'body' => [
        'html' => 'HTML body',
        'text' => 'TEXT body',
      ],
    ];
    $this->extraParams = [
      'unsubscribe_url' => 'https://www.mailpoet.com',
    ];
  }

  public function testItCanGenerateBody() {
    $body = $this->mailer->getBody($this->newsletter, $this->subscriber, $this->extraParams);
    expect($body['to'])->stringContainsString($this->subscriber);
    expect($body['from'])->equals($this->sender['from_email']);
    expect($body['fromname'])->equals($this->sender['from_name']);
    expect($body['replyto'])->equals($this->replyTo['reply_to_email']);
    expect($body['subject'])->equals($this->newsletter['subject']);
    $headers = json_decode($body['headers'], true);
    expect($headers['List-Unsubscribe'])
      ->equals('<' . $this->extraParams['unsubscribe_url'] . '>');
    expect($body['html'])->equals($this->newsletter['body']['html']);
    expect($body['text'])->equals($this->newsletter['body']['text']);
  }

  public function testItCanCreateRequest() {
    $body = $this->mailer->getBody($this->newsletter, $this->subscriber);
    $request = $this->mailer->request($this->newsletter, $this->subscriber);
    expect($request['timeout'])->equals(10);
    expect($request['httpversion'])->equals('1.1');
    expect($request['method'])->equals('POST');
    expect($request['headers']['Authorization'])
      ->equals('Bearer ' . $this->settings['api_key']);
    expect($request['body'])->equals(http_build_query($body));
  }

  public function testItCanDoBasicAuth() {
    expect($this->mailer->auth())
      ->equals('Bearer ' . $this->settings['api_key']);
  }

  public function testItCannotSendWithoutProperApiKey() {
    if (getenv('WP_TEST_MAILER_ENABLE_SENDING') !== 'true') $this->markTestSkipped();
    $this->mailer->apiKey = 'someapi';
    $result = $this->mailer->send(
      $this->newsletter,
      $this->subscriber
    );
    expect($result['response'])->false();
  }

  public function testItChecksBlacklistBeforeSending() {
    $blacklistedSubscriber = 'blacklist_test@example.com';
    $blacklist = Stub::make(new BlacklistCheck(), ['isBlacklisted' => true], $this);
    $mailer = Stub::make(
      $this->mailer,
      ['blacklist' => $blacklist, 'errorMapper' => new SendGridMapper()],
      $this
    );
    $result = $mailer->send(
      $this->newsletter,
      $blacklistedSubscriber
    );
    expect($result['response'])->false();
    expect($result['error'])->isInstanceOf(MailerError::class);
    expect($result['error']->getMessage())->stringContainsString('SendGrid has returned an unknown error.');
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
