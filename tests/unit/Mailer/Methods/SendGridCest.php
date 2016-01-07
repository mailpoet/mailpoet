<?php

use MailPoet\Mailer\Methods\SendGrid;

class SendGridCest {
  function _before() {
    $this->settings = array(
      'method' => 'SendGrid',
      'api_key' => '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'
    );
    $this->from_email = 'staff@mailpoet.com';
    $this->from_name = 'Sender';
    $this->mailer = new SendGrid(
      $this->settings['api_key'],
      $this->from_email,
      $this->from_name
    );
    $this->subscriber = 'Recipient <mailpoet-phoenix-test@mailinator.com>';
    $this->newsletter = array(
      'subject' => 'testing SendGrid',
      'body' => array(
        'html' => 'HTML body',
        'text' => 'TEXT body'
      )
    );
  }
  
  function itCanGenerateBody() {
    $body = $this->mailer->getBody($this->newsletter, $this->subscriber);
    expect($body['to'])->contains($this->subscriber);
    expect($body['from'])->equals($this->from_email);
    expect($body['from_name'])->equals($this->from_name);
    expect($body['subject'])->equals($this->newsletter['subject']);
    expect($body['html'])->equals($this->newsletter['body']['html']);
    expect($body['text'])->equals($this->newsletter['body']['text']);
  }
  
  function itCanCreateRequest() {
    $body = $this->mailer->getBody($this->newsletter, $this->subscriber);
    $request = $this->mailer->request($this->newsletter, $this->subscriber);
    expect($request['timeout'])->equals(10);
    expect($request['httpversion'])->equals('1.1');
    expect($request['method'])->equals('POST');
    expect($request['headers']['Authorization'])
      ->equals('Bearer ' . $this->settings['api_key']);
    expect($request['body'])->equals(urldecode(http_build_query($body)));
  }
  
  function itCanDoBasicAuth() {
    expect($this->mailer->auth())
      ->equals('Bearer ' . $this->settings['api_key']);
  }
  
  function itCannotSendWithoutProperApiKey() {
    $this->mailer->api_key = 'someapi';
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
