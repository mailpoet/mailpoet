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
      $matached_posts);
    $matached_posts = $matached_posts[1];
    if(!count($matached_posts)) {
      return $newsletter;
    }
    foreach($matached_posts as $post) {
      $newletter_post = NewsletterPost::create();
      $newletter_post->newsletter_id = $newsletter['id'];
      $newletter_post->post_id = $post;
      $newletter_post->save();
    }
    return $newsletter;
  }
}