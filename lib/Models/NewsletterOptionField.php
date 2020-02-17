<?php

namespace MailPoet\Models;

use MailPoet\WP\Functions as WPFunctions;

/**
 * @property string $name
 * @property string $newsletterType
 */

class NewsletterOptionField extends Model {
  public static $_table = MP_NEWSLETTER_OPTION_FIELDS_TABLE; // phpcs:ignore PSR2.Classes.PropertyDeclaration

  public function __construct() {
    parent::__construct();
    $this->addValidations('name', [
      'required' => WPFunctions::get()->__('Please specify a name.', 'mailpoet'),
    ]);
    $this->addValidations('newsletter_type', [
      'required' => WPFunctions::get()->__('Please specify a newsletter type.', 'mailpoet'),
    ]);
  }

  public function newsletters() {
    return $this->has_many_through(
      __NAMESPACE__ . '\Newsletter',
      __NAMESPACE__ . '\NewsletterOption',
      'option_field_id',
      'newsletter_id'
    )->select_expr(MP_NEWSLETTER_OPTION_TABLE . '.value');
  }
}
