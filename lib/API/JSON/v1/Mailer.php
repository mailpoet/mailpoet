<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\Config\AccessControl;
use MailPoet\Mailer\MailerLog;

if(!defined('ABSPATH')) exit;

class Mailer extends APIEndpoint {
  public $permissions = array(
    'global' => AccessControl::PERMISSION_MANAGE_EMAILS
  );

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

    if($result['response'] === false) {
      $error = sprintf(
        __('The email could not be sent: %s', 'mailpoet'),
        $result['error']->getMessage()
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
