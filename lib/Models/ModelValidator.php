<?php

namespace MailPoet\Models;

use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class ModelValidator extends \Sudzy\Engine {
  public $validators;

  const EMAIL_MIN_LENGTH = 6;
  const EMAIL_MAX_LENGTH = 150;

  const ROLE_EMAILS = [
    'abuse',
    'compliance',
    'devnull',
    'dns',
    'ftp',
    'hostmaster',
    'inoc',
    'ispfeedback',
    'ispsupport',
    'list-request',
    'list',
    'maildaemon',
    'noc',
    'no-reply',
    'noreply',
    'null',
    'phish',
    'phishing',
    'postmaster',
    'privacy',
    'registrar',
    'root',
    'security',
    'spam',
    'sysadmin',
    'undisclosed-recipients',
    'unsubscribe',
    'usenet',
    'uucp',
    'webmaster',
    'www',
  ];

  function __construct() {
    parent::__construct();
    $this->validators = [
      'validEmail' => 'validateEmail',
      'validRenderedNewsletterBody' => 'validateRenderedNewsletterBody',
    ];
    $this->setupValidators();
  }

  private function setupValidators() {
    $_this = $this;
    foreach ($this->validators as $validator => $action) {
      $this->addValidator($validator, function($params) use ($action, $_this) {
        $callback = [$_this, $action];
        if (is_callable($callback)) {
          return call_user_func($callback, $params);
        }
      });
    }
  }

  function validateEmail($email) {
    $permitted_length = (strlen($email) >= self::EMAIL_MIN_LENGTH && strlen($email) <= self::EMAIL_MAX_LENGTH);
    $valid_email = WPFunctions::get()->isEmail($email) !== false && parent::_isEmail($email, null);
    return ($permitted_length && $valid_email);
  }

  function validateNonRoleEmail($email) {
    if (!$this->validateEmail($email)) return false;
    $first_part = strtolower(substr($email, 0, strpos($email, '@')));
    return array_search($first_part, self::ROLE_EMAILS) === false;
  }

  function validateRenderedNewsletterBody($newsletter_body) {
    if (is_serialized($newsletter_body)) {
      $newsletter_body = unserialize($newsletter_body);
    } else if (Helpers::isJson($newsletter_body)) {
      $newsletter_body = json_decode($newsletter_body, true);
    }
    return (is_null($newsletter_body) || (is_array($newsletter_body) && !empty($newsletter_body['html']) && !empty($newsletter_body['text'])));
  }
}
