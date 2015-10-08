<?php

use MailPoet\Mailer\API\MailGun;

class MailGunCest {
  function _before() {
    $this->settings = array(
      'name' => 'MailGun',
      'type' => 'API',
      'api_key' => 'key-6cf5g5qjzenk-7nodj44gdt8phe6vam2',
      'domain' => 'mrcasual.com'
    );
    $this->from = 'Sender <staff@mailpoet.com>';
    $this->mailer = new MailGun(
      $this->settings['domain'],
      $this->settings['api_key'],
      $this->from
    );
    $this->mailer->subscriber =
      'Recipient <mailpoet-phoenix-test@mailinator.com>';
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
    expect($body['from'])
      ->equals($this->from);
    expect($body['to'])
      ->equals($this->mailer->subscriber);
    expect($body['subject'])
      ->equals($this->mailer->newsletter['subject']);
    expect($body['html'])
      ->equals($this->mailer->newsletter['body']['html']);
    expect($body['text'])
      ->equals($this->mailer->newsletter['body']['text']);
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
      ->equals(urldecode(http_build_query($this->mailer->getBody())));
  }

  function itCannotSendWithoutProperAPIKey() {
    $this->mailer->apiKey = 'someapi';
    $result = $this->mailer->send(
      $this->mailer->newsletter,
      $this->mailer->subscriber
    );
    expect($result)->false();
  }

  function itCannotSendWithoutProperDomain() {
    $this->mailer->url =
      str_replace($this->settings['domain'], 'somedomain', $this->mailer->url);
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