<?php

use MailPoet\Mailer\API\MailGun;

class MailGunCest {
  function __construct() {
    $this->settings = array(
      'name' => 'MailGun',
      'type' => 'API',
      'api_key' => 'key-6cf5g5qjzenk-7nodj44gdt8phe6vam2',
      'domain' => 'mrcasual.com'
    );
    $this->from = 'Sender <do-not-reply@mailpoet.com>';
    $this->mailer = new MailGun($this->settings['domain'], $this->settings['api_key'], $this->from);
    $this->mailer->subscriber = 'Recipient <mailpoet-phoenix-test@mailinator.com>';
    $this->mailer->newsletter = array(
      'subject' => 'testing MailGun',
      'body' => array(
        'html' => 'HTML body',
        'text' => 'TEXT body'
      )
    );
  }

  function itCanGenerateBody() {
    $body = $this->mailer->getBody();
    $body = explode('&', $body);
    expect($body[0])
      ->equals('from=' . $this->from);
    expect($body[1])
      ->equals('to=' . $this->mailer->subscriber);
    expect($body[2])
      ->equals('subject=' . $this->mailer->newsletter['subject']);
    expect($body[3])
      ->equals('html=' . $this->mailer->newsletter['body']['html']);
    expect($body[4])
      ->equals('text=' . $this->mailer->newsletter['body']['text']);
  }

  function itCanDoBasicAuth() {
    expect($this->mailer->auth())
      ->equals('Basic ' . base64_encode('api:' . $this->settings['api_key']));
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
      ->equals('application/x-www-form-urlencoded');
    expect($request['headers']['Authorization'])
      ->equals('Basic ' . base64_encode('api:' . $this->settings['api_key']));
    expect($request['body'])
      ->equals($this->mailer->getBody());
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

  function itCannotSendWithoutProperDomain() {
    $mailer = clone $this->mailer;
    $mailer->domain = 'somedomain';
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