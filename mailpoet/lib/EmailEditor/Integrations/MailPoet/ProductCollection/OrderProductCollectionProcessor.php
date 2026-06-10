<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\ProductCollection;

/**
 * Fills product collection blocks using one of the MailPoet order-aware
 * collections with products derived from the order that triggered the email.
 *
 * The collection slug stored in the block's `collection` attribute is the
 * marker — it is a declared block attribute, so it survives editing and
 * re-saving the email in the block editor. The same slugs are registered in
 * the editor (see assets/js/src/mailpoet-email-editor-integration/order-product-collections.ts)
 * so merchants see what the block does.
 *
 * When a source yields no products, related products of the purchased items
 * are used instead. When even those are missing, the block is left untouched
 * and renders the generic store query authored in the pattern.
 */
class OrderProductCollectionProcessor {
  public const COLLECTION_ORDER_CROSS_SELLS = 'mailpoet/product-collection/order-cross-sells';
  public const COLLECTION_ORDER_SAME_TAG = 'mailpoet/product-collection/order-same-tag';
  public const COLLECTION_ORDER_SAME_CATEGORY = 'mailpoet/product-collection/order-same-category';

  public const ORDER_COLLECTIONS = [
    self::COLLECTION_ORDER_CROSS_SELLS,
    self::COLLECTION_ORDER_SAME_TAG,
    self::COLLECTION_ORDER_SAME_CATEGORY,
  ];

  private const PRODUCT_COLLECTION_BLOCK = 'woocommerce/product-collection';
  private const MAX_PRODUCTS = 24;
  private const RELATED_PRODUCTS_PER_ITEM = 8;

  /**
   * Build a `woocommerce_email_blocks_renderer_parsed_blocks` filter bound to the
   * order in the render context. Returns null when there is no order, leaving
   * marked blocks to render their authored fallback query.
   *
   * @param array<string, mixed> $renderContext
   */
  public function createBlocksFilter(array $renderContext): ?callable {
    $order = $renderContext['order'] ?? null;
    if (!$order instanceof \WC_Order) {
      return null;
    }

    return function (array $blocks) use ($order): array {
      return $this->fillOrderCollections($blocks, $order);
    };
  }

  /**
   * Set hand-picked products on every product collection block using one of the
   * order-aware collections, descending into inner blocks. Each collection is
   * resolved at most once per call.
   *
   * @param array<array-key, mixed> $blocks
   * @return array<array-key, mixed>
   */
  public function fillOrderCollections(array $blocks, \WC_Order $order): array {
    $resolvedIds = [];
    return $this->walkBlocks($blocks, $order, $resolvedIds);
  }

  /**
   * Product IDs for an order-aware collection, excluding the purchased products
   * themselves and products that are not displayable (unpublished, out of stock).
   * Falls back to related products of the purchased items when the primary
   * source yields nothing.
   *
   * @return int[]
   */
  public function resolveProductIds(string $collection, \WC_Order $order): array {
    $purchasedProducts = $this->getPurchasedProducts($order);
    if (!$purchasedProducts) {
      return [];
    }
    $purchasedIds = array_keys($purchasedProducts);

    switch ($collection) {
      case self::COLLECTION_ORDER_CROSS_SELLS:
        $ids = $this->getCrossSellIds($purchasedProducts);
        break;
      case self::COLLECTION_ORDER_SAME_TAG:
        $ids = $this->getSameTermProductIds($purchasedProducts, 'product_tag_id');
        break;
      case self::COLLECTION_ORDER_SAME_CATEGORY:
        $ids = $this->getSameTermProductIds($purchasedProducts, 'product_category_id');
        break;
      default:
        return [];
    }

    $ids = $this->filterDisplayable($ids, $purchasedIds);
    if (!$ids) {
      $ids = $this->filterDisplayable($this->getRelatedIds($purchasedIds), $purchasedIds);
    }

    return array_slice($ids, 0, self::MAX_PRODUCTS);
  }

  /**
   * @param array<array-key, mixed> $blocks
   * @param array<string, int[]> $resolvedIds
   * @return array<array-key, mixed>
   */
  private function walkBlocks(array $blocks, \WC_Order $order, array &$resolvedIds): array {
    foreach ($blocks as $index => $block) {
      if (!is_array($block)) {
        continue;
      }

      $collection = $this->getOrderCollectionSlug($block);
      if ($collection !== null) {
        if (!array_key_exists($collection, $resolvedIds)) {
          $resolvedIds[$collection] = $this->resolveProductIds($collection, $order);
        }
        if ($resolvedIds[$collection]) {
          $block = $this->setHandPickedProducts($block, $resolvedIds[$collection]);
        }
      }

      if (isset($block['innerBlocks']) && is_array($block['innerBlocks'])) {
        $block['innerBlocks'] = $this->walkBlocks($block['innerBlocks'], $order, $resolvedIds);
      }

      $blocks[$index] = $block;
    }

    return $blocks;
  }

  /**
   * @param array<array-key, mixed> $block
   */
  private function getOrderCollectionSlug(array $block): ?string {
    if (($block['blockName'] ?? null) !== self::PRODUCT_COLLECTION_BLOCK) {
      return null;
    }

    $attrs = $block['attrs'] ?? null;
    $collection = is_array($attrs) ? ($attrs['collection'] ?? null) : null;
    return is_string($collection) && in_array($collection, self::ORDER_COLLECTIONS, true) ? $collection : null;
  }

  /**
   * @param array<array-key, mixed> $block
   * @param int[] $productIds
   * @return array<array-key, mixed>
   */
  private function setHandPickedProducts(array $block, array $productIds): array {
    $attrs = is_array($block['attrs'] ?? null) ? $block['attrs'] : [];
    $query = is_array($attrs['query'] ?? null) ? $attrs['query'] : [];
    $query['woocommerceHandPickedProducts'] = $productIds;
    $attrs['query'] = $query;
    $block['attrs'] = $attrs;
    return $block;
  }

  /**
   * Purchased products keyed by product ID. Uses the parent product for
   * variations because cross-sells and taxonomy terms live on the parent.
   *
   * @return array<int, \WC_Product>
   */
  private function getPurchasedProducts(\WC_Order $order): array {
    $products = [];
    foreach ($order->get_items() as $item) {
      if (!$item instanceof \WC_Order_Item_Product) {
        continue;
      }
      $productId = $item->get_product_id();
      if (!$productId || isset($products[$productId])) {
        continue;
      }
      $product = wc_get_product($productId);
      if ($product instanceof \WC_Product) {
        $products[$productId] = $product;
      }
    }
    return $products;
  }

  /**
   * @param array<int, \WC_Product> $purchasedProducts
   * @return int[]
   */
  private function getCrossSellIds(array $purchasedProducts): array {
    $ids = [];
    foreach ($purchasedProducts as $product) {
      foreach ($product->get_cross_sell_ids() as $crossSellId) {
        $ids[] = (int)$crossSellId;
      }
    }
    return $ids;
  }

  /**
   * Products sharing taxonomy terms with the purchased products.
   *
   * @param array<int, \WC_Product> $purchasedProducts
   * @param string $termQueryArg WC_Product_Query taxonomy argument ('product_tag_id' or 'product_category_id').
   * @return int[]
   */
  private function getSameTermProductIds(array $purchasedProducts, string $termQueryArg): array {
    $termIds = [];
    foreach ($purchasedProducts as $product) {
      $productTermIds = $termQueryArg === 'product_tag_id' ? $product->get_tag_ids() : $product->get_category_ids();
      foreach ($productTermIds as $termId) {
        $termIds[] = (int)$termId;
      }
    }
    $termIds = array_values(array_unique($termIds));
    if (!$termIds) {
      return [];
    }

    // Purchased products are not excluded here (the exclude/post__not_in query
    // parameter scales poorly); filterDisplayable() removes them afterwards.
    $ids = wc_get_products([
      'status' => 'publish',
      'limit' => self::MAX_PRODUCTS + count($purchasedProducts),
      'orderby' => 'date',
      'order' => 'DESC',
      $termQueryArg => $termIds,
      'return' => 'ids',
    ]);
    return is_array($ids) ? array_map('intval', $ids) : [];
  }

  /**
   * @param int[] $purchasedIds
   * @return int[]
   */
  private function getRelatedIds(array $purchasedIds): array {
    $ids = [];
    foreach ($purchasedIds as $purchasedId) {
      foreach (wc_get_related_products($purchasedId, self::RELATED_PRODUCTS_PER_ITEM) as $relatedId) {
        $ids[] = (int)$relatedId;
      }
    }
    return $ids;
  }

  /**
   * Deduplicates, removes purchased products, and keeps only products a
   * customer can still see and buy.
   *
   * @param int[] $ids
   * @param int[] $excludeIds
   * @return int[]
   */
  private function filterDisplayable(array $ids, array $excludeIds): array {
    $ids = array_values(array_unique(array_map('intval', $ids)));
    $ids = array_values(array_diff($ids, $excludeIds));

    return array_values(array_filter($ids, function (int $id): bool {
      $product = wc_get_product($id);
      return $product instanceof \WC_Product
        && $product->get_status() === 'publish'
        && $product->get_stock_status() !== 'outofstock';
    }));
  }
}
