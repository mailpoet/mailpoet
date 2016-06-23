<?php
namespace MailPoet\Cron\Workers\SendingQueue\Tasks;

use MailPoet\Models\NewsletterPost;

if(!defined('ABSPATH')) exit;

class Posts {
  static function extractAndSave(array $newsletter) {
    if(empty($newsletter['rendered_body']['html']) || empty($newsletter['id'])) {
      return;
    }
    preg_match_all(
      '/data-post-id="(\d+)"/ism',
      $newsletter['rendered_body']['html'],
      $matched_posts_ids);
    $matched_posts_ids = $matched_posts_ids[1];
    if(!count($matched_posts_ids)) {
      return $newsletter;
    }
    foreach($matched_posts_ids as $post_id) {
      $newletter_post = NewsletterPost::create();
      $newletter_post->newsletter_id = $newsletter['id'];
      $newletter_post->post_id = $post_id;
      $newletter_post->save();
    }
  }
}