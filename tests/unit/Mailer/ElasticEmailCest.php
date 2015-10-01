<?php

use MailPoet\Mailer\ElasticEmail;

class ElasticEmailCest {
  function __construct() {
    $this->data = array(
      'api_key' => '997f1f7f-41de-4d7f-a8cb-86c8481370fa',
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

    $this->mailer = new ElasticEmail(
      $this->data['api_key'],
      $this->data['from_email'],
      $this->data['from_name'],
      $this->data['newsletter'],
      $this->data['subscribers']);
  }

  function itCanGenerateSubscribers() {
    $subscribers = explode(';', $this->mailer->getSubscribers());
    expect(count($subscribers))->equals(3);

    // test proper handling of spaces between first/last name
    expect($subscribers[0])->equals(
      $this->data['subscribers'][0]['last_name'] . ' <' . $this->data['subscribers'][0]['email'] . '>'
    );
    expect($subscribers[1])->equals(
      $this->data['subscribers'][1]['first_name'] . ' ' .
      $this->data['subscribers'][1]['last_name'] .
      ' <' . $this->data['subscribers'][1]['email'] . '>'
    );
  }

  function itCanGenerateBody() {
    $urlEncodedBody = $this->mailer->getBody();
    expect($urlEncodedBody)
      ->contains(urlencode($this->data['newsletter']['subject']));

    $body = explode('&', urldecode($urlEncodedBody));
    expect($body[0])
      ->equals("api_key=" . $this->data['api_key']);
    expect($body[1])
      ->equals("from=" . $this->data['from_email']);
    expect($body[2])
      ->equals("from_name=" . $this->data['from_name']);
    expect($body[3])
      ->contains($this->data['subscribers'][0]['email']);
    expect($body[4])
      ->equals("subject=" . $this->data['newsletter']['subject']);
    expect($body[5])
      ->equals("body_html=" . $this->data['newsletter']['body']);
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
    expect($request['body'])
      ->equals($this->mailer->getBody());
  }

  function itCannotSendWithoutSubscribers() {
    $mailer = new ElasticEmail(
      $this->data['api_key'],
      $this->data['from_email'],
      $this->data['from_name'],
      $this->data['newsletter'],
      array()
    );
    expect($mailer->send())->equals(false);
  }

  function itCannotSendWithoutProperAPIKey() {
    $mailer = new ElasticEmail(
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
