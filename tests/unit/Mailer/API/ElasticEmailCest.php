<?php

use MailPoet\Mailer\API\ElasticEmail;

class ElasticEmailCest {
  function __construct() {
    $this->settings = array(
      'name' => 'ElasticEmail',
      'type' => 'API',
      'api_key' => '997f1f7f-41de-4d7f-a8cb-86c8481370fa'
    );
    $this->fromEmail = 'do-not-reply@mailpoet.com';
    $this->fromName = 'Sender';
    $this->mailer = new ElasticEmail($this->settings['api_key'], $this->fromEmail, $this->fromName);
    $this->mailer->subscriber = 'Recipient <mailpoet-test1@mailinator.com>';
    $this->mailer->newsletter = array(
      'subject' => 'testing ElasticEmail',
      'body' => array(
        'html' => 'HTML body',
        'text' => 'TEXT body'
      )
    );
  }

  function itCanGenerateBody() {
    $body = explode('&', $this->mailer->getBody());
    expect($body[0])
      ->equals('api_key=' . $this->settings['api_key']);
    expect($body[1])
      ->equals('from=' . $this->fromEmail);
    expect($body[2])
      ->equals('from_name=' . $this->fromName);
    expect($body[3])
      ->contains('to=' . $this->mailer->subscriber);
    expect($body[4])
      ->equals('subject=' . $this->mailer->newsletter['subject']);
    expect($body[5])
      ->equals('body_html=' . $this->mailer->newsletter['body']['html']);
    expect($body[6])
      ->equals('body_text=' . $this->mailer->newsletter['body']['text']);
  }

  function itCanCreateRequest() {
    $request = $this->mailer->request();
    expect($request['timeout'])
      ->equals(10);
    expect($request['httpversion'])
      ->equals('1.0');
    expect($request['method'])
      ->equals('POST');
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

  function itCanSend() {
    $result = $this->mailer->send(
      $this->mailer->newsletter,
      $this->mailer->subscriber
    );
    expect($result)->true();
  }
}
