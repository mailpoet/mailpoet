<?php
namespace MailPoet\Cron\Workers\SendingQueue\Tasks;

use MailPoet\Models\Newsletter as NewsletterModel;
use MailPoet\Models\NewsletterPost;

if(!defined('ABSPATH')) exit;

class Posts {
  static function extractAndSave($rendered_newsletter, $newsletter) {
    preg_match_all(
      '/data-post-id="(\d+)"/ism',
      $rendered_newsletter['html'],
      $matched_posts_ids);
    $matched_posts_ids = $matched_posts_ids[1];
    if(!count($matched_posts_ids)) {
      return false;
    }
    $newsletter_id = ($newsletter->type === NewsletterModel::TYPE_NOTIFICATION_HISTORY) ?
      $newsletter->parent_id :
      $newsletter->id;
    foreach($matched_posts_ids as $post_id) {
      $newsletter_post = NewsletterPost::create();
      $newsletter_post->newsletter_id = $newsletter_id;
      $newsletter_post->post_id = $post_id;
      $newsletter_post->save();
    }
    return true;
  }
}