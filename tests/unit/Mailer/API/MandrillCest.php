<?php

use MailPoet\Mailer\API\Mandrill;

class MandrillCest {
  function __construct() {
    $this->settings = array(
      'name' => 'Mandrill',
      'type' => 'API',
      'api_key' => '692ys1B7REEoZN7R-dYwNA'
    );
    $this->fromEmail = 'do-not-reply@mailpoet.com';
    $this->fromName = 'Sender';
    $this->mailer = new Mandrill($this->settings['api_key'], $this->fromEmail, $this->fromName);
    $this->mailer->subscriber = 'Recipient <mailpoet-test1@mailinator.com>';
    $this->mailer->newsletter = array(
      'subject' => 'testing Mandrill',
      'body' => array(
        'html' => 'HTML body',
        'text' => 'TEXT body'
      )
    );
  }

  function itCanGenerateBody() {
    $body = $this->mailer->getBody();
    expect($body['key'])->equals($this->settings['api_key']);
    expect($body['message']['from_email'])->equals(
      $this->fromEmail
    );
    expect($body['message']['from_name'])->equals(
      $this->fromName
    );
    expect($body['message']['to'])->equals(
      $this->mailer->subscriber
    );
    expect($body['message']['subject'])->equals(
      $this->mailer->newsletter['subject']
    );
    expect($body['message']['html'])->equals(
      $this->mailer->newsletter['body']['html']
    );
    expect($body['message']['text'])->equals(
      $this->mailer->newsletter['body']['text']
    );
    expect($body['message']['headers']['Reply-To'])->equals(
      $this->fromEmail
    );
    expect($body['async'])->false();
  }

  function itCanCreateRequest() {
    $request = $this->mailer->request();
    expect($request['timeout'])
      ->equals(10);
    expect($request['httpversion'])
      ->equals('1.0');
    expect($request['method'])
      ->equals('POST');
    expect($request['headers']['Content-Type'])
      ->equals('application/json');
    expect($request['body'])
      ->equals(json_encode($this->mailer->getBody()));
  }

  function itCannotSendWithoutProperAPIKey() {
    $mailer = clone $this->mailer;
    $mailer->api_key = 'someapi';
    $result = $mailer->send(
      $mailer->newsletter,
      $mailer->subscriber
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