<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\AutomaticEmails\WooCommerce\Events\AbandonedCart;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Newsletter\BlockPostQuery;
use MailPoet\Newsletter\DynamicProducts;
use MailPoet\WP\Functions as WPFunctions;

class DynamicProductsBlock {
  // For Order subject - products from the order
  const ORDER_PRODUCTS_META_NAME = 'order_product_ids';

  // For Order subject - cross-sell products
  const ORDER_CROSS_SELL_PRODUCTS_META_NAME = 'order_cross_sell_product_ids';

  /**
   * Cache for rendered posts in newsletter.
   * Used to prevent duplicate post in case a newsletter contains 2 DP blocks
   * @var array
   */
  public $renderedPostsInNewsletter;

  /** @var DynamicProducts  */
  private $dynamicProducts;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    DynamicProducts $dynamicProducts,
    WPFunctions $wp
  ) {
    $this->renderedPostsInNewsletter = [];
    $this->dynamicProducts = $dynamicProducts;
    $this->wp = $wp;
  }

  public function render(NewsletterEntity $newsletter, $args, $preview = false, ?SendingQueueEntity $sendingQueue = null) {
    $newerThanTimestamp = false;
    $newsletterId = $newsletter->getId();
    $postsToExclude = $this->getRenderedPosts((int)$newsletterId);

    // Check if we have specific product IDs from subject metadata
    $productIds = [];

    if (!$preview && $sendingQueue) {
      $meta = $sendingQueue->getMeta();

      if (!empty($args['dynamicProductsType'])) {
        switch ($args['dynamicProductsType']) {
          case 'order':
            // Check for OrderSubject products
            if (!empty($meta[self::ORDER_PRODUCTS_META_NAME])) {
              $productIds = $meta[self::ORDER_PRODUCTS_META_NAME];
            }
            break;
          case 'cross-sell':
            // Check for OrderSubject cross-sells
            if (!empty($meta[self::ORDER_CROSS_SELL_PRODUCTS_META_NAME])) {
              $productIds = $meta[self::ORDER_CROSS_SELL_PRODUCTS_META_NAME];
            }
            break;
          case 'cart':
            // Check for AbandonedCartSubject products
            if (!empty($meta[AbandonedCart::TASK_META_NAME])) {
              $productIds = $meta[AbandonedCart::TASK_META_NAME];
            }
            break;
        }
      }

      // Check for Premium dynamic products
      $productIds = (array)$this->wp->applyFilters('mailpoet_dynamic_products_meta', $productIds, $sendingQueue);
      $productIds = array_map(function($id) {
        return is_numeric($id) ? (int)$id : 0;
      }, $productIds);
    }

    // Define query parameters
    $queryArgs = [
      'args' => $args,
      'contentType' => 'product',
      'postsToExclude' => $postsToExclude,
      'newsletterId' => $newsletterId,
      'newerThanTimestamp' => $newerThanTimestamp,
      'dynamic' => true,
    ];

    // If we have specific product IDs, add them to the query
    if (!empty($productIds)) {
      $queryArgs['includeProductIds'] = $productIds;
    } else {
      // Don't show any products if we don't have specific product IDs
      // and the user didn't choose "selected" in the dynamic products type dropdown
      if ($sendingQueue) {
        if (!empty($args['dynamicProductsType']) || $args['dynamicProductsType'] !== 'selected') {
          $queryArgs['includeProductIds'] = [0];
        }
      }
    }

    $query = new BlockPostQuery($queryArgs);
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
