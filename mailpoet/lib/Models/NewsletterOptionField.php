<?php

namespace MailPoet\Models;

/**
 * @property string $name
 * @property string $newsletterType
 *
 * @deprecated This model is deprecated. Use \MailPoet\Newsletter\Options\NewsletterOptionFieldsRepository and
 * \MailPoet\Entities\NewsletterOptionFieldEntity instead. This class can be removed after 2022-11-11.
 */
class NewsletterOptionField extends Model {
  public static $_table = MP_NEWSLETTER_OPTION_FIELDS_TABLE; // phpcs:ignore PSR2.Classes.PropertyDeclaration

  /**
   * @deprecated
   */
  public function __construct() {
    self::deprecationError(__METHOD__);
    parent::__construct();
    $this->addValidations('name', [
      'required' => __('Please specify a name.', 'mailpoet'),
    ]);
    $this->addValidations('newsletter_type', [
      'required' => __('Please specify a newsletter type.', 'mailpoet'),
    ]);
  }

  /**
   * @deprecated
   */
  public function newsletters() {
    return $this->has_many_through(
      __NAMESPACE__ . '\Newsletter',
      __NAMESPACE__ . '\NewsletterOption',
      'option_field_id',
      'newsletter_id'
    )->select_expr(MP_NEWSLETTER_OPTION_TABLE . '.value');
  }

  /**
   * @deprecated This is here for displaying the deprecation warning for properties.
   */
  public function __get($key) {
    self::deprecationError('property "' . $key . '"');
    return parent::__get($key);
  }

  /**
   * @deprecated This is here for displaying the deprecation warning for static calls.
   */
  public static function __callStatic($name, $arguments) {
    self::deprecationError($name);
    return parent::__callStatic($name, $arguments);
  }

  private static function deprecationError($methodName) {
    trigger_error(
      'Calling ' . esc_html($methodName) . ' is deprecated and will be removed. Use \MailPoet\Newsletter\Options\NewsletterOptionFieldsRepository and \MailPoet\Entities\NewsletterOptionFieldEntity instead.',
      E_USER_DEPRECATED
    );
  }
}
