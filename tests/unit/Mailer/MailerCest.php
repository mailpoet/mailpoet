<?php
use MailPoet\Mailer\Mailer;

class MailerCest {

  function _before() {
    $this->newsletter = array(
      'subject' => 'A test message',
      'body' => 'Test, one, two, three.'
    );
    $this->subscribers = array(
      array(
        'first_name' => 'Marco',
        'last_name' => 'Lisci',
        'email' => 'marco@mailpoet.com'
      ),
      array(
        'first_name' => 'Test',
        'last_name' => 'MailPoet',
        'email' => 'testmailpoet@gmail.com'
      ),
      array(
        'first_name' => 'Vlad',
        'last_name' => '',
        'email' => 'vlad@mailpoet.com'
      ),
      array(
        'first_name' => 'Jonathan',
        'last_name' => 'Labreuille',
        'email' => 'jonathan@mailpoet.com'
      )
    );
    $this->mailer = new Mailer(
      $this->newsletter,
      $this->subscribers
    );
  }

  function itCanDoBasicAuth() {
    expect($this->mailer->auth())->equals(
      'Basic YXBpOlhjXzF6cjdhT3hxZkQ1czV4WnFHdm52VUo3eWM2SEJ2R0J4azRQRDNWMlU='
    );
  }

  function itCanGenerateACorrectMessage() {
    $subscriber = $this->subscribers[0];
    $message = $this->mailer->generateMessage($subscriber);
    expect($message['to']['address'])
      ->equals($subscriber['email']);
    expect($message['to']['name'])
      ->equals($subscriber['first_name'].' '.$subscriber['last_name']);
    expect($message['reply_to']['address'])
      ->equals('');
    expect($message['reply_to']['name'])
      ->equals('');
    expect($message['subject'])
      ->equals($this->newsletter['subject']);
    expect($message['html'])
      ->equals($this->newsletter['body']);
    expect($message['text'])
      ->equals('');
  }

  function itCanGenerateCorrectMessages() {
    $messages = $this->mailer->messages();
    expect(count($messages))
      ->equals(count($this->subscribers));
    for ($i=0; $i<count($this->subscribers); $i++) {
      expect($messages[$i]['to']['address'])
        ->equals($this->subscribers[$i]['email']);
      expect($messages[$i]['to']['name'])
        ->equals(
          $this->subscribers[$i]['first_name']
          .' '
          .$this->subscribers[$i]['last_name']
        );
    }
  }

  function itCanCreateARequest() {
    $request = $this->mailer->request();
    expect($request['timeout'])
      ->equals(10);
    expect($request['httpversion'])
      ->equals('1.0');
    expect($request['method'])
      ->equals('POST');
    expect($request['headers']['Authorization'])
      ->equals(
        'Basic YXBpOlhjXzF6cjdhT3hxZkQ1czV4WnFHdm52VUo3eWM2SEJ2R0J4azRQRDNWMlU='
      );
    expect($request['headers']['Content-Type'])
      ->equals('application/json');
    expect($request['body'])
      ->equals(
        '[{"from":{"address":"info@mailpoet.com","name":""},"to":{"address":"marco@mailpoet.com","name":"Marco Lisci"},"reply_to":{"address":"info@mailpoet.com","name":""},"subject":"A test message","html":"Test, one, two, three.","text":""},{"from":{"address":"info@mailpoet.com","name":""},"to":{"address":"testmailpoet@gmail.com","name":"Test MailPoet"},"reply_to":{"address":"info@mailpoet.com","name":""},"subject":"A test message","html":"Test, one, two, three.","text":""},{"from":{"address":"info@mailpoet.com","name":""},"to":{"address":"vlad@mailpoet.com","name":"Vlad "},"reply_to":{"address":"info@mailpoet.com","name":""},"subject":"A test message","html":"Test, one, two, three.","text":""},{"from":{"address":"info@mailpoet.com","name":""},"to":{"address":"jonathan@mailpoet.com","name":"Jonathan Labreuille"},"reply_to":{"address":"info@mailpoet.com","name":""},"subject":"A test message","html":"Test, one, two, three.","text":""}]'
      );
  }

  function itCanSend() {
    /* $result = $this->mailer->send(); */
    /* expect($result)->equals(true); */
  }
}
