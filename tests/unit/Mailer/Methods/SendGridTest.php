<?php

use MailPoet\Mailer\Methods\SendGrid;

class SendGridTest extends MailPoetTest {
  function _before() {
    $this->settings = array(
      'method' => 'SendGrid',
      'api_key' => getenv('WP_TEST_MAILER_SENDGRID_API') ?
        getenv('WP_TEST_MAILER_SENDGRID_API') :
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
    $this->mailer = new SendGrid(
      $this->settings['api_key'],
      $this->sender,
      $this->reply_to
    );
    $this->subscriber = 'Recipient <mailpoet-phoenix-test@mailinator.com>';
    $this->newsletter = array(
      'subject' => 'testing SendGrid',
      'body' => array(
        'html' => 'HTML body',
        'text' => 'TEXT body'
      )
    );
    $this->extra_params = array(
      'unsubscribe_url' => 'http://www.mailpoet.com'
    );
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
    if(getenv('WP_TEST_MAILER_ENABLE_SENDING') !== 'true') return;
    $this->mailer->api_key = 'someapi';
    $result = $this->mailer->send(
      $this->newsletter,
      $this->subscriber
    );
    expect($result['response'])->false();
  }

  function testItCanSend() {
    if(getenv('WP_TEST_MAILER_ENABLE_SENDING') !== 'true') return;
    $result = $this->mailer->send(
      $this->newsletter,
      $this->subscriber
    );
    expect($result['response'])->true();
  }
}
