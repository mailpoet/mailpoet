<?php
namespace MailPoet\Host;

class SMTP {

  private static $_hosts = array(
    'amazon' => array(
      'name' => 'Amazon SES',
      'api' => false,
      'emails' => 100,
      'interval' => 5
    ),
    'elasticemail' => array(
      'name' => 'ElasticEmail',
      'api' => true,
      'emails' => 100,
      'interval' => 5
    ),
    'mailgun' => array(
      'name' => 'MailGun',
      'api' => false,
      'emails' => 100,
      'interval' => 5
    ),
    'sendgrid' => array(
      'name' => 'SendGrid',
      'api' => true,
      'emails' => 100,
      'interval' => 5
    )
  );

  // returns the list of hosts as an array
  public static function getList() {
    return static::$_hosts;
  }
}