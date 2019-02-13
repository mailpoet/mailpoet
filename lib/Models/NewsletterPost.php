<?php
namespace MailPoet\Models;

if (!defined('ABSPATH')) exit;

class NewsletterPost extends Model {
  public static $_table = MP_NEWSLETTER_POSTS_TABLE;

  static function getNewestNewsletterPost($newsletter_id) {
    return self::where('newsletter_id', $newsletter_id)
      ->orderByDesc('created_at')
      ->findOne();
  }
}
