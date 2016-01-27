<?php

use MailPoet\Mailer\Methods\MailPoet;

class MailPoetCest {
  function _before() {
    $this->settings = array(
      'method' => 'MailPoet',
      'api_key' => 'dhNSqj1XHkVltIliyQDvMiKzQShOA5rs0m_DdRUVZHU'
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

  function itCanGenerateBody() {
    $subscriber = $this->mailer->processSubscriber($this->subscriber);
    $body = $this->mailer->getBody($this->newsletter, $subscriber);
    expect($body['to']['address'])->equals($subscriber['email']);
    expect($body['to']['name'])->equals($subscriber['name']);
    expect($body['from']['address'])->equals($this->sender['from_email']);
    expect($body['from']['name'])->equals($this->sender['from_name']);
    expect($body['reply_to']['address'])->equals($this->reply_to['reply_to_email']);
    expect($body['reply_to']['name'])->equals($this->reply_to['reply_to_name']);
    expect($body['subject'])->equals($this->newsletter['subject']);
    expect($body['html'])->equals($this->newsletter['body']['html']);
    expect($body['text'])->equals($this->newsletter['body']['text']);
  }

  function itCanCreateRequest() {
    $subscriber = $this->mailer->processSubscriber(
      'Recipient <mailpoet-phoenix-test@mailinator.com>'
    );
    $body = array($this->mailer->getBody($this->newsletter, $subscriber));
    $request = $this->mailer->request($this->newsletter, $subscriber);
    expect($request['timeout'])->equals(10);
    expect($request['httpversion'])->equals('1.0');
    expect($request['method'])->equals('POST');
    expect($request['headers']['Content-Type'])->equals('application/json');
    expect($request['headers']['Authorization'])->equals($this->mailer->auth());
    expect($request['body'])->equals($body);
  }

  function itCanProcessSubscriber() {
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

  function itCanDoBasicAuth() {
    expect($this->mailer->auth())
      ->equals('Basic ' . base64_encode('api:' . $this->settings['api_key']));
  }

  function itCannotSendWithoutProperApiKey() {
    $this->mailer->api_key = 'someapi';
    $result = $this->mailer->send(
      $this->newsletter,
      $this->subscriber
    );
    expect($result)->false();
  }

  function itCanSend() {
    $result = $this->mailer->send(
      $this->newsletter,
      $this->subscriber
    );
    expect($result)->true();
  }
}