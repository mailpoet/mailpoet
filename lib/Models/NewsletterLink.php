<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class NewsletterLink extends Model {
  public static $_table = MP_NEWSLETTER_LINKS_TABLE;

  static function getByHash($hash) {
    return parent::where('hash', $hash)
      ->findOne();
  }
}
