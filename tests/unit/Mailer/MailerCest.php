<?php
use MailPoet\Mailer\Mailer;

class MailerCest {
  function itCanSend() {
    $newsletter = array(
      'subject' => 'A test message',
      'body' => 'Test, one, two, three.'
    );
    $subscribers = array(
      1 => array(
        'first_name' => 'Test',
        'last_name' => 'MailPoet',
        'email' => 'testmailpoet@gmail.com'
      )
    );
    $mailer = new Mailer($newsletter, $subscribers);
    $result = $mailer->send();
    expect($result)->equals(TRUE);
  }
}