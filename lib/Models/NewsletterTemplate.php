<?php

namespace MailPoet\Models;

use MailPoet\WP\Functions as WPFunctions;

/**
 * @property string $name
 * @property int|null $newsletterId
 * @property string $categories
 * @property string $description
 * @property string|null $body
 * @property string|null $thumbnail
 * @property int|null $readonly
 */
class NewsletterTemplate extends Model {
  public static $_table = MP_NEWSLETTER_TEMPLATES_TABLE; // phpcs:ignore PSR2.Classes.PropertyDeclaration

  const RECENTLY_SENT_CATEGORIES = '["recent"]';
  const RECENTLY_SENT_COUNT = 12;

  public function __construct() {
    parent::__construct();

    $this->addValidations('name', [
      'required' => WPFunctions::get()->__('Please specify a name.', 'mailpoet'),
    ]);
    $this->addValidations('body', [
      'required' => WPFunctions::get()->__('The template body cannot be empty.', 'mailpoet'),
    ]);
  }

  public static function cleanRecentlySent($data) {
    if (!empty($data['categories']) && $data['categories'] === self::RECENTLY_SENT_CATEGORIES) {
      $ids = parent::where('categories', self::RECENTLY_SENT_CATEGORIES)
        ->select('id')
        ->orderByDesc('id')
        ->limit(self::RECENTLY_SENT_COUNT)
        ->findMany();
      $ids = array_map(function ($template) {
        return $template->id;
      }, $ids);
      parent::where('categories', self::RECENTLY_SENT_CATEGORIES)
        ->whereNotIn('id', $ids)
        ->deleteMany();
    }
  }
}
