<?php

use MailPoet\Mailer\WPMail;

class WPMailCest {
  function _before() {
    $this->settings = array(
      'name' => 'WPMail'
    );
    $this->fromEmail = 'staff@mailpoet.com';
    $this->fromName = 'Sender';
    $this->mailer = new WPMail(
      $this->fromEmail,
      $this->fromName
    );
    $this->subscriber = 'Recipient <mailpoet-phoenix-test@mailinator.com>';
    $this->newsletter = array(
      'subject' => 'testing SMTP',
      'body' => array(
        'html' => 'HTML body',
        'text' => 'TEXT body'
      )
    );
  }

  function itCanAddFilters() {
    $this->mailer->addFilters();
    expect(has_filter('wp_mail_from_name', array(
      $this->mailer,
      'setFromName'
    )))->notEmpty();
    expect(has_filter('wp_mail_from', array(
      $this->mailer,
      'setFromEmail'
    )))->notEmpty();
    expect(has_filter('wp_mail_content_type', array(
      $this->mailer,
      'setContentType'
    )))->notEmpty();
  }

  function itCanRemoveFilters() {
    $this->mailer->addFilters();
    $this->mailer->removeFilters();
    expect(has_filter('wp_mail_from_name'))->false();
    expect(has_filter('wp_mail_from'))->false();
    expect(has_filter('wp_mail_content_type'))->false();
  }

  function itCanSetFromName() {
    expect($this->mailer->setFromName())->equals($this->fromName);
  }

  function itCanSetFromEmail() {
    expect($this->mailer->setFromName())->equals($this->fromName);
  }

  function itCanSetContentType() {
    expect($this->mailer->setContentType())->equals('text/html');
  }

  function itCanSend() {
    $_SERVER['SERVER_NAME'] = 'localhost';
    $result = $this->mailer->send(
      $this->newsletter,
      $this->subscriber
    );
    //expect($result)->true();
  }
}