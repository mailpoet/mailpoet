<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Models;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Services\Validator;
use MailPoet\Util\Helpers;

class ModelValidator extends \MailPoetVendor\Sudzy\Engine {
  public $validators;

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
    $validator = ContainerWrapper::getInstance()->get(Validator::class);
    return $validator->validateEmail($email);
  }

  public function validateRenderedNewsletterBody($newsletterBody) {
    if (is_serialized($newsletterBody)) {
      $newsletterBody = unserialize($newsletterBody);
    } else if (Helpers::isJson($newsletterBody)) {
      $newsletterBody = json_decode($newsletterBody, true);
    }
    return (is_null($newsletterBody) || (is_array($newsletterBody) && !empty($newsletterBody['html']) && !empty($newsletterBody['text'])));
  }
}
