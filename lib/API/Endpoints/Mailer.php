<?php
namespace MailPoet\API\Endpoints;
use MailPoet\API\Endpoint as APIEndpoint;
use MailPoet\API\Error as APIError;

if(!defined('ABSPATH')) exit;

class Mailer extends APIEndpoint {
  function send($data = array()) {
    try {
      $mailer = new \MailPoet\Mailer\Mailer(
        (isset($data['mailer'])) ? $data['mailer'] : false,
        (isset($data['sender'])) ? $data['sender'] : false,
        (isset($data['reply_to'])) ? $data['reply_to'] : false
      );
      $result = $mailer->send($data['newsletter'], $data['subscriber']);
    } catch(\Exception $e) {
      return $this->errorResponse(array(
        $e->getCode() => $e->getMessage()
      ));
    }

    if($result === false) {
      return $this->errorResponse(array(
        APIError::BAD_REQUEST => __("The email could not be sent. Please check your settings.")
      ));
    } else {
      return $this->successResponse(null);
    }
  }
}