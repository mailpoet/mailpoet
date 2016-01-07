<?php
namespace MailPoet\Mailer\Methods;

require_once(ABSPATH . 'wp-includes/pluggable.php');

if(!defined('ABSPATH')) exit;

class WPMail {
  public $from_email;
  public $from_name;
  public $filters = array(
    'wp_mail_from' => 'setFromEmail',
    'wp_mail_from_name' => 'setFromName',
    'wp_mail_content_type' => 'setContentType'
  );

  function __construct($from_email, $from_name) {
    $this->from_email = $from_email;
    $this->from_name = $from_name;
  }

  function addFilters() {
    foreach($this->filters as $filter => $method) {
      add_filter($filter, array(
        $this,
        $method
      ));
    }
  }

  function removeFilters() {
    foreach($this->filters as $filter => $method) {
      remove_filter($filter, array(
        $this,
        $method
      ));
    }
  }

  function setFromEmail() {
    return $this->from_email;
  }

  function setFromName() {
    return $this->from_name;
  }

  function setContentType() {
    return 'text/html';
  }

  function send($newsletter, $subscriber) {
    $this->addFilters();
    $result = wp_mail(
      $subscriber, $newsletter['subject'],
      (!empty($newsletter['body']['html'])) ?
        $newsletter['body']['html'] :
        $newsletter['body']['text']
    );
    $this->removeFilters();
    return ($result === true);
  }
}