<?php

use MailPoet\Mailer\Methods\ElasticEmail;

class ElasticEmailCest {
  function _before() {
    $this->settings = array(
      'method' => 'ElasticEmail',
      'api_key' => '997f1f7f-41de-4d7f-a8cb-86c8481370fa'
    );
    $this->fromEmail = 'staff@mailpoet.com';
    $this->fromName = 'Sender';
    $this->mailer = new ElasticEmail(
      $this->settings['api_key'],
      $this->fromEmail,
      $this->fromName
    );
    $this->subscriber = 'Recipient <mailpoet-phoenix-test@mailinator.com>';
    $this->newsletter = array(
      'subject' => 'testing ElasticEmail',
      'body' => array(
        'html' => 'HTML body',
        'text' => 'TEXT body'
      )
    );
  }

  function itCanGenerateBody() {
    $body = $this->mailer->getBody($this->newsletter, $this->subscriber);
    expect($body['api_key'])->equals($this->settings['api_key']);
    expect($body['from'])->equals($this->fromEmail);
    expect($body['from_name'])->equals($this->fromName);
    expect($body['to'])->contains($this->subscriber);
    expect($body['subject'])->equals($this->newsletter['subject']);
    expect($body['body_html'])->equals($this->newsletter['body']['html']);
    expect($body['body_text'])->equals($this->newsletter['body']['text']);
  }

  function itCanCreateRequest() {
    $request = $this->mailer->request($this->newsletter, $this->subscriber);
    $body = $this->mailer->getBody($this->newsletter, $this->subscriber);
    expect($request['timeout'])->equals(10);
    expect($request['httpversion'])->equals('1.0');
    expect($request['method'])->equals('POST');
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

  function itCanSend() {
    $result = $this->mailer->send(
      $this->newsletter,
      $this->subscriber
    );
    expect($result)->true();
  }
}
