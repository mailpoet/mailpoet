<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\AutomaticEmails\WooCommerce\Events\AbandonedCart;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\NewslettersRepository;

/**
 * @group woo
 */
class DynamicProductsBlockTest extends \MailPoetTest {
  /** @var DynamicProductsBlock */
  private $block;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var array */
  private $productIds = [];

  private $dpBlock = [
    'type' => 'dynamicProducts',
    'withLayout' => true,
    'amount' => '2',
    'contentType' => 'product',
    'terms' => [],
    'inclusionType' => 'include',
    'displayType' => 'excerpt',
    'titleFormat' => 'h1',
    'titleAlignment' => 'left',
    'titleIsLink' => false,
    'imageFullWidth' => false,
    'titlePosition' => 'abovePost',
    'featuredImagePosition' => 'left',
    'pricePosition' => 'below',
    'readMoreType' => 'link',
    'readMoreText' => 'Buy now',
    'excludeOutOfStock' => false,
    'readMoreButton' => [
      'type' => 'button',
      'text' => 'Buy now',
      'url' => '[postLink]',
      'context' => 'dynamicProducts.readMoreButton',
      'styles' => [
        'block' => [
          'backgroundColor' => '#2ea1cd',
          'borderColor' => '#0074a2',
          'borderWidth' => '1px',
          'borderRadius' => '5px',
          'borderStyle' => 'solid',
          'width' => '180px',
          'lineHeight' => '40px',
          'fontColor' => '#ffffff',
          'fontFamily' => 'Verdana',
          'fontSize' => '18px',
          'fontWeight' => 'normal',
          'textAlign' => 'center',
        ],
      ],
    ],
    'sortBy' => 'newest',
    'showDivider' => true,
    'dynamicProductsType' => 'selected',
    'divider' => [
      'type' => 'divider',
      'context' => 'dynamicProducts.divider',
      'styles' => [
        'block' => [
          'backgroundColor' => 'transparent',
          'padding' => '13px',
          'borderStyle' => 'solid',
          'borderWidth' => '3px',
          'borderColor' => '#aaaaaa',
        ],
      ],
    ],
    'backgroundColor' => '#ffffff',
    'backgroundColorAlternate' => '#eeeeee',
  ];

  public function _before() {
    parent::_before();
    $this->block = $this->diContainer->get(DynamicProductsBlock::class);
    $this->newslettersRepository = $this->diContainer->get(NewslettersRepository::class);

    // Clear old products
    $products = wc_get_products(['limit' => -1]);
    if (is_array($products)) {
      foreach ($products as $product) {
        $product->delete(true);
      }
    }

    // Create test products using the tester
    $this->productIds = [];
    $this->productIds[] = $this->tester->createWooCommerceProduct([
      'name' => 'DPB Product 1',
      'date_created' => date('Y-m-d H:i:s', strtotime('-1 days')),
      'price' => '10.00',
    ])->get_id();

    $this->productIds[] = $this->tester->createWooCommerceProduct([
      'name' => 'DPB Product 2',
      'date_created' => date('Y-m-d H:i:s', strtotime('-2 days')),
      'price' => '20.00',
    ])->get_id();

    $this->productIds[] = $this->tester->createWooCommerceProduct([
      'name' => 'DPB Product 3',
      'date_created' => date('Y-m-d H:i:s', strtotime('-3 day')),
      'price' => '30.00',
    ])->get_id();

    $this->productIds[] = $this->tester->createWooCommerceProduct([
      'name' => 'DPB Product 4',
      'date_created' => date('Y-m-d H:i:s', strtotime('-4 days')),
      'price' => '40.00',
    ])->get_id();

    $this->productIds[] = $this->tester->createWooCommerceProduct([
      'name' => 'DPB Product 5',
      'date_created' => date('Y-m-d H:i:s', strtotime('-5 days')),
      'price' => '50.00',
    ])->get_id();

    $this->productIds[] = $this->tester->createWooCommerceProduct([
      'name' => 'DPB Product 6',
      'date_created' => date('Y-m-d H:i:s', strtotime('-6 days')),
      'price' => '60.00',
    ])->get_id();
  }

  public function testItRendersLatestProductsInDP() {
    $automation = $this->createNewsletter('Automation', NewsletterEntity::TYPE_AUTOMATION);
    $result = $this->block->render($automation, $this->dpBlock);
    $encodedResult = json_encode($result);
    verify($encodedResult)->stringContainsString('DPB Product 1');
    verify($encodedResult)->stringContainsString('DPB Product 2');
    verify($encodedResult)->stringNotContainsString('DPB Product 3');
    verify($encodedResult)->stringNotContainsString('DPB Product 4');
    verify($encodedResult)->stringNotContainsString('DPB Product 5');
    verify($encodedResult)->stringNotContainsString('DPB Product 6');
  }

  public function testItRendersProductOnlyOncePerEmail() {
    $automation = $this->createNewsletter('Automation', NewsletterEntity::TYPE_AUTOMATION);
    $result = $this->block->render($automation, $this->dpBlock);
    $encodedResult = json_encode($result);
    verify($encodedResult)->stringContainsString('DPB Product 1');
    verify($encodedResult)->stringContainsString('DPB Product 2');
    verify($encodedResult)->stringNotContainsString('DPB Product 3');
    verify($encodedResult)->stringNotContainsString('DPB Product 4');
    verify($encodedResult)->stringNotContainsString('DPB Product 5');
    verify($encodedResult)->stringNotContainsString('DPB Product 6');
    $result = $this->block->render($automation, $this->dpBlock);
    $encodedResult = json_encode($result);
    verify($encodedResult)->stringNotContainsString('DPB Product 1');
    verify($encodedResult)->stringNotContainsString('DPB Product 2');
    verify($encodedResult)->stringContainsString('DPB Product 3');
    verify($encodedResult)->stringContainsString('DPB Product 4');
    verify($encodedResult)->stringNotContainsString('DPB Product 5');
    verify($encodedResult)->stringNotContainsString('DPB Product 6');
  }

  public function testItCanRenderSameProductsForDifferentAutomations() {
    $automation1 = $this->createNewsletter('Automation 1', NewsletterEntity::TYPE_AUTOMATION);
    $result = $this->block->render($automation1, $this->dpBlock);
    $encodedResult = json_encode($result);
    verify($encodedResult)->stringContainsString('DPB Product 1');
    verify($encodedResult)->stringContainsString('DPB Product 2');
    verify($encodedResult)->stringNotContainsString('DPB Product 3');
    verify($encodedResult)->stringNotContainsString('DPB Product 4');
    verify($encodedResult)->stringNotContainsString('DPB Product 5');
    verify($encodedResult)->stringNotContainsString('DPB Product 6');
    $automation2 = $this->createNewsletter('Automation 2', NewsletterEntity::TYPE_AUTOMATION);
    $result = $this->block->render($automation2, $this->dpBlock);
    $encodedResult = json_encode($result);
    verify($encodedResult)->stringContainsString('DPB Product 1');
    verify($encodedResult)->stringContainsString('DPB Product 2');
    verify($encodedResult)->stringNotContainsString('DPB Product 3');
    verify($encodedResult)->stringNotContainsString('DPB Product 4');
    verify($encodedResult)->stringNotContainsString('DPB Product 5');
    verify($encodedResult)->stringNotContainsString('DPB Product 6');
  }

  public function testItRendersOrderProducts() {
    $automation = $this->createNewsletter('Automation', NewsletterEntity::TYPE_AUTOMATION);
    $sendingQueue = new \MailPoet\Entities\SendingQueueEntity();
    $sendingQueue->setMeta([
      DynamicProductsBlock::ORDER_PRODUCTS_META_NAME => [$this->productIds[0], $this->productIds[1]],
      DynamicProductsBlock::ORDER_CROSS_SELL_PRODUCTS_META_NAME => [$this->productIds[2], $this->productIds[3]],
      AbandonedCart::TASK_META_NAME => [$this->productIds[4], $this->productIds[5]],
    ]);

    $block = array_merge($this->dpBlock, ['dynamicProductsType' => 'order']);
    $result = $this->block->render($automation, $block, false, $sendingQueue);
    $encodedResult = json_encode($result);
    verify($encodedResult)->stringContainsString('DPB Product 1');
    verify($encodedResult)->stringContainsString('DPB Product 2');
    verify($encodedResult)->stringNotContainsString('DPB Product 3');
    verify($encodedResult)->stringNotContainsString('DPB Product 4');
    verify($encodedResult)->stringNotContainsString('DPB Product 5');
    verify($encodedResult)->stringNotContainsString('DPB Product 6');
  }

  public function testItRendersOrderCrossSellProducts() {
    $automation = $this->createNewsletter('Automation', NewsletterEntity::TYPE_AUTOMATION);
    $sendingQueue = new \MailPoet\Entities\SendingQueueEntity();
    $sendingQueue->setMeta([
      DynamicProductsBlock::ORDER_PRODUCTS_META_NAME => [$this->productIds[0], $this->productIds[1]],
      DynamicProductsBlock::ORDER_CROSS_SELL_PRODUCTS_META_NAME => [$this->productIds[2], $this->productIds[3]],
      AbandonedCart::TASK_META_NAME => [$this->productIds[4], $this->productIds[5]],
    ]);

    $block = array_merge($this->dpBlock, ['dynamicProductsType' => 'cross-sell']);
    $result = $this->block->render($automation, $block, false, $sendingQueue);
    $encodedResult = json_encode($result);
    verify($encodedResult)->stringNotContainsString('DPB Product 1');
    verify($encodedResult)->stringNotContainsString('DPB Product 2');
    verify($encodedResult)->stringContainsString('DPB Product 3');
    verify($encodedResult)->stringContainsString('DPB Product 4');
    verify($encodedResult)->stringNotContainsString('DPB Product 5');
    verify($encodedResult)->stringNotContainsString('DPB Product 6');
  }

  public function testItRendersAbandonedCartProducts() {
    $automation = $this->createNewsletter('Automation', NewsletterEntity::TYPE_AUTOMATION);
    $sendingQueue = new \MailPoet\Entities\SendingQueueEntity();
    $sendingQueue->setMeta([
      DynamicProductsBlock::ORDER_PRODUCTS_META_NAME => [$this->productIds[0], $this->productIds[1]],
      DynamicProductsBlock::ORDER_CROSS_SELL_PRODUCTS_META_NAME => [$this->productIds[2], $this->productIds[3]],
      AbandonedCart::TASK_META_NAME => [$this->productIds[4], $this->productIds[5]],
    ]);

    $block = array_merge($this->dpBlock, ['dynamicProductsType' => 'cart']);
    $result = $this->block->render($automation, $block, false, $sendingQueue);
    $encodedResult = json_encode($result);
    verify($encodedResult)->stringNotContainsString('DPB Product 1');
    verify($encodedResult)->stringNotContainsString('DPB Product 2');
    verify($encodedResult)->stringNotContainsString('DPB Product 3');
    verify($encodedResult)->stringNotContainsString('DPB Product 4');
    verify($encodedResult)->stringContainsString('DPB Product 5');
    verify($encodedResult)->stringContainsString('DPB Product 6');
  }

  public function testItIncludesAllProductsWhenExcludeOutOfStockIsFalse(): void {
    // Create products with different stock statuses
    $this->createStockTestProducts();

    $automation = $this->createNewsletter('Automation', NewsletterEntity::TYPE_AUTOMATION);
    $block = array_merge($this->dpBlock, [
      'excludeOutOfStock' => false,
      'amount' => '10',
      'dynamicProductsType' => 'selected',
    ]);

    $result = $this->block->render($automation, $block);
    $encodedResult = json_encode($result);

    // All products should be included when excludeOutOfStock is false
    verify($encodedResult)->stringContainsString('Simple Product No Stock Management');
    verify($encodedResult)->stringContainsString('Simple Product Out Of Stock');
    verify($encodedResult)->stringContainsString('Simple Product On Backorder');
    verify($encodedResult)->stringContainsString('Simple Product In Stock');
  }

  public function testItExcludesOutOfStockProductsWhenExcludeOutOfStockIsTrue(): void {
    // Create products with different stock statuses
    $this->createStockTestProducts();

    $automation = $this->createNewsletter('Automation', NewsletterEntity::TYPE_AUTOMATION);
    $block = array_merge($this->dpBlock, [
      'excludeOutOfStock' => true,
      'amount' => '10',
      'dynamicProductsType' => 'selected',
    ]);

    $result = $this->block->render($automation, $block);
    $encodedResult = json_encode($result);

    // Products in stock and on backorder should be included
    verify($encodedResult)->stringContainsString('Simple Product No Stock Management');
    verify($encodedResult)->stringContainsString('Simple Product On Backorder');
    verify($encodedResult)->stringContainsString('Simple Product In Stock');

    // Out of stock products should be excluded
    verify($encodedResult)->stringNotContainsString('Simple Product Out Of Stock');
  }

  public function testItIncludesVariableProductWithAllVariationsInStock(): void {
    // Create a variable product where all variations are in stock
    $this->createVariableProductAllInStock();

    $automation = $this->createNewsletter('Automation', NewsletterEntity::TYPE_AUTOMATION);

    // Test with excludeOutOfStock true - should include variable product since all variations are in stock
    $block = array_merge($this->dpBlock, [
      'excludeOutOfStock' => true,
      'amount' => '10',
      'dynamicProductsType' => 'selected',
    ]);

    $result = $this->block->render($automation, $block);
    $encodedResult = json_encode($result);
    verify($encodedResult)->stringContainsString('Variable Product All In Stock');
  }

  public function testItExcludesVariableProductWithAllVariationsOutOfStock(): void {
    // Create a variable product where all variations are out of stock
    $this->createVariableProductAllOutOfStock();

    $automation = $this->createNewsletter('Automation', NewsletterEntity::TYPE_AUTOMATION);

    // Test with excludeOutOfStock false - should include variable product
    $blockInclude = array_merge($this->dpBlock, [
      'excludeOutOfStock' => false,
      'amount' => '10',
      'dynamicProductsType' => 'selected',
    ]);

    $result = $this->block->render($automation, $blockInclude);
    $encodedResult = json_encode($result);
    verify($encodedResult)->stringContainsString('Variable Product All Out Of Stock');

    // Test with excludeOutOfStock true - should exclude variable product since all variations are out of stock
    $blockExclude = array_merge($this->dpBlock, [
      'excludeOutOfStock' => true,
      'amount' => '10',
      'dynamicProductsType' => 'selected',
    ]);

    $result = $this->block->render($automation, $blockExclude);
    $encodedResult = json_encode($result);
    verify($encodedResult)->stringNotContainsString('Variable Product All Out Of Stock');
  }

  private function createNewsletter($subject, $type, $parent = null): NewsletterEntity {
    $newsletter = new NewsletterEntity();
    $newsletter->setSubject($subject);
    $newsletter->setType($type);
    $newsletter->setParent($parent);
    $this->newslettersRepository->persist($newsletter);
    $this->newslettersRepository->flush();
    return $newsletter;
  }

  private function clearTestProducts(): void {
    $products = wc_get_products(['limit' => -1]);
    if (is_array($products)) {
      foreach ($products as $product) {
        $product->delete(true);
      }
    }
  }

  private function createStockTestProducts(): array {
    $products = [];

    // 1. Simple product without managed stock (default stock status is 'instock')
    $products['no_stock_management'] = $this->tester->createWooCommerceProduct([
      'name' => 'Simple Product No Stock Management',
      'price' => '10.00',
    ]);
    $products['no_stock_management']->set_manage_stock(false);
    $products['no_stock_management']->set_stock_status('instock');
    $products['no_stock_management']->save();

    // 2. Simple product out of stock
    $products['out_of_stock'] = $this->tester->createWooCommerceProduct([
      'name' => 'Simple Product Out Of Stock',
      'price' => '20.00',
    ]);
    $products['out_of_stock']->set_manage_stock(true);
    $products['out_of_stock']->set_stock_quantity(0);
    $products['out_of_stock']->set_stock_status('outofstock');
    $products['out_of_stock']->save();

    // 3. Simple product on backorder
    $products['on_backorder'] = $this->tester->createWooCommerceProduct([
      'name' => 'Simple Product On Backorder',
      'price' => '30.00',
    ]);
    $products['on_backorder']->set_manage_stock(true);
    $products['on_backorder']->set_stock_quantity(0);
    $products['on_backorder']->set_backorders('yes');
    $products['on_backorder']->set_stock_status('onbackorder');
    $products['on_backorder']->save();

    // 4. Simple product with something in stock
    $products['in_stock'] = $this->tester->createWooCommerceProduct([
      'name' => 'Simple Product In Stock',
      'price' => '40.00',
    ]);
    $products['in_stock']->set_manage_stock(true);
    $products['in_stock']->set_stock_quantity(10);
    $products['in_stock']->set_stock_status('instock');
    $products['in_stock']->save();

    return $products;
  }

  private function createVariableProductAllInStock(): \WC_Product_Variable {
    // Create variable product
    $variableProduct = new \WC_Product_Variable();
    $variableProduct->set_name('Variable Product All In Stock');
    $variableProduct->set_status('publish');
    $variableProduct->set_manage_stock(false);
    $variableProduct->set_stock_status('instock');

    // Create size attribute
    $attribute = new \WC_Product_Attribute();
    $attribute->set_id(0);
    $attribute->set_name('Size');
    $attribute->set_options(['Small', 'Large']);
    $attribute->set_visible(true);
    $attribute->set_variation(true);
    $variableProduct->set_attributes([$attribute]);
    $variableProduct->save();

    // Create variations - all in stock
    // Variation 1: Small - In Stock
    $variation1 = new \WC_Product_Variation();
    $variation1->set_parent_id($variableProduct->get_id());
    $variation1->set_attributes(['Size' => 'Small']);
    $variation1->set_manage_stock(true);
    $variation1->set_stock_quantity(10);
    $variation1->set_stock_status('instock');
    $variation1->set_regular_price('60.00');
    $variation1->set_status('publish');
    $variation1->save();

    // Update variable product stock status based on variations
    \WC_Product_Variable::sync_stock_status($variableProduct->get_id());

    // Reload to get the synced status
    $variableProduct = wc_get_product($variableProduct->get_id());
    $this->assertInstanceOf(\WC_Product_Variable::class, $variableProduct);
    return $variableProduct;
  }

  private function createVariableProductAllOutOfStock(): \WC_Product_Variable {
    // Create variable product
    $variableProduct = new \WC_Product_Variable();
    $variableProduct->set_name('Variable Product All Out Of Stock');
    $variableProduct->set_status('publish');
    $variableProduct->set_manage_stock(false);
    $variableProduct->set_stock_status('instock');

    // Create color attribute
    $attribute = new \WC_Product_Attribute();
    $attribute->set_id(0);
    $attribute->set_name('Color');
    $attribute->set_options(['Red', 'Blue']);
    $attribute->set_visible(true);
    $attribute->set_variation(true);
    $variableProduct->set_attributes([$attribute]);
    $variableProduct->save();

    // Create variations - all out of stock
    // Variation 1: Red - Out of Stock
    $variation1 = new \WC_Product_Variation();
    $variation1->set_parent_id($variableProduct->get_id());
    $variation1->set_attributes(['Color' => 'Red']);
    $variation1->set_manage_stock(true);
    $variation1->set_stock_quantity(0);
    $variation1->set_stock_status('outofstock');
    $variation1->set_regular_price('70.00');
    $variation1->set_status('publish');
    $variation1->save();

    // Update variable product stock status based on variations
    \WC_Product_Variable::sync_stock_status($variableProduct->get_id());

    // Reload to get the synced status
    $variableProduct = wc_get_product($variableProduct->get_id());
    $this->assertInstanceOf(\WC_Product_Variable::class, $variableProduct);
    return $variableProduct;
  }

  public function _after(): void {
    $this->clearTestProducts();
  }
}
