<?php
namespace MailPoet\API\JSON\v1;
use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\Mailer\MailerLog;

if(!defined('ABSPATH')) exit;

class Mailer extends APIEndpoint {
  function send($data = array()) {
    try {
      $mailer = new \MailPoet\Mailer\Mailer(
        (isset($data['mailer'])) ? $data['mailer'] : false,
        (isset($data['sender'])) ? $data['sender'] : false,
        (isset($data['reply_to'])) ? $data['reply_to'] : false
      );
      $extra_params = array(
        'test_email' => true
      );
      $result = $mailer->send($data['newsletter'], $data['subscriber'], $extra_params);
    } catch(\Exception $e) {
      return $this->errorResponse(array(
        $e->getCode() => $e->getMessage()
      ));
    }

    if($result['response'] === false) {
      $error = sprintf(
        __('The email could not be sent: %s', 'mailpoet'),
        $result['error_message']
      );
      return $this->errorResponse(array(APIError::BAD_REQUEST => $error));
    } else {
      return $this->successResponse(null);
    }
  }

  function resumeSending() {
    MailerLog::resumeSending();
    return $this->successResponse(null);
  }
}