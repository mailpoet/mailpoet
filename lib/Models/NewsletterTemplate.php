<?php
namespace MailPoet\Models;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

/**
 * @property string $name
 * @property int|null $newsletter_id
 * @property string $categories
 * @property string $description
 * @property string|null $body
 * @property string|null $thumbnail
 * @property int|null $readonly
 */
class NewsletterTemplate extends Model {
  public static $_table = MP_NEWSLETTER_TEMPLATES_TABLE;

  const RECENTLY_SENT_CATEGORIES = '["recent"]';
  const RECENTLY_SENT_COUNT = 12;

  function __construct() {
    parent::__construct();

    $this->addValidations('name', [
      'required' => WPFunctions::get()->__('Please specify a name.', 'mailpoet'),
    ]);
    $this->addValidations('body', [
      'required' => WPFunctions::get()->__('The template body cannot be empty.', 'mailpoet'),
    ]);
  }

  static function cleanRecentlySent($data) {
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

  function asArray() {
    $template = parent::asArray();
    if (isset($template['body'])) {
      $template['body'] = json_decode($template['body'], true);
    }
    return $template;
  }

}
