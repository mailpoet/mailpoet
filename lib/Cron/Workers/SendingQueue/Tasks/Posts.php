<?php

namespace MailPoet\Cron\Workers\SendingQueue\Tasks;

use MailPoet\Logging\LoggerFactory;
use MailPoet\Models\Newsletter as NewsletterModel;
use MailPoet\Models\NewsletterPost;

class Posts {
  /** @var LoggerFactory */
  private $loggerFactory;

  public function __construct() {
    $this->loggerFactory = LoggerFactory::getInstance();
  }

  public function extractAndSave($renderedNewsletter, $newsletter) {
    if ($newsletter->type !== NewsletterModel::TYPE_NOTIFICATION_HISTORY) {
      return false;
    }
    $this->loggerFactory->getLogger(LoggerFactory::TOPIC_POST_NOTIFICATIONS)->addInfo(
      'extract and save posts - before',
      ['newsletter_id' => $newsletter->id]
    );
    preg_match_all(
      '/data-post-id="(\d+)"/ism',
      $renderedNewsletter['html'],
      $matchedPostsIds);
    $matchedPostsIds = $matchedPostsIds[1];
    if (!count($matchedPostsIds)) {
      return false;
    }
    $newsletterId = $newsletter->parentId; // parent post notification
    foreach ($matchedPostsIds as $postId) {
      $newsletterPost = NewsletterPost::create();
      $newsletterPost->newsletterId = $newsletterId;
      $newsletterPost->postId = $postId;
      $newsletterPost->save();
    }
    $this->loggerFactory->getLogger(LoggerFactory::TOPIC_POST_NOTIFICATIONS)->addInfo(
      'extract and save posts - after',
      ['newsletter_id' => $newsletter->id, 'matched_posts_ids' => $matchedPostsIds]
    );
    return true;
  }

  public function getAlcPostsCount($renderedNewsletter, \MailPoet\Models\Newsletter $newsletter) {
    $templatePostsCount = substr_count($newsletter->getBodyString(), 'data-post-id');
    $newsletterPostsCount = substr_count($renderedNewsletter['html'], 'data-post-id');
    return $newsletterPostsCount - $templatePostsCount;
  }
}
