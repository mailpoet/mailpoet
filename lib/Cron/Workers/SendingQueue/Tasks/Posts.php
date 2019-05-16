<?php
namespace MailPoet\Cron\Workers\SendingQueue\Tasks;

use MailPoet\Logging\Logger;
use MailPoet\Models\Newsletter as NewsletterModel;
use MailPoet\Models\NewsletterPost;

if (!defined('ABSPATH')) exit;

class Posts {
  function extractAndSave($rendered_newsletter, $newsletter) {
    if ($newsletter->type !== NewsletterModel::TYPE_NOTIFICATION_HISTORY) {
      return false;
    }
    Logger::getLogger('post-notifications')->addInfo(
      'extract and save posts - before',
      ['newsletter_id' => $newsletter->id]
    );
    preg_match_all(
      '/data-post-id="(\d+)"/ism',
      $rendered_newsletter['html'],
      $matched_posts_ids);
    $matched_posts_ids = $matched_posts_ids[1];
    if (!count($matched_posts_ids)) {
      return false;
    }
    $newsletter_id = $newsletter->parent_id; // parent post notification
    foreach ($matched_posts_ids as $post_id) {
      $newsletter_post = NewsletterPost::create();
      $newsletter_post->newsletter_id = $newsletter_id;
      $newsletter_post->post_id = $post_id;
      $newsletter_post->save();
    }
    Logger::getLogger('post-notifications')->addInfo(
      'extract and save posts - after',
      ['newsletter_id' => $newsletter->id, 'matched_posts_ids' => $matched_posts_ids]
    );
    return true;
  }

  function getAlcPostsCount($rendered_newsletter, \MailPoet\Models\Newsletter $newsletter) {
    $template_posts_count = substr_count($newsletter->body, 'data-post-id');
    $newsletter_posts_count = substr_count($rendered_newsletter['html'], 'data-post-id');
    return $newsletter_posts_count - $template_posts_count;
  }
}
