<?php

use MailPoet\Mailer\Mandrill;

class MandrillCest {
  function __construct() {
    $this->data = array(
      'api_key' => '692ys1B7REEoZN7R-dYwNA',
      'from_email' => 'vlad@mailpoet.com',
      'from_name' => 'Vlad',
      'newsletter' => array(
        'subject' => 'hi there!',
        'body' => 'this is a test message....'
      ),
      'subscribers' => array(
        array(
          'email' => 'johndoe@mailpoet.com',
          'last_name' => 'Smith'
        ),
        array(
          'email' => 'janesmith@mailpoet.com',
          'first_name' => 'Jane',
          'last_name' => 'Smith'
        ),
        array(
          'email' => 'someone@mailpoet.com',
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
    expect($subscribers[0]['name'])->equals(
      $this->data['subscribers'][0]['last_name']
    );
    expect($subscribers[1]['name'])->equals(
      sprintf('%s %s',
              $this->data['subscribers'][1]['first_name'],
              $this->data['subscribers'][1]['last_name']
      )
    );
  }

  function itCanGenerateBody() {
    $body = $this->mailer->getBody();
    expect($body['key'])
      ->equals($this->data['api_key']);
    expect($body['message'])
      ->equals(
        array(
          "html" => $this->data['newsletter']['body'],
          "subject" => $this->data['newsletter']['subject'],
          "from_email" => $this->data['from_email'],
          "from_name" => $this->data['from_name'],
          "to" => $this->mailer->getSubscribers(),
          "headers" => array(
            "Reply-To" => $this->data['from_email']
          ),
          "preserve_recipients" => false
        ));
    expect($body['async'])
      ->false();
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

  function itCannotSendWithoutSubscribers() {
    $mailer = new Mandrill(
      $this->data['api_key'],
      $this->data['from_email'],
      $this->data['from_name'],
      $this->data['newsletter'],
      array()
    );
    expect($mailer->send())->equals(false);
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
