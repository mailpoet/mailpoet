<?php
namespace MailPoet\Mailer;
require_once(ABSPATH . 'wp-includes/pluggable.php');

if(!defined('ABSPATH')) exit;

class WPMail {
  function __construct($fromEmail, $fromName) {
    $this->fromEmail = $fromEmail;
    $this->fromName = $fromName;
    add_filter('wp_mail_from', array(
      $this,
      'setFromEmail'
    ));
    $this->filters = array(
      'wp_mail_from' => 'setFromEmail',
      'wp_mail_from_name' => 'setFromName',
      'wp_mail_content_type' => 'setContentType'
    );
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
    return $this->fromEmail;
  }

  function setFromName() {
    return $this->fromName;
  }

  function setContentType() {
    return 'text/html';
  }

  function send($newsletter, $subscriber) {
    $this->addFilters();
    $result = wp_mail(
      $subscriber, $newsletter['subject'],
      (!empty($newsletter['body']['html'])) ? $newsletter['body']['html'] : $newsletter['body']['text']
    );
    $this->removeFilters();
    return ($result === true);
  }
}