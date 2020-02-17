<?php

namespace MailPoet\Models;

/**
 * @property int $newsletterId
 * @property int $optionFieldId
 * @property string $value
 * @property string $updatedAt
 */
class NewsletterOption extends Model {
  public static $_table = MP_NEWSLETTER_OPTION_TABLE; // phpcs:ignore PSR2.Classes.PropertyDeclaration

  public static function createOrUpdate($data = []) {
    if (!is_array($data) || empty($data['newsletter_id']) || empty($data['option_field_id'])) {
      return;
    }
    return parent::_createOrUpdate($data, [
      'option_field_id' => $data['option_field_id'],
      'newsletter_id' => $data['newsletter_id'],
    ]);
  }
}
