<?php

use MailPoet\Mailer\MailGun;

class MailGunCest {
  function __construct() {
    $this->data = array(
      'domain' => 'mrcasual.com',
      'api_key' => 'key-6cf5g5qjzenk-7nodj44gdt8phe6vam2',
      'from_email' => 'do-not-reply@mailpoet.com',
      'from_name' => 'Phoenix',
      'newsletter' => array(
        'subject' => 'Testing MailGun',
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

    $this->mailer = new MailGun(
      $this->data['domain'],
      $this->data['api_key'],
      $this->data['from_email'],
      $this->data['from_name'],
      $this->data['newsletter'],
      $this->data['subscribers']);
  }

  function itCanGenerateSubscribers() {
    $subscribers = $this->mailer->getSubscribers();
    expect(count($subscribers))->equals(2);
    expect(count($subscribers['emails']))->equals(3);
    expect(count($subscribers['data']))->equals(3);
    // test proper handling of spaces between first/last name
    expect($subscribers['emails'][0])->equals(
      sprintf(
        '%s <%s>',
        $this->data['subscribers'][0]['last_name'],
        $this->data['subscribers'][0]['email'])
    );
    expect($subscribers['emails'][1])->equals(
      sprintf(
        '%s %s <%s>', $this->data['subscribers'][1]['first_name'],
        $this->data['subscribers'][1]['last_name'],
        $this->data['subscribers'][1]['email']
      )
    );
    expect($subscribers['data'][0])->equals(
      array(
        sprintf(
          '%s <%s>',
          $this->data['subscribers'][0]['last_name'],
          $this->data['subscribers'][0]['email']
        ) => array()
      )
    );
  }

  function itCanGenerateBody() {
    $body = $this->mailer->getBody();
    expect(substr_count($body, 'to='))->equals(3);
    $body = explode('&', $body);
    expect($body[0])
      ->equals('from=' .
               sprintf(
                 '%s <%s>', $this->data['from_name'], $this->data['from_email']
               )
      );
    expect($body[1])
      ->equals('to=' . $this->mailer->getSubscribers()['emails'][0]
      );
    expect($body[2])
      ->equals('to=' . $this->mailer->getSubscribers()['emails'][1]
      );
    expect($body[3])
      ->equals('to=' . $this->mailer->getSubscribers()['emails'][2]
      );
    expect($body[4])
      ->equals('recipient-variables=' .
               json_encode($this->mailer->getSubscribers()['data'])
      );
    expect($body[5])->equals('subject=' . $this->data['newsletter']['subject']);
    expect($body[6])->equals('text=' . $this->data['newsletter']['body']);
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
    expect($request['headers']['Authorization'])
      ->equals('Basic ' . base64_encode('api:' . $this->data['api_key']));
    expect($request['body'])
      ->equals($this->mailer->getBody());
  }

  function itCannotSendWithoutProperAPIKey() {
    $mailer = new MailGun(
      $this->data['domain'],
      'someapikey',
      $this->data['from_email'],
      $this->data['from_name'],
      $this->data['newsletter'],
      $this->data['subscribers']
    );
    expect($mailer->send())->equals(false);
  }

  function itCannotSendWithoutProperDomain() {
    $mailer = new MailGun(
      'mailpoet.com',
      $this->data['api_key'],
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
