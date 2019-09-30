<?php

namespace MailPoet\Test\Mailer\Methods;

use Codeception\Stub;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\Methods\ErrorMappers\SendGridMapper;
use MailPoet\Mailer\Methods\SendGrid;
use MailPoet\Mailer\Methods\Common\BlacklistCheck;

class SendGridTest extends \MailPoetTest {
  function _before() {
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
    $this->reply_to = [
      'reply_to_name' => 'Reply To',
      'reply_to_email' => 'reply-to@mailpoet.com',
      'reply_to_name_email' => 'Reply To <reply-to@mailpoet.com>',
    ];
    $this->mailer = new SendGrid(
      $this->settings['api_key'],
      $this->sender,
      $this->reply_to,
      new SendGridMapper()
    );
    $this->subscriber = 'Recipient <mailpoet-phoenix-test@mailinator.com>';
    $this->newsletter = [
      'subject' => 'testing SendGrid',
      'body' => [
        'html' => 'HTML body',
        'text' => 'TEXT body',
      ],
    ];
    $this->extra_params = [
      'unsubscribe_url' => 'http://www.mailpoet.com',
    ];
  }

  function testItCanGenerateBody() {
    $body = $this->mailer->getBody($this->newsletter, $this->subscriber, $this->extra_params);
    expect($body['to'])->contains($this->subscriber);
    expect($body['from'])->equals($this->sender['from_email']);
    expect($body['fromname'])->equals($this->sender['from_name']);
    expect($body['replyto'])->equals($this->reply_to['reply_to_email']);
    expect($body['subject'])->equals($this->newsletter['subject']);
    $headers = json_decode($body['headers'], true);
    expect($headers['List-Unsubscribe'])
      ->equals('<' . $this->extra_params['unsubscribe_url'] . '>');
    expect($body['html'])->equals($this->newsletter['body']['html']);
    expect($body['text'])->equals($this->newsletter['body']['text']);
  }

  function testItCanCreateRequest() {
    $body = $this->mailer->getBody($this->newsletter, $this->subscriber);
    $request = $this->mailer->request($this->newsletter, $this->subscriber);
    expect($request['timeout'])->equals(10);
    expect($request['httpversion'])->equals('1.1');
    expect($request['method'])->equals('POST');
    expect($request['headers']['Authorization'])
      ->equals('Bearer ' . $this->settings['api_key']);
    expect($request['body'])->equals(http_build_query($body));
  }

  function testItCanDoBasicAuth() {
    expect($this->mailer->auth())
      ->equals('Bearer ' . $this->settings['api_key']);
  }

  function testItCannotSendWithoutProperApiKey() {
    if (getenv('WP_TEST_MAILER_ENABLE_SENDING') !== 'true') $this->markTestSkipped();
    $this->mailer->api_key = 'someapi';
    $result = $this->mailer->send(
      $this->newsletter,
      $this->subscriber
    );
    expect($result['response'])->false();
  }

  function testItChecksBlacklistBeforeSending() {
    $blacklisted_subscriber = 'blacklist_test@example.com';
    $blacklist = Stub::make(new BlacklistCheck(), ['isBlacklisted' => true], $this);
    $mailer = Stub::make(
      $this->mailer,
      ['blacklist' => $blacklist, 'error_mapper' => new SendGridMapper()],
      $this
    );
    $result = $mailer->send(
      $this->newsletter,
      $blacklisted_subscriber
    );
    expect($result['response'])->false();
    expect($result['error'])->isInstanceOf(MailerError::class);
    expect($result['error']->getMessage())->contains('SendGrid has returned an unknown error.');
  }

  function testItCanSend() {
    if (getenv('WP_TEST_MAILER_ENABLE_SENDING') !== 'true') $this->markTestSkipped();
    $result = $this->mailer->send(
      $this->newsletter,
      $this->subscriber
    );
    expect($result['response'])->true();
  }
}
