<?php

use MailPoet\Mailer\Methods\Mandrill;

class MandrillCest {
  function _before() {
    $this->settings = array(
      'method' => 'Mandrill',
      'type' => 'API',
      'api_key' => '692ys1B7REEoZN7R-dYwNA'
    );
    $this->fromEmail = 'staff@mailpoet.com';
    $this->fromName = 'Sender';
    $this->mailer = new Mandrill(
      $this->settings['api_key'],
      $this->fromEmail,
      $this->fromName
    );
    $this->subscriber = 'Recipient <mailpoet-phoenix-test@mailinator.com>';
    $this->newsletter = array(
      'subject' => 'testing Mandrill',
      'body' => array(
        'html' => 'HTML body',
        'text' => 'TEXT body'
      )
    );
  }

  function itCanGenerateBody() {
    $subscriber = $this->mailer->processSubscriber($this->subscriber);
    $body = $this->mailer->getBody($this->newsletter, $subscriber);
    expect($body['key'])->equals($this->settings['api_key']);
    expect($body['message']['from_email'])->equals($this->fromEmail);
    expect($body['message']['from_name'])->equals($this->fromName);
    expect($body['message']['to'])->equals(array($subscriber));
    expect($body['message']['subject'])->equals($this->newsletter['subject']);
    expect($body['message']['html'])->equals($this->newsletter['body']['html']);
    expect($body['message']['text'])->equals($this->newsletter['body']['text']);
    expect($body['async'])->false();
  }

  function itCanCreateRequest() {
    $subscriber = $this->mailer->processSubscriber($this->subscriber);
    $body = $this->mailer->getBody($this->newsletter, $subscriber);
    $request = $this->mailer->request($this->newsletter, $subscriber);
    expect($request['timeout'])->equals(10);
    expect($request['httpversion'])->equals('1.0');
    expect($request['method'])->equals('POST');
    expect($request['headers']['Content-Type'])->equals('application/json');
    expect($request['body'])->equals(json_encode($body));
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