<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class NewsletterTemplate extends Model {
  public static $_table = MP_NEWSLETTER_TEMPLATES_TABLE;

  const RECENTLY_SENT_CATEGORIES = '["recent"]';
  const RECENTLY_SENT_COUNT = 12;

  function __construct() {
    parent::__construct();

    $this->addValidations('name', array(
      'required' => __('Please specify a name.', 'mailpoet')
    ));
    $this->addValidations('body', array(
      'required' => __('The template body cannot be empty.', 'mailpoet')
    ));
  }

  static function cleanRecentlySent($data) {
    if(!empty($data['categories']) && $data['categories'] === self::RECENTLY_SENT_CATEGORIES) {
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
    if(isset($template['body'])) {
      $template['body'] = json_decode($template['body'], true);
    }
    return $template;
  }

}
