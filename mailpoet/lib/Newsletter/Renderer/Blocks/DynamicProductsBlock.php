<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\BlockPostQuery;
use MailPoet\Newsletter\DynamicProducts;

class DynamicProductsBlock {
  /**
   * Cache for rendered posts in newsletter.
   * Used to prevent duplicate post in case a newsletter contains 2 DP blocks
   * @var array
   */
  public $renderedPostsInNewsletter;

  /** @var DynamicProducts  */
  private $dynamicProducts;

  public function __construct(
    DynamicProducts $dynamicProducts
  ) {
    $this->renderedPostsInNewsletter = [];
    $this->dynamicProducts = $dynamicProducts;
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
    $products = $this->dynamicProducts->getPosts($query);
    foreach ($products as $product) {
      $postsToExclude[] = $product->get_id();
    }
    $this->setRenderedPosts((int)$newsletterId, $postsToExclude);
    return $this->dynamicProducts->transformPosts($args, $products);
  }

  private function getRenderedPosts(int $newsletterId) {
    return $this->renderedPostsInNewsletter[$newsletterId] ?? [];
  }

  private function setRenderedPosts(int $newsletterId, array $posts) {
    return $this->renderedPostsInNewsletter[$newsletterId] = $posts;
  }
}
