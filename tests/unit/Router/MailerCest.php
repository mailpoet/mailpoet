<?php
use MailPoet\Router\Mailer;

class MailerCest {

  function __construct() {
    $this->router = new Mailer();
  }

  function itCanConstruct() {
    expect($this->router->from)->equals('Sender <mailpoet-test1@mailinator.com>');
  }

  function itCanTransformSubscriber() {
    expect($this->router->transformSubscriber('test@email.com'))
      ->equals('test@email.com');
    expect($this->router->transformSubscriber(
      array(
        'first_name' => 'First',
        'email' => 'test@email.com'
      ))
    )->equals('First <test@email.com>');
    expect($this->router->transformSubscriber(
      array(
        'last_name' => 'Last',
        'email' => 'test@email.com'
      ))
    )->equals('Last <test@email.com>');
    expect($this->router->transformSubscriber(
      array(
        'first_name' => 'First',
        'last_name' => 'Last',
        'email' => 'test@email.com'
      ))
    )->equals('First Last <test@email.com>');
  }

  function itCanConfigureMailer() {
    $mailer = $this->router->configureMailer();
    $class = 'Mailpoet\\Mailer\\' .
      $this->router->mailer['type'] . '\\' .
      $this->router->mailer['name'];
    expect($mailer instanceof $class)->true();
    expect(method_exists($mailer, 'send'))->true();
  }

  function itCanSend() {
    $newsletter = array(
      'subject' => 'testing Mailer router with '.$this->router->mailer['name'],
      'body' => array(
        'html' => 'HTML body',
        'text' => 'TEXT body'
      )
    );
    $subscriber = array(
      'first_name' => 'First',
      'last_name' => 'Last',
      'email' => 'mailpoet-test1@mailinator.com'
    );
    expect($this->router->send($newsletter, $subscriber))->true();
  }
}
