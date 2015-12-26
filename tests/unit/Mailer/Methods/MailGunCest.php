<?php

use MailPoet\Mailer\Methods\MailGun;

class MailGunCest {
  function _before() {
    $this->settings = array(
      'method' => 'MailGun',
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
    $this->subscriber = 'Recipient <mailpoet-phoenix-test@mailinator.com>';
    $this->newsletter = array(
      'subject' => 'testing MailGun',
      'body' => array(
        'html' => 'HTML body',
        'text' => 'TEXT body'
      )
    );
  }

  function itCanGenerateBody() {
    $body = $this->mailer->getBody($this->newsletter, $this->subscriber);
    expect($body['from'])->equals($this->from);
    expect($body['to'])->equals($this->subscriber);
    expect($body['subject'])->equals($this->newsletter['subject']);
    expect($body['html'])->equals($this->newsletter['body']['html']);
    expect($body['text'])->equals($this->newsletter['body']['text']);
  }

  function itCanDoBasicAuth() {
    expect($this->mailer->auth())
      ->equals('Basic ' . base64_encode('api:' . $this->settings['api_key']));
  }

  function itCanCreateRequest() {
    $request = $this->mailer->request($this->newsletter, $this->subscriber);
    $body = $this->mailer->getBody($this->newsletter, $this->subscriber);
    expect($request['timeout'])->equals(10);
    expect($request['httpversion'])->equals('1.0');
    expect($request['method'])->equals('POST');
    expect($request['headers']['Content-Type'])
      ->equals('application/x-www-form-urlencoded');
    expect($request['headers']['Authorization'])
      ->equals('Basic ' . base64_encode('api:' . $this->settings['api_key']));
    expect($request['body'])->equals(urldecode(http_build_query($body)));
  }

  function itCannotSendWithoutProperAPIKey() {
    $this->mailer->apiKey = 'someapi';
    $result = $this->mailer->send(
      $this->newsletter,
      $this->subscriber
    );
    expect($result)->false();
  }

  function itCannotSendWithoutProperDomain() {
    $this->mailer->url =
      str_replace($this->settings['domain'], 'somedomain', $this->mailer->url);
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