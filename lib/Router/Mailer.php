<?php
namespace MailPoet\Router;

if(!defined('ABSPATH')) exit;

class Mailer {
  function send($data) {
    $mailer = new \MailPoet\Mailer\Mailer(
      (isset($data['mailer'])) ? $data['mailer'] : false,
      (isset($data['sender'])) ? $data['sender'] : false,
      (isset($data['reply_to'])) ? $data['reply_to'] : false
    );
    $result = $mailer->send($data['newsletter'], $data['subscriber']);
    wp_send_json(
      array(
        'result' => ($result) ? true : false
      )
    );
  }
}