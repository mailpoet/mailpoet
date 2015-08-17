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

  function itCanGenerateACorrectMessage() {
    $messages = $this->mailer->messages();
    expect($messages[0]['to']['address'])
      ->equals($this->subscribers[0]['email']);
  }

  function itCanSend() {
    /* $result = $this->mailer->send(); */
    /* expect($result)->equals(true); */
  }
}
