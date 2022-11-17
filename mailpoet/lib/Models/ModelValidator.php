<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Models;

use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;

class ModelValidator extends \MailPoetVendor\Sudzy\Engine {
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
    'nospam',
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

  public function __construct() {
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

  public function validateEmail($email) {
    $permittedLength = (strlen($email) >= self::EMAIL_MIN_LENGTH && strlen($email) <= self::EMAIL_MAX_LENGTH);
    $validEmail = WPFunctions::get()->isEmail($email) !== false && parent::_isEmail($email, null);
    return ($permittedLength && $validEmail);
  }

  public function validateNonRoleEmail($email) {
    if (!$this->validateEmail($email)) return false;
    $firstPart = strtolower(substr($email, 0, (int)strpos($email, '@')));
    return array_search($firstPart, self::ROLE_EMAILS) === false;
  }

  public function validateRenderedNewsletterBody($newsletterBody) {
    if (is_serialized($newsletterBody)) {
      $newsletterBody = unserialize($newsletterBody);
    } else if (Helpers::isJson($newsletterBody)) {
      $newsletterBody = json_decode($newsletterBody, true);
    }
    return (is_null($newsletterBody) || (is_array($newsletterBody) && !empty($newsletterBody['html']) && !empty($newsletterBody['text'])));
  }

  public function validateIPAddress(string $ip): bool {
    return (bool)filter_var($ip, FILTER_VALIDATE_IP);
  }
}
