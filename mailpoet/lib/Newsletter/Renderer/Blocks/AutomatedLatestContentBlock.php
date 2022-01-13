<?php

namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterPostEntity;
use MailPoet\Models\Newsletter;
use MailPoet\Newsletter\AutomatedLatestContent;
use MailPoet\Newsletter\NewsletterPostsRepository;

class AutomatedLatestContentBlock {
  /**
   * Cache for rendered posts in newsletter.
   * Used to prevent duplicate post in case a newsletter contains 2 ALC blocks
   * @var array
   */
  public $renderedPostsInNewsletter;

  /** @var AutomatedLatestContent  */
  private $ALC;

  /** @var NewsletterPostsRepository */
  private $newsletterPostsRepository;

  public function __construct(
    NewsletterPostsRepository $newsletterPostsRepository,
    AutomatedLatestContent $ALC
  ) {
    $this->renderedPostsInNewsletter = [];
    $this->ALC = $ALC;
    $this->newsletterPostsRepository = $newsletterPostsRepository;
  }

  public function render(NewsletterEntity $newsletter, $args) {
    $newerThanTimestamp = false;
    $newsletterId = false;
    if ($newsletter->getType() === Newsletter::TYPE_NOTIFICATION_HISTORY) {
      $parent = $newsletter->getParent();
      if ($parent instanceof NewsletterEntity) {
        $newsletterId = $parent->getId();

        $lastPost = $this->newsletterPostsRepository->findOneBy(['newsletter' => $parent], ['createdAt' => 'desc']);
        if ($lastPost instanceof NewsletterPostEntity) {
          $newerThanTimestamp = $lastPost->getCreatedAt();
        }

      }
    }
    $postsToExclude = $this->getRenderedPosts((int)$newsletterId);
    $aLCPosts = $this->ALC->getPosts($args, $postsToExclude, $newsletterId, $newerThanTimestamp);
    foreach ($aLCPosts as $post) {
      $postsToExclude[] = $post->ID;
    }
    $this->setRenderedPosts((int)$newsletterId, $postsToExclude);
    return $this->ALC->transformPosts($args, $aLCPosts);
  }

  private function getRenderedPosts(int $newsletterId) {
    return $this->renderedPostsInNewsletter[$newsletterId] ?? [];
  }

  private function setRenderedPosts(int $newsletterId, array $posts) {
    return $this->renderedPostsInNewsletter[$newsletterId] = $posts;
  }
}
