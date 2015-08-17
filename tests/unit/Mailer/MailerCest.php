<?php
use MailPoet\Mailer\Mailer;

class MailerCest {
  function itCanGenerateMessage() {
    $newsletter = array(
      'subject' => 'A test message',
      'body' => 'Test, one, two, three.'
    );
    $mailer = new Mailer($newsletter, array());
    $subscribers = array(
      array(
        'first_name' => 'Test',
        'last_name' => 'MailPoet',
        'email' => 'testmailpoet@gmail.com'
      )
    );
    $message = $mailer->generateMessage($subscribers[0]);
    expect($message['from']['address'])->equals('marco@mailpoet.com');
    expect($message['from']['name'])->equals('');
    expect($message['to']['address'])->equals('testmailpoet@gmail.com');
    expect($message['to']['name'])->equals('Test MailPoet');
    expect($message['reply_to']['address'])->equals('staff@mailpoet.com');
    expect($message['reply_to']['name'])->equals('');
    expect($message['subject'])->equals('A test message');
    expect($message['html'])->equals('Test, one, two, three.');
    expect($message['text'])->equals('');
  }
  function itCanSend() {
    $newsletter = array(
      'subject' => 'A test message',
      'body' => 'Test, one, two, three.'
    );
    $subscribers = array(
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
    $mailer = new Mailer($newsletter, $subscribers);
    $result = $mailer->send();
    expect($result)->equals(TRUE);
  }
}