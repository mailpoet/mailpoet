<?php

use MailPoet\Mailer\API\SendGrid;

class SendGridCest {
  function _before() {
    $this->settings = array(
      'name' => 'SendGrid',
      'type' => 'API',
      'api_key' => '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'
    );
    $this->fromEmail = 'staff@mailpoet.com';
    $this->fromName = 'Sender';
    $this->mailer = new SendGrid(
      $this->settings['api_key'],
      $this->fromEmail,
      $this->fromName
    );
    $this->mailer->subscriber =
      'Recipient <mailpoet-phoenix-test@mailinator.com>';
    $this->mailer->newsletter = array(
      'subject' => 'testing SendGrid',
      'body' => array(
        'html' => 'HTML body',
        'text' => 'TEXT body'
      )
    );
  }
  
  function itCanGenerateBody() {
    $body = $this->mailer->getBody();
    expect($body['to'])
      ->contains($this->mailer->subscriber);
    expect($body['from'])
      ->equals($this->fromEmail);
    expect($body['fromname'])
      ->equals($this->fromName);
    expect($body['subject'])
      ->equals($this->mailer->newsletter['subject']);
    expect($body['html'])
      ->equals($this->mailer->newsletter['body']['html']);
    expect($body['text'])
      ->equals($this->mailer->newsletter['body']['text']);
  }
  
  function itCanCreateRequest() {
    $request = $this->mailer->request();
    expect($request['timeout'])
      ->equals(10);
    expect($request['httpversion'])
      ->equals('1.1');
    expect($request['method'])
      ->equals('POST');
    expect($request['headers']['Authorization'])
      ->equals('Bearer ' . $this->settings['api_key']);
    expect($request['body'])
      ->equals(urldecode(http_build_query($this->mailer->getBody())));
  }
  
  function itCanDoBasicAuth() {
    expect($this->mailer->auth())
      ->equals('Bearer ' . $this->settings['api_key']);
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
