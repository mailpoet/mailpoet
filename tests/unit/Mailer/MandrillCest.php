<?php

use MailPoet\Mailer\Mandrill;

class MandrillCest {
  function __construct() {
    $this->data = array(
      'api_key' => '692ys1B7REEoZN7R-dYwNA',
      'from_email' => 'do-not-reply@mailpoet.com',
      'from_name' => 'Phoenix',
      'newsletter' => array(
        'subject' => 'Testing Mandrill',
        'body' => 'this is a test message....'
      ),
      'subscribers' => array(
        array(
          'email' => 'mailpoet-test1@mailinator.com',
          'last_name' => 'Smith'
        ),
        array(
          'email' => 'mailpoet-test2@mailinator.com',
          'first_name' => 'Jane',
          'last_name' => 'Smith'
        ),
        array(
          'email' => 'mailpoet-test3@mailinator.com',
        ),
        array()
      )
    );
    
    $this->mailer = new Mandrill(
      $this->data['api_key'],
      $this->data['from_email'],
      $this->data['from_name'],
      $this->data['newsletter'],
      $this->data['subscribers']);
  }
  
  function itCanGenerateSubscribers() {
    $subscribers = $this->mailer->getSubscribers();
    expect(count($subscribers))->equals(3);
    // test proper handling of spaces between first/last name
    expect($subscribers[0]['email'])->equals(
      $this->data['subscribers'][0]['email']
    );
    expect($subscribers[0]['name'])->equals(
      $this->data['subscribers'][0]['last_name']
    );
    expect($subscribers[1]['name'])->equals(
      sprintf(
        '%s %s', $this->data['subscribers'][1]['first_name'],
        $this->data['subscribers'][1]['last_name']
      )
    );
  }

  function itCanGenerateBody() {
    $body = $this->mailer->getBody();
    expect($body['key'])->equals($this->data['api_key']);
    expect($body['message']['html'])->equals(
      $this->data['newsletter']['body']
    );
    expect($body['message']['subject'])->equals(
      $this->data['newsletter']['subject']
    );
    expect($body['message']['from_email'])->equals(
      $this->data['from_email']
    );
    expect($body['message']['from_name'])->equals(
      $this->data['from_name']
    );
    expect($body['message']['to'])->equals(
      $this->mailer->getSubscribers()
    );
    expect($body['message']['headers']['Reply-To'])->equals(
      $this->data['from_email']
    );
    expect($body['message']['preserve_recipients'])->false();
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
    $mailer = new Mandrill(
      'someapikey',
      $this->data['from_email'],
      $this->data['from_name'],
      $this->data['newsletter'],
      $this->data['subscribers']
    );
    expect($mailer->send())->equals(false);
  }

  function itCanSend() {
    $result = $this->mailer->send();
    expect($result)->equals(true);
  }
}
