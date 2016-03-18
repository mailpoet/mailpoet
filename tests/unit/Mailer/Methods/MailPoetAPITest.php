<?php

use MailPoet\Mailer\Methods\MailPoet;

class MailPoetAPITest extends MailPoetTest {
  function _before() {
    $this->settings = array(
      'method' => 'MailPoet',
      'api_key' => getenv('WP_TEST_MAILER_MAILPOET_API') ?
        getenv('WP_TEST_MAILER_MAILPOET_API') :
        '1234567890'
    );
    $this->sender = array(
      'from_name' => 'Sender',
      'from_email' => 'staff@mailpoet.com',
      'from_name_email' => 'Sender <staff@mailpoet.com>'
    );
    $this->reply_to = array(
      'reply_to_name' => 'Reply To',
      'reply_to_email' => 'reply-to@mailpoet.com',
      'reply_to_name_email' => 'Reply To <reply-to@mailpoet.com>'
    );
    $this->mailer = new MailPoet(
      $this->settings['api_key'],
      $this->sender,
      $this->reply_to
    );
    $this->subscriber = 'Recipient <mailpoet-phoenix-test@mailinator.com>';
    $this->newsletter = array(
      'subject' => 'testing MailPoet',
      'body' => array(
        'html' => 'HTML body',
        'text' => 'TEXT body'
      )
    );
  }

  function testItCanGenerateBodyForSingleMessage() {
    $body = $this->mailer->getBody($this->newsletter, $this->subscriber);
    $subscriber = $this->mailer->processSubscriber($this->subscriber);
    expect($body[0]['to']['address'])->equals($subscriber['email']);
    expect($body[0]['to']['name'])->equals($subscriber['name']);
    expect($body[0]['from']['address'])->equals($this->sender['from_email']);
    expect($body[0]['from']['name'])->equals($this->sender['from_name']);
    expect($body[0]['reply_to']['address'])->equals($this->reply_to['reply_to_email']);
    expect($body[0]['reply_to']['name'])->equals($this->reply_to['reply_to_name']);
    expect($body[0]['subject'])->equals($this->newsletter['subject']);
    expect($body[0]['html'])->equals($this->newsletter['body']['html']);
    expect($body[0]['text'])->equals($this->newsletter['body']['text']);
  }

  function testItCanGenerateBodyForMultipleMessages() {
    $newsletters = array_fill(0, 10, $this->newsletter);
    $subscribers = array_fill(0, 10, $this->subscriber);
    $body = $this->mailer->getBody($newsletters, $subscribers);
    expect(count($body))->equals(10);
    $subscriber = $this->mailer->processSubscriber($this->subscriber);
    expect($body[0]['to']['address'])->equals($subscriber['email']);
    expect($body[0]['to']['name'])->equals($subscriber['name']);
    expect($body[0]['from']['address'])->equals($this->sender['from_email']);
    expect($body[0]['from']['name'])->equals($this->sender['from_name']);
    expect($body[0]['reply_to']['address'])->equals($this->reply_to['reply_to_email']);
    expect($body[0]['reply_to']['name'])->equals($this->reply_to['reply_to_name']);
    expect($body[0]['subject'])->equals($this->newsletter['subject']);
    expect($body[0]['html'])->equals($this->newsletter['body']['html']);
    expect($body[0]['text'])->equals($this->newsletter['body']['text']);
  }

  function testItCanCreateRequest() {
    $body = $this->mailer->getBody($this->newsletter, $this->subscriber);
    $request = $this->mailer->request($body);
    expect($request['timeout'])->equals(10);
    expect($request['httpversion'])->equals('1.0');
    expect($request['method'])->equals('POST');
    expect($request['headers']['Content-Type'])->equals('application/json');
    expect($request['headers']['Authorization'])->equals($this->mailer->auth());
    expect($request['body'])->equals(json_encode($body));
  }

  function testItCanProcessSubscriber() {
    expect($this->mailer->processSubscriber('test@test.com'))
      ->equals(
        array(
          'email' => 'test@test.com',
          'name' => ''
        ));
    expect($this->mailer->processSubscriber('First <test@test.com>'))
      ->equals(
        array(
          'email' => 'test@test.com',
          'name' => 'First'
        ));
    expect($this->mailer->processSubscriber('First Last <test@test.com>'))
      ->equals(
        array(
          'email' => 'test@test.com',
          'name' => 'First Last'
        ));
  }

  function testItCanDoBasicAuth() {
    expect($this->mailer->auth())
      ->equals('Basic ' . base64_encode('api:' . $this->settings['api_key']));
  }

  function testItCannotSendWithoutProperApiKey() {
    if(getenv('WP_TEST_MAILER_ENABLE_SENDING') !== 'true') return;
    $this->mailer->api_key = 'someapi';
    $result = $this->mailer->send(
      $this->newsletter,
      $this->subscriber
    );
    expect($result)->false();
  }

  function testItCanSend() {
    if(getenv('WP_TEST_MAILER_ENABLE_SENDING') !== 'true') return;
    $result = $this->mailer->send(
      $this->newsletter,
      $this->subscriber
    );
    expect($result)->true();
  }
}