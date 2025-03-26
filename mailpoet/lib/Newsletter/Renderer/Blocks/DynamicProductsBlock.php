<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\AutomatedLatestContent;
use MailPoet\Newsletter\BlockPostQuery;

class DynamicProductsBlock {
  /**
   * Cache for rendered posts in newsletter.
   * Used to prevent duplicate post in case a newsletter contains 2 DP blocks
   * @var array
   */
  public $renderedPostsInNewsletter;

  /** @var AutomatedLatestContent  */
  private $ALC;

  public function __construct(
    AutomatedLatestContent $ALC
  ) {
    $this->renderedPostsInNewsletter = [];
    $this->ALC = $ALC;
  }

  public function render(NewsletterEntity $newsletter, $args) {
    $newerThanTimestamp = false;
    $newsletterId = $newsletter->getId();
    $postsToExclude = $this->getRenderedPosts((int)$newsletterId);
    $query = new BlockPostQuery([
      'args' => $args,
      'contentType' => 'product',
      'postsToExclude' => $postsToExclude,
      'newsletterId' => $newsletterId,
      'newerThanTimestamp' => $newerThanTimestamp,
      'dynamic' => true,
    ]);
    $aLCPosts = $this->ALC->getPosts($query);
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
