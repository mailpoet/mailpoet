<?php

use MailPoet\Mailer\API\SendGrid;

class SendGridCest {
  function __construct() {
    $this->settings = array(
      'name' => 'SendGrid',
      'type' => 'API',
      'api_key' => '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'
    );
    $this->from = 'Sender <do-not-reply@mailpoet.com>';
    $this->mailer = new SendGrid($this->settings['api_key'], $this->from);
    $this->mailer->subscriber = 'Recipient <mailpoet-test1@mailinator.com>';
    $this->mailer->newsletter = array(
      'subject' => 'testing SendGrid',
      'body' => array(
        'html' => 'HTML body',
        'text' => 'TEXT body'
      )
    );
  }
  
  function itCanGenerateBody() {
    $body = explode('&', $this->mailer->getBody());
    expect($body[0])
      ->contains('to=' . $this->mailer->subscriber);
    expect($body[1])
      ->equals('from=' . $this->from);
    expect($body[2])
      ->equals('subject=' . $this->mailer->newsletter['subject']);
    expect($body[3])
      ->equals('html=' . $this->mailer->newsletter['body']['html']);
    expect($body[4])
      ->equals('text=' . $this->mailer->newsletter['body']['text']);
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
      ->equals($this->mailer->getBody());
  }
  
  function itCanDoBasicAuth() {
    expect($this->mailer->auth())
      ->equals('Bearer ' . $this->settings['api_key']);
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
