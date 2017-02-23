<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class NewsletterOption extends Model {
  public static $_table = MP_NEWSLETTER_OPTION_TABLE;

  static function createOrUpdate($data = array()) {
    if(!is_array($data) || empty($data['newsletter_id']) || empty($data['option_field_id'])) return;

    $newsletter_option = self::where('option_field_id', $data['option_field_id'])
      ->where('newsletter_id', $data['newsletter_id'])
      ->findOne();

    if(empty($newsletter_option)) $newsletter_option = self::create();

    $newsletter_option->newsletter_id = $data['newsletter_id'];
    $newsletter_option->option_field_id = $data['option_field_id'];
    $newsletter_option->value = $data['value'];
    $newsletter_option->save();
    return $newsletter_option;
  }
}
