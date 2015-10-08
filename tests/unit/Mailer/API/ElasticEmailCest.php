<?php

use MailPoet\Mailer\API\ElasticEmail;

class ElasticEmailCest {
  function _before() {
    $this->settings = array(
      'name' => 'ElasticEmail',
      'type' => 'API',
      'api_key' => '997f1f7f-41de-4d7f-a8cb-86c8481370fa'
    );
    $this->fromEmail = 'staff@mailpoet.com';
    $this->fromName = 'Sender';
    $this->mailer = new ElasticEmail(
      $this->settings['api_key'],
      $this->fromEmail,
      $this->fromName
    );
    $this->mailer->subscriber =
      'Recipient <mailpoet-phoenix-test@mailinator.com>';
    $this->mailer->newsletter = array(
      'subject' => 'testing ElasticEmail',
      'body' => array(
        'html' => 'HTML body',
        'text' => 'TEXT body'
      )
    );
  }

  function itCanGenerateBody() {
    $body = $this->mailer->getBody();
    expect($body['api_key'])
      ->equals($this->settings['api_key']);
    expect($body['from'])
      ->equals($this->fromEmail);
    expect($body['from_name'])
      ->equals($this->fromName);
    expect($body['to'])
      ->contains($this->mailer->subscriber);
    expect($body['subject'])
      ->equals($this->mailer->newsletter['subject']);
    expect($body['body_html'])
      ->equals($this->mailer->newsletter['body']['html']);
    expect($body['body_text'])
      ->equals($this->mailer->newsletter['body']['text']);
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

  function itCanSend() {
    $result = $this->mailer->send(
      $this->mailer->newsletter,
      $this->mailer->subscriber
    );
    expect($result)->true();
  }
}
