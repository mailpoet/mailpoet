<?php

use MailPoet\Mailer\Methods\ElasticEmail;

class ElasticEmailCest {
  function _before() {
    $this->settings = array(
      'method' => 'ElasticEmail',
      'api_key' => getenv('WP_TEST_MAILER_ELASTICEMAIL_API') ?
        getenv('WP_TEST_MAILER_ELASTICEMAIL_API') :
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
    $this->mailer = new ElasticEmail(
      $this->settings['api_key'],
      $this->sender,
      $this->reply_to
    );
    $this->subscriber = 'Recipient <mailpoet-phoenix-test@mailinator.com>';
    $this->newsletter = array(
      'subject' => 'testing ElasticEmail',
      'body' => array(
        'html' => 'HTML body',
        'text' => 'TEXT body'
      )
    );
  }

  function itCanGenerateBody() {
    $body = $this->mailer->getBody($this->newsletter, $this->subscriber);
    expect($body['api_key'])->equals($this->settings['api_key']);
    expect($body['from'])->equals($this->sender['from_email']);
    expect($body['from_name'])->equals($this->sender['from_name']);
    expect($body['reply_to'])->equals($this->reply_to['reply_to_email']);
    expect($body['reply_to_name'])->equals($this->reply_to['reply_to_name']);
    expect($body['to'])->contains($this->subscriber);
    expect($body['subject'])->equals($this->newsletter['subject']);
    expect($body['body_html'])->equals($this->newsletter['body']['html']);
    expect($body['body_text'])->equals($this->newsletter['body']['text']);
  }

  function itCanCreateRequest() {
    $request = $this->mailer->request($this->newsletter, $this->subscriber);
    $body = $this->mailer->getBody($this->newsletter, $this->subscriber);
    expect($request['timeout'])->equals(10);
    expect($request['httpversion'])->equals('1.0');
    expect($request['method'])->equals('POST');
    expect($request['body'])->equals(urldecode(http_build_query($body)));
  }

  function itCannotSendWithoutProperApiKey() {
    if(getenv('WP_TEST_MAILER_ENABLE_SENDING') !== 'true') return;
    $this->mailer->api_key = 'someapi';
    $result = $this->mailer->send(
      $this->newsletter,
      $this->subscriber
    );
    expect($result)->false();
  }

  function itCanSend() {
    if(getenv('WP_TEST_MAILER_ENABLE_SENDING') !== 'true') return;
    $result = $this->mailer->send(
      $this->newsletter,
      $this->subscriber
    );
    expect($result)->true();
  }
}