<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\Config\AccessControl;
use MailPoet\Config\Installer;
use MailPoet\Services\Bridge;
use MailPoet\WP\DateTime;

if(!defined('ABSPATH')) exit;

class Services extends APIEndpoint {
  public $bridge;
  public $date_time;
  public $permissions = array(
    'global' => AccessControl::PERMISSION_MANAGE_SETTINGS
  );

  function __construct() {
    $this->bridge = new Bridge();
    $this->date_time = new DateTime();
  }

  function checkMSSKey($data = array()) {
    $key = isset($data['key']) ? trim($data['key']) : null;

    if(!$key) {
      return $this->badRequest(array(
        APIError::BAD_REQUEST  => __('Please specify a key.', 'mailpoet')
      ));
    }

    try {
      $result = $this->bridge->checkMSSKey($key);
      $this->bridge->storeMSSKeyAndState($key, $result);
    } catch(\Exception $e) {
      return $this->errorResponse(array(
        $e->getCode() => $e->getMessage()
      ));
    }

    $state = !empty($result['state']) ? $result['state'] : null;

    $success_message = null;
    if($state == Bridge::KEY_VALID) {
      $success_message = __('Your MailPoet Sending Service key has been successfully validated.', 'mailpoet');
    } elseif($state == Bridge::KEY_EXPIRING) {
      $success_message = sprintf(
        __('Your MailPoet Sending Service key expires on %s!', 'mailpoet'),
        $this->date_time->formatDate(strtotime($result['data']['expire_at']))
      );
    }

    if($success_message) {
      return $this->successResponse(array('message' => $success_message));
    }

    switch($state) {
      case Bridge::KEY_INVALID:
        $error = __('Your MailPoet Sending Service key is invalid.', 'mailpoet');
        break;
      case Bridge::KEY_ALREADY_USED:
        $error = __('Your MailPoet Sending Service key is already used on another site.', 'mailpoet');
        break;
      default:
        $code = !empty($result['code']) ? $result['code'] : Bridge::CHECK_ERROR_UNKNOWN;
        $error = sprintf(
          __('Error validating MailPoet Sending Service key, please try again later (%s)', 'mailpoet'),
          $this->getErrorDescriptionByCode($code)
        );
        break;
    }

    return $this->errorResponse(array(APIError::BAD_REQUEST => $error));
  }

  function checkPremiumKey($data = array()) {
    $key = isset($data['key']) ? trim($data['key']) : null;

    if(!$key) {
      return $this->badRequest(array(
        APIError::BAD_REQUEST  => __('Please specify a key.', 'mailpoet')
      ));
    }

    try {
      $result = $this->bridge->checkPremiumKey($key);
      $this->bridge->storePremiumKeyAndState($key, $result);
    } catch(\Exception $e) {
      return $this->errorResponse(array(
        $e->getCode() => $e->getMessage()
      ));
    }

    $state = !empty($result['state']) ? $result['state'] : null;

    $success_message = null;
    if($state == Bridge::KEY_VALID) {
      $success_message = __('Your Premium key has been successfully validated.', 'mailpoet');
    } elseif($state == Bridge::KEY_EXPIRING) {
      $success_message = sprintf(
        __('Your Premium key expires on %s.', 'mailpoet'),
        $this->date_time->formatDate(strtotime($result['data']['expire_at']))
      );
    }

    if($success_message) {
      return $this->successResponse(
        array('message' => $success_message),
        Installer::getPremiumStatus()
      );
    }

    switch($state) {
      case Bridge::KEY_INVALID:
        $error = __('Your Premium key is invalid.', 'mailpoet');
        break;
      case Bridge::KEY_ALREADY_USED:
        $error = __('Your Premium key is already used on another site.', 'mailpoet');
        break;
      default:
        $code = !empty($result['code']) ? $result['code'] : Bridge::CHECK_ERROR_UNKNOWN;
        $error = sprintf(
          __('Error validating Premium key, please try again later (%s)', 'mailpoet'),
          $this->getErrorDescriptionByCode($code)
        );
        break;
    }

    return $this->errorResponse(array(APIError::BAD_REQUEST => $error));
  }

  private function getErrorDescriptionByCode($code) {
    switch($code) {
      case Bridge::CHECK_ERROR_UNAVAILABLE:
        $text = __('Service unavailable', 'mailpoet');
        break;
      case Bridge::CHECK_ERROR_UNKNOWN:
        $text = __('Contact your hosting support to check the connection between your host and https://bridge.mailpoet.com', 'mailpoet');
        break;
      default:
        $text = sprintf(_x('code: %s', 'Error code (inside parentheses)', 'mailpoet'), $code);
        break;
    }

    return $text;
  }
}
