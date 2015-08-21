<?php
use MailPoet\Mailer\Bridge;
use MailPoet\Models\Setting;

class BridgeCest {

  function _before() {
    $from_name = Setting::create();
    $from_name->name = 'from_name';
    $from_name->value = 'Marco';
    $from_name->save();

    $from_address = Setting::create();
    $from_address->name = 'from_address';
    $from_address->value = 'marco@mailpoet.com';
    $from_address->save();

    $api_key = Setting::create();
    $api_key->name = 'api_key';
    $api_key->value = 'xxxccc';
    $api_key->save();

    $this->newsletter = array(
      'subject' => 'A test message from mp3',
      'body' => 'Hey, I am mp3, chapter two.'
    );
    $this->subscribers = array(
      array(
        'first_name' => 'Marco',
        'last_name' => 'Lisci',
        'email' => 'marco@mailpoet.com'
      ),
      array(
        'first_name' => 'Jonathan',
        'last_name' => 'Labreuille',
        'email' => 'jonathan@mailpoet.com'
      )
    );

    $this->mailer = new Bridge(
      $this->newsletter,
      $this->subscribers
    );
  }

  function itCanDoBasicAuth() {
    $api_key = Setting::where('name', 'api_key')
      ->findOne()->value;
    expect($this->mailer->auth())->equals(
      'Basic '
      . base64_encode('api:' . $api_key)
    );
  }

  function itCanGenerateACorrectMessage() {
    $subscriber = $this->subscribers[0];
    $message =
      $this->mailer->generateMessage($subscriber);

    expect($message['to']['address'])
      ->equals($subscriber['email']);

    expect($message['to']['name'])
      ->equals($subscriber['first_name'].' '.$subscriber['last_name']);

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
  }

  function itCanCreateARequest() {
    $request = $this->mailer->request();

    expect($request['timeout'])
      ->equals(10);

    expect($request['httpversion'])
      ->equals('1.0');

    expect($request['method'])
      ->equals('POST');

    expect($request['headers']['Content-Type'])
      ->equals('application/json');
  }

  function itCanSend() {
    /* $result = $this->mailer->send(); */
    /* expect($result)->equals(true); */
  }

  function _after() {
    Setting::where('name', 'from_name')
      ->findOne()->delete();
    Setting::where('name', 'from_address')
      ->findOne()->delete();
    Setting::where('name', 'api_key')
      ->findOne()->delete();
  }
}
