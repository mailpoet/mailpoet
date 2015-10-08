<?php

use MailPoet\Mailer\API\MailPoet;

class MailPoetCest {
  function _before() {
    $this->settings = array(
      'name' => 'MailPoet',
      'type' => 'API',
      'api_key' => 'dhNSqj1XHkVltIliyQDvMiKzQShOA5rs0m_DdRUVZHU'
    );
    $this->fromEmail = 'staff@mailpoet.com';
    $this->fromName = 'Sender';
    $this->mailer = new MailPoet(
      $this->settings['api_key'],
      $this->fromEmail,
      $this->fromName
    );
    $this->mailer->subscriber = 'Recipient <mailpoet-phoenix-test@mailinator.com>';
    $this->mailer->newsletter = array(
      'subject' => 'testing MailPoet',
      'body' => array(
        'html' => 'HTML body',
        'text' => 'TEXT body'
      )
    );
  }

  function itCanGenerateBody() {
    $this->mailer->subscriber = $this->mailer->processSubscriber($this->mailer->subscriber);
    $body = $this->mailer->getBody();
    expect($body['to']['address'])
      ->equals($this->mailer->subscriber['email']);
    expect($body['to']['name'])
      ->equals($this->mailer->subscriber['name']);
    expect($body['from']['address'])
      ->equals($this->fromEmail);
    expect($body['subject'])
      ->equals($this->mailer->newsletter['subject']);
    expect($body['html'])
      ->equals($this->mailer->newsletter['body']['html']);
    expect($body['text'])
      ->equals($this->mailer->newsletter['body']['text']);
  }

  function itCanCreateRequest() {
    $this->mailer->subscriber = $this->mailer->processSubscriber($this->mailer->subscriber);
    $request = $this->mailer->request();
    expect($request['timeout'])
      ->equals(10);
    expect($request['httpversion'])
      ->equals('1.0');
    expect($request['method'])
      ->equals('POST');
    expect($request['headers']['Content-Type'])
      ->equals('application/json');
    expect($request['headers']['Authorization'])
      ->equals($this->mailer->auth());
    expect($request['body'])
      ->equals(json_encode(array($this->mailer->getBody())));
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
      $this->mailer->newsletter,
      $this->mailer->subscriber
    );
    expect($result)->false();
  }

  function itCanSend() {
    $result = $this->mailer->send(
      $this->mailer->newsletter,
      $this->mailer->subscriber
    );
    expect($result)->true();
  }
}
