<?php

use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\Methods\SMTP;

class SMTPTest extends MailPoetTest {
  function _before() {
    $this->settings = array(
      'method' => 'SMTP',
      'host' => getenv('WP_TEST_MAILER_SMTP_HOST') ?
        getenv('WP_TEST_MAILER_SMTP_HOST') :
        'example.com',
      'port' => 587,
      'login' => getenv('WP_TEST_MAILER_SMTP_LOGIN') ?
        getenv('WP_TEST_MAILER_SMTP_LOGIN') :
        'example.com',
      'password' => getenv('WP_TEST_MAILER_SMTP_PASSWORD') ?
        getenv('WP_TEST_MAILER_SMTP_PASSWORD') :
        'example.com',
      'authentication' => '1',
      'encryption' => 'tls'
    );
    $this->sender = array(
      'from_name' => 'Sender',
      'from_email' => 'staff@mailpoet.com',
      'from_name_email' => 'Sender <staff@mailpoet.com>'
    );
    $this->reply_to = array(
      'reply_to_name' => 'Reply To',
      'reply_to_email' => 'reply-to@mailpoet.com',
      'reply_to_name_email' => 'Reply To <reply-to@mailpoet.com>'
    );
    $this->return_path = 'bounce@mailpoet.com';
    $this->mailer = new SMTP(
      $this->settings['host'],
      $this->settings['port'],
      $this->settings['authentication'],
      $this->settings['login'],
      $this->settings['password'],
      $this->settings['encryption'],
      $this->sender,
      $this->reply_to,
      $this->return_path
    );
    $this->subscriber = 'Recipient <mailpoet-phoenix-test@mailinator.com>';
    $this->newsletter = array(
      'subject' => 'testing SMTP',
      'body' => array(
        'html' => 'HTML body',
        'text' => 'TEXT body'
      )
    );
    $this->extra_params = array(
      'unsubscribe_url' => 'http://www.mailpoet.com'
    );
  }

  function testItCanBuildMailer() {
    $mailer = $this->mailer->buildMailer();
    expect($mailer->getTransport()->getHost())
      ->equals($this->settings['host']);
    expect($mailer->getTransport()->getPort())
      ->equals($this->settings['port']);
    expect($mailer->getTransport()->getUsername())
      ->equals($this->settings['login']);
    expect($mailer->getTransport()->getPassword())
      ->equals($this->settings['password']);
    expect($mailer->getTransport()->getEncryption())
      ->equals($this->settings['encryption']);
  }

  function testWhenReturnPathIsNullItIsSetToSenderEmail() {
    $mailer = new SMTP(
      $this->settings['host'],
      $this->settings['port'],
      $this->settings['authentication'],
      $this->settings['login'],
      $this->settings['password'],
      $this->settings['encryption'],
      $this->sender,
      $this->reply_to,
      $return_path = false
    );
    expect($mailer->return_path)->equals($this->sender['from_email']);
  }

  function testItCanCreateMessage() {
    $message = $this->mailer
      ->createMessage($this->newsletter, $this->subscriber, $this->extra_params);
    expect($message->getTo())
      ->equals(array('mailpoet-phoenix-test@mailinator.com' => 'Recipient'));
    expect($message->getFrom())
      ->equals(array($this->sender['from_email'] => $this->sender['from_name']));
    expect($message->getSender())
      ->equals(array($this->sender['from_email'] => null));
    expect($message->getReplyTo())
      ->equals(array($this->reply_to['reply_to_email'] => $this->reply_to['reply_to_name']));
    expect($message->getSubject())
      ->equals($this->newsletter['subject']);
    expect($message->getBody())
      ->equals($this->newsletter['body']['html']);
    expect($message->getChildren()[0]->getContentType())
      ->equals('text/plain');
    expect($message->getHeaders()->get('List-Unsubscribe')->getValue())
      ->equals('<' . $this->extra_params['unsubscribe_url'] . '>');
  }

  function testItCanProcessSubscriber() {
    expect($this->mailer->processSubscriber('test@test.com'))
      ->equals(array('test@test.com' => ''));
    expect($this->mailer->processSubscriber('First <test@test.com>'))
      ->equals(array('test@test.com' => 'First'));
    expect($this->mailer->processSubscriber('First Last <test@test.com>'))
      ->equals(array('test@test.com' => 'First Last'));
  }

  function testItCantSendWithoutProperAuthentication() {
    if(getenv('WP_TEST_MAILER_ENABLE_SENDING') !== 'true') return;
    $this->mailer->login = 'someone';
    $this->mailer->mailer = $this->mailer->buildMailer();
    $result = $this->mailer->send(
      $this->newsletter,
      $this->subscriber
    );
    expect($result['response'])->false();
  }

  function testItCanProcessExceptionMessage() {
    $message = 'Connection could not be established with host localhost [Connection refused #111]' . PHP_EOL
      . 'Log data:' . PHP_EOL
      . '++ Starting Swift_SmtpTransport' . PHP_EOL
      . '!! Connection could not be established with host localhost [Connection refused #111] (code: 0)';
    expect($this->mailer->processExceptionMessage($message))
      ->equals('Connection could not be established with host localhost [Connection refused #111]');
  }

  function testItCanProcessLogMessageWhenOneExists() {
    $message = '++ Swift_SmtpTransport started' . PHP_EOL
      . '>> MAIL FROM:<moi@mrcasual.com>' . PHP_EOL
      . '<< 250 OK' . PHP_EOL
      . '>> RCPT TO:<test2@ietsdoenofferte.nl>' . PHP_EOL
      . '<< 550 No such recipient here' . PHP_EOL
      . '!! Expected response code 250/251/252 but got code "550", with message "550 No such recipient here' . PHP_EOL
      . '" (code: 550)' . PHP_EOL
      . '>> RSET' . PHP_EOL
      . '<< 250 Reset OK' . PHP_EOL;
    expect($this->mailer->processLogMessage('test@example.com', $extra_params = array(), $message))
      ->equals('Expected response code 250/251/252 but got code "550", with message "550 No such recipient here" (code: 550) Unprocessed subscriber: test@example.com');
    expect($this->mailer->processLogMessage('test@example.com', $extra_params = array(), $message))
      ->equals('Expected response code 250/251/252 but got code "550", with message "550 No such recipient here" (code: 550) Unprocessed subscriber: test@example.com');
  }

  function testItReturnsGenericMessageWhenLogMessageDoesNotExist() {
    expect($this->mailer->processLogMessage('test@example.com'))
      ->equals(Mailer::METHOD_SMTP . ' has returned an unknown error. Unprocessed subscriber: test@example.com');
  }

  function testItCanSend() {
    if(getenv('WP_TEST_MAILER_ENABLE_SENDING') !== 'true') return;
    $result = $this->mailer->send(
      $this->newsletter,
      $this->subscriber
    );
    expect($result['response'])->true();
  }
}