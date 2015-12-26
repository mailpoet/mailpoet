<?php

use MailPoet\Mailer\Methods\MailPoet;

class MailPoetCest {
  function _before() {
    $this->settings = array(
      'method' => 'MailPoet',
      'api_key' => 'dhNSqj1XHkVltIliyQDvMiKzQShOA5rs0m_DdRUVZHU'
    );
    $this->fromEmail = 'staff@mailpoet.com';
    $this->fromName = 'Sender';
    $this->mailer = new MailPoet(
      $this->settings['api_key'],
      $this->fromEmail,
      $this->fromName
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
    expect($body['from']['address'])->equals($this->fromEmail);
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

  function itCannotSendWithoutProperAPIKey() {
    $this->mailer->apiKey = 'someapi';
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
