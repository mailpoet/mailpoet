<?php
namespace MailPoet\API;


if(!defined('ABSPATH')) exit;

class Mailer {
  function send($data) {
    $response = array();
    try {
      $mailer = new \MailPoet\Mailer\Mailer(
        (isset($data['mailer'])) ? $data['mailer'] : false,
        (isset($data['sender'])) ? $data['sender'] : false,
        (isset($data['reply_to'])) ? $data['reply_to'] : false
      );
      $result = $mailer->send($data['newsletter'], $data['subscriber']);
    } catch(\Exception $e) {
      $result = false;
      $response['errors'] = array($e->getMessage());
    }
    $response['result'] = ($result) ? true : false;
    return $response;
  }
}