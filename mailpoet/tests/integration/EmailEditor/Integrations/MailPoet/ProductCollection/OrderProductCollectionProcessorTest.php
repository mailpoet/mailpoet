<?php declare(strict_types = 1);

namespace MailPoet\Test\EmailEditor\Integrations\MailPoet\ProductCollection;

use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\PatternsController;
use MailPoet\EmailEditor\Integrations\MailPoet\ProductCollection\OrderProductCollectionProcessor;
use MailPoet\Util\CdnAssetUrl;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;

/**
 * @group woo
 */
class OrderProductCollectionProcessorTest extends \MailPoetTest {
  /** @var OrderProductCollectionProcessor */
  private $processor;

  /** @var int[] */
  private $productIds = [];

  /** @var int[] */
  private $orderIds = [];

  public function _before(): void {
    parent::_before();
    $this->processor = new OrderProductCollectionProcessor();
  }

  public function _after(): void {
    foreach ($this->orderIds as $orderId) {
      $order = wc_get_order($orderId);
      if ($order instanceof \WC_Order) {
        $order->delete(true);
      }
    }
    foreach ($this->productIds as $productId) {
      $product = wc_get_product($productId);
      if ($product instanceof \WC_Product) {
        $product->delete(true);
      }
    }
    $this->orderIds = [];
    $this->productIds = [];
    parent::_after();
  }

  public function testCrossSellsCollectionExcludesPurchasedProducts(): void {
    $crossSell = $this->createProduct('Cross-sell');
    $alsoPurchased = $this->createProduct('Also purchased');
    $purchased = $this->createProduct('Purchased', ['crossSellIds' => [$crossSell->get_id(), $alsoPurchased->get_id()]]);
    $order = $this->createOrder([$purchased, $alsoPurchased]);

    $ids = $this->processor->resolveProductIds(OrderProductCollectionProcessor::COLLECTION_ORDER_CROSS_SELLS, $order);

    $this->assertSame([$crossSell->get_id()], $ids);
  }

  public function testCrossSellsCollectionExcludesOutOfStockProducts(): void {
    $outOfStock = $this->createProduct('Out of stock', ['stockStatus' => 'outofstock']);
    $inStock = $this->createProduct('In stock');
    $purchased = $this->createProduct('Purchased', ['crossSellIds' => [$outOfStock->get_id(), $inStock->get_id()]]);
    $order = $this->createOrder([$purchased]);

    $ids = $this->processor->resolveProductIds(OrderProductCollectionProcessor::COLLECTION_ORDER_CROSS_SELLS, $order);

    $this->assertSame([$inStock->get_id()], $ids);
  }

  public function testCrossSellsCollectionFallsBackToRelatedProducts(): void {
    $categoryId = $this->createTerm('Fallback category', 'product_cat');
    $purchased = $this->createProduct('Purchased', ['categoryIds' => [$categoryId]]);
    $related = $this->createProduct('Related', ['categoryIds' => [$categoryId]]);
    $order = $this->createOrder([$purchased]);

    $ids = $this->processor->resolveProductIds(OrderProductCollectionProcessor::COLLECTION_ORDER_CROSS_SELLS, $order);

    $this->assertSame([$related->get_id()], $ids);
  }

  public function testSameTagCollectionFindsProductsSharingTags(): void {
    $tagId = $this->createTerm('Shared tag', 'product_tag');
    $purchased = $this->createProduct('Purchased', ['tagIds' => [$tagId]]);
    $sameTagA = $this->createProduct('Same tag A', ['tagIds' => [$tagId]]);
    $sameTagB = $this->createProduct('Same tag B', ['tagIds' => [$tagId]]);
    $this->createProduct('Unrelated');
    $order = $this->createOrder([$purchased]);

    $ids = $this->processor->resolveProductIds(OrderProductCollectionProcessor::COLLECTION_ORDER_SAME_TAG, $order);

    sort($ids);
    $expected = [$sameTagA->get_id(), $sameTagB->get_id()];
    sort($expected);
    $this->assertSame($expected, $ids);
  }

  public function testSameCategoryCollectionFindsProductsSharingCategories(): void {
    $categoryId = $this->createTerm('Shared category', 'product_cat');
    $purchased = $this->createProduct('Purchased', ['categoryIds' => [$categoryId]]);
    $sameCategory = $this->createProduct('Same category', ['categoryIds' => [$categoryId]]);
    $this->createProduct('Unrelated');
    $order = $this->createOrder([$purchased]);

    $ids = $this->processor->resolveProductIds(OrderProductCollectionProcessor::COLLECTION_ORDER_SAME_CATEGORY, $order);

    $this->assertSame([$sameCategory->get_id()], $ids);
  }

  public function testResolveReturnsNothingWhenThereAreNoRecommendableProducts(): void {
    // The purchased product gets its own category: products saved without one
    // fall into the store default category, which products created by other
    // tests may share, making the related-products fallback non-empty.
    $purchased = $this->createProduct('Purchased', ['categoryIds' => [$this->createTerm('Lonely category', 'product_cat')]]);
    $this->createProduct('Stray uncategorized');
    $order = $this->createOrder([$purchased]);

    foreach (OrderProductCollectionProcessor::ORDER_COLLECTIONS as $collection) {
      $this->assertSame([], $this->processor->resolveProductIds($collection, $order));
    }
  }

  public function testBlocksFilterFillsOnlyMarkedCollectionBlocks(): void {
    $crossSell = $this->createProduct('Cross-sell');
    $purchased = $this->createProduct('Purchased', ['crossSellIds' => [$crossSell->get_id()]]);
    $order = $this->createOrder([$purchased]);

    $content = $this->getPatternContent('product-purchase-follow-up');
    $this->assertStringContainsString(OrderProductCollectionProcessor::COLLECTION_ORDER_CROSS_SELLS, $content);

    $bestSellersBlock = '<!-- wp:woocommerce/product-collection {"query":{"woocommerceHandPickedProducts":[]},"collection":"woocommerce/product-collection/best-sellers"} --><div class="wp-block-woocommerce-product-collection"></div><!-- /wp:woocommerce/product-collection -->';

    $filter = $this->processor->createBlocksFilter(['order' => $order]);
    $this->assertIsCallable($filter);
    $blocks = $filter(parse_blocks($content . $bestSellersBlock));

    $collectionBlocks = $this->findBlocks($blocks, 'woocommerce/product-collection');
    $this->assertCount(2, $collectionBlocks);

    // The order-aware collection receives the order's cross-sell products.
    $marked = $this->blockWithCollection($collectionBlocks, OrderProductCollectionProcessor::COLLECTION_ORDER_CROSS_SELLS);
    $this->assertSame([$crossSell->get_id()], $this->handPicked($marked));
    // The best-sellers block stays a generic store collection, untouched by the order.
    $bestSellers = $this->blockWithCollection($collectionBlocks, 'woocommerce/product-collection/best-sellers');
    $this->assertSame([], $this->handPicked($bestSellers));
  }

  public function testBlocksFilterLeavesMarkedBlockUntouchedWhenNothingResolves(): void {
    // Own category for the same reason as in testResolveReturnsNothingWhenThereAreNoRecommendableProducts.
    $purchased = $this->createProduct('Purchased', ['categoryIds' => [$this->createTerm('Lonely category', 'product_cat')]]);
    $this->createProduct('Stray uncategorized');
    $order = $this->createOrder([$purchased]);

    $content = $this->getPatternContent('product-purchase-follow-up');
    $filter = $this->processor->createBlocksFilter(['order' => $order]);
    $this->assertIsCallable($filter);
    $blocks = $filter(parse_blocks($content));

    $collectionBlocks = $this->findBlocks($blocks, 'woocommerce/product-collection');
    $marked = $this->blockWithCollection($collectionBlocks, OrderProductCollectionProcessor::COLLECTION_ORDER_CROSS_SELLS);
    // The authored fallback query renders generic store products instead.
    $this->assertSame([], $this->handPicked($marked));
  }

  public function testTagAndCategoryPatternsUseTheirOrderAwareCollections(): void {
    $this->assertStringContainsString(
      OrderProductCollectionProcessor::COLLECTION_ORDER_SAME_TAG,
      $this->getPatternContent('tag-purchase-follow-up')
    );
    $this->assertStringContainsString(
      OrderProductCollectionProcessor::COLLECTION_ORDER_SAME_CATEGORY,
      $this->getPatternContent('category-purchase-follow-up')
    );
    $this->assertStringContainsString(
      OrderProductCollectionProcessor::COLLECTION_ORDER_CROSS_SELLS,
      $this->getPatternContent('first-purchase-thank-you')
    );
  }

  /**
   * @param array{crossSellIds?: int[], categoryIds?: int[], tagIds?: int[], stockStatus?: string} $args
   */
  private function createProduct(string $name, array $args = []): \WC_Product {
    $product = new \WC_Product_Simple();
    $product->set_name($name);
    $product->set_regular_price('10');
    $product->set_status('publish');
    if (isset($args['crossSellIds'])) {
      $product->set_cross_sell_ids($args['crossSellIds']);
    }
    if (isset($args['categoryIds'])) {
      $product->set_category_ids($args['categoryIds']);
    }
    if (isset($args['tagIds'])) {
      $product->set_tag_ids($args['tagIds']);
    }
    if (isset($args['stockStatus'])) {
      $product->set_stock_status($args['stockStatus']);
    }
    $product->save();
    $this->productIds[] = $product->get_id();
    return $product;
  }

  private function createTerm(string $name, string $taxonomy): int {
    $term = wp_insert_term($name . ' ' . uniqid(), $taxonomy);
    $this->assertIsArray($term);
    return (int)$term['term_id'];
  }

  /**
   * @param \WC_Product[] $products
   */
  private function createOrder(array $products): \WC_Order {
    $order = wc_create_order();
    $this->assertInstanceOf(\WC_Order::class, $order);
    foreach ($products as $product) {
      $order->add_product($product);
    }
    $order->save();
    $this->orderIds[] = $order->get_id();
    return $order;
  }

  private function getPatternContent(string $patternName): string {
    $wooCommerceHelper = $this->createMock(WooCommerceHelper::class);
    $wooCommerceHelper->method('isWooCommerceActive')->willReturn(true);
    $wooCommerceHelper->method('getWooCommerceVersion')->willReturn('10.8.0');
    $wooCommerceHelper->method('wcSupportsOrderReviewUrl')->willReturn(true);

    $patternsController = new PatternsController(
      $this->diContainer->get(CdnAssetUrl::class),
      $this->diContainer->get(WPFunctions::class),
      $wooCommerceHelper
    );

    $content = $patternsController->getPatternContent($patternName);
    $this->assertIsString($content);
    return $content;
  }

  /**
   * @param array<array-key, mixed> $blocks
   * @return array<int, array<array-key, mixed>>
   */
  private function findBlocks(array $blocks, string $blockName): array {
    $found = [];
    foreach ($blocks as $block) {
      if (!is_array($block)) {
        continue;
      }
      if (($block['blockName'] ?? null) === $blockName) {
        $found[] = $block;
      }
      $inner = $block['innerBlocks'] ?? null;
      if (is_array($inner)) {
        $found = array_merge($found, $this->findBlocks($inner, $blockName));
      }
    }
    return $found;
  }

  /**
   * @param array<array-key, mixed> $block
   * @return array<array-key, mixed>
   */
  private function handPicked(array $block): array {
    $attrs = $block['attrs'] ?? null;
    $this->assertIsArray($attrs);
    $query = $attrs['query'] ?? null;
    $this->assertIsArray($query);
    $picked = $query['woocommerceHandPickedProducts'] ?? null;
    $this->assertIsArray($picked);
    return $picked;
  }

  /**
   * @param array<int, array<array-key, mixed>> $blocks
   * @return array<array-key, mixed>
   */
  private function blockWithCollection(array $blocks, string $collection): array {
    foreach ($blocks as $block) {
      $attrs = $block['attrs'] ?? null;
      if (is_array($attrs) && ($attrs['collection'] ?? null) === $collection) {
        return $block;
      }
    }
    $this->fail(sprintf('No product collection block using the "%s" collection was found.', $collection));
  }
}
