<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Models;

/**
 * @property int $newsletterId
 * @property int $optionFieldId
 * @property string $value
 * @property string $updatedAt
 *
 * @deprecated This model is deprecated. Use \MailPoet\Newsletter\Options\NewsletterOptionsRepository and
 * \MailPoet\Entities\NewsletterOptionEntity instead. This class can be removed after 2022-11-11.
 */
class NewsletterOption extends Model {
  public static $_table = MP_NEWSLETTER_OPTION_TABLE; // phpcs:ignore PSR2.Classes.PropertyDeclaration

  /**
   * @deprecated
   */
  public static function createOrUpdate($data = []) {
    self::deprecationError(__METHOD__);
    if (!is_array($data) || empty($data['newsletter_id']) || empty($data['option_field_id'])) {
      return;
    }
    return parent::_createOrUpdate($data, [
      'option_field_id' => $data['option_field_id'],
      'newsletter_id' => $data['newsletter_id'],
    ]);
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
      'Calling ' . esc_html($methodName) . ' is deprecated and will be removed. Use \MailPoet\Newsletter\Options\NewsletterOptionsRepository and \MailPoet\Entities\NewsletterOptionEntity instead.',
      E_USER_DEPRECATED
    );
  }
}
