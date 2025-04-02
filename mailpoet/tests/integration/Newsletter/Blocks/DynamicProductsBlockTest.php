<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\WP\Functions as WPFunctions;

/**
 * @group woo
 */
class DynamicProductsBlockTest extends \MailPoetTest {
  /** @var DynamicProductsBlock */
  private $block;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var WPFunctions */
  private $wp;

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
    'titleFormat' => 'h2',
    'titleAlignment' => 'left',
    'titleIsLink' => false,
    'imageFullWidth' => true,
    'titlePosition' => 'abovePost',
    'featuredImagePosition' => 'left',
    'showAuthor' => 'no',
    'authorPrecededBy' => 'Author:',
    'showCategories' => 'no',
    'categoriesPrecededBy' => 'Categories:',
    'readMoreType' => 'button',
    'readMoreText' => 'Read more',
    'readMoreButton' => [
      'type' => 'button',
      'text' => 'Read more',
      'url' => '[postLink]',
      'styles' => [
        'block' => [
          'backgroundColor' => '#e2973f',
          'borderColor' => '#e2973f',
          'borderWidth' => '0px',
          'borderRadius' => '5px',
          'borderStyle' => 'solid',
          'width' => '110px',
          'lineHeight' => '40px',
          'fontColor' => '#ffffff',
          'fontFamily' => 'Arial',
          'fontSize' => '14px',
          'fontWeight' => 'bold',
          'textAlign' => 'left',
        ],
      ],
      'context' => 'dynamicProducts.readMoreButton',
    ],
    'sortBy' => 'newest',
    'showDivider' => false,
    'divider' => [
      'type' => 'divider',
      'styles' => [
        'block' => [
          'backgroundColor' => 'transparent',
          'padding' => '13px',
          'borderStyle' => 'solid',
          'borderWidth' => '3px',
          'borderColor' => '#aaaaaa',
        ],
      ],
      'context' => 'dynamicProducts.divider',
    ],
    'backgroundColor' => '#ffffff',
    'backgroundColorAlternate' => '#eeeeee',
  ];

  public function _before() {
    parent::_before();
    $this->block = $this->diContainer->get(DynamicProductsBlock::class);
    $this->wp = $this->diContainer->get(WPFunctions::class);
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
      'name' => 'PRODUCT 1',
      'date_created' => date('Y-m-d H:i:s', strtotime('-3 days')),
      'price' => '10.00',
    ])->get_id();
    
    $this->productIds[] = $this->tester->createWooCommerceProduct([
      'name' => 'PRODUCT 2',
      'date_created' => date('Y-m-d H:i:s', strtotime('-2 days')),
      'price' => '10.00',
    ])->get_id();
    
    $this->productIds[] = $this->tester->createWooCommerceProduct([
      'name' => 'PRODUCT 3',
      'date_created' => date('Y-m-d H:i:s', strtotime('-1 day')),
      'price' => '10.00',
    ])->get_id();
    
    $this->productIds[] = $this->tester->createWooCommerceProduct([
      'name' => 'PRODUCT 4',
      'date_created' => date('Y-m-d H:i:s'),
      'price' => '10.00',
    ])->get_id();
  }

  public function _after() {
    parent::_after();
    
    // Clean up any remaining products manually created outside the tester
    foreach ($this->productIds as $productId) {
      $this->wp->wpDeletePost($productId);
    }
    
    $this->productIds = [];
  }

  public function testItRendersLatestProductsInDP() {
    $automation = $this->createNewsletter('Automation', NewsletterEntity::TYPE_AUTOMATION);
    $result = $this->block->render($automation, $this->dpBlock);
    $encodedResult = json_encode($result);
    verify($encodedResult)->stringContainsString('PRODUCT 4');
    verify($encodedResult)->stringContainsString('PRODUCT 3');
    verify($encodedResult)->stringNotContainsString('PRODUCT 2');
    verify($encodedResult)->stringNotContainsString('PRODUCT 1');
  }

  public function testItRendersProductOnlyOncePerEmail() {
    $automation = $this->createNewsletter('Automation', NewsletterEntity::TYPE_AUTOMATION);
    $result = $this->block->render($automation, $this->dpBlock);
    $encodedResult = json_encode($result);
    verify($encodedResult)->stringContainsString('PRODUCT 4');
    verify($encodedResult)->stringContainsString('PRODUCT 3');
    verify($encodedResult)->stringNotContainsString('PRODUCT 2');
    verify($encodedResult)->stringNotContainsString('PRODUCT 1');
    $result = $this->block->render($automation, $this->dpBlock);
    $encodedResult = json_encode($result);
    verify($encodedResult)->stringNotContainsString('PRODUCT 4');
    verify($encodedResult)->stringNotContainsString('PRODUCT 3');
    verify($encodedResult)->stringContainsString('PRODUCT 2');
    verify($encodedResult)->stringContainsString('PRODUCT 1');
  }

  public function testItCanRenderSameProductsForDifferentAutomations() {
    $automation1 = $this->createNewsletter('Automation 1', NewsletterEntity::TYPE_AUTOMATION);
    $result = $this->block->render($automation1, $this->dpBlock);
    $encodedResult = json_encode($result);
    verify($encodedResult)->stringContainsString('PRODUCT 4');
    verify($encodedResult)->stringContainsString('PRODUCT 3');
    verify($encodedResult)->stringNotContainsString('PRODUCT 2');
    verify($encodedResult)->stringNotContainsString('PRODUCT 1');
    $automation2 = $this->createNewsletter('Automation 2', NewsletterEntity::TYPE_AUTOMATION);
    $result = $this->block->render($automation2, $this->dpBlock);
    $encodedResult = json_encode($result);
    verify($encodedResult)->stringContainsString('PRODUCT 4');
    verify($encodedResult)->stringContainsString('PRODUCT 3');
    verify($encodedResult)->stringNotContainsString('PRODUCT 2');
    verify($encodedResult)->stringNotContainsString('PRODUCT 1');
  }

  public function testItRendersOrderProducts() {
    $automation = $this->createNewsletter('Automation', NewsletterEntity::TYPE_AUTOMATION);
    $sendingQueue = new \MailPoet\Entities\SendingQueueEntity();
    $sendingQueue->setMeta([
      DynamicProductsBlock::ORDER_PRODUCTS_META_NAME => [$this->productIds[0], $this->productIds[1]],
    ]);

    $result = $this->block->render($automation, $this->dpBlock, false, $sendingQueue);
    $encodedResult = json_encode($result);
    verify($encodedResult)->stringContainsString('PRODUCT 1');
    verify($encodedResult)->stringContainsString('PRODUCT 2');
    verify($encodedResult)->stringNotContainsString('PRODUCT 3');
    verify($encodedResult)->stringNotContainsString('PRODUCT 4');
  }

  public function testItRendersOrderCrossSellProducts() {
    $automation = $this->createNewsletter('Automation', NewsletterEntity::TYPE_AUTOMATION);
    $sendingQueue = new \MailPoet\Entities\SendingQueueEntity();
    $sendingQueue->setMeta([
      DynamicProductsBlock::ORDER_CROSS_SELL_PRODUCTS_META_NAME => [$this->productIds[2], $this->productIds[3]],
    ]);

    $result = $this->block->render($automation, $this->dpBlock, false, $sendingQueue);
    $encodedResult = json_encode($result);
    verify($encodedResult)->stringContainsString('PRODUCT 3');
    verify($encodedResult)->stringContainsString('PRODUCT 4');
    verify($encodedResult)->stringNotContainsString('PRODUCT 1');
    verify($encodedResult)->stringNotContainsString('PRODUCT 2');
  }

  public function testItRendersOrderCrossSellProductsWhenShowCrossSellsIsTrue() {
    $automation = $this->createNewsletter('Automation', NewsletterEntity::TYPE_AUTOMATION);
    $sendingQueue = new \MailPoet\Entities\SendingQueueEntity();
    $sendingQueue->setMeta([
      DynamicProductsBlock::ORDER_PRODUCTS_META_NAME => [$this->productIds[0]],
      DynamicProductsBlock::ORDER_CROSS_SELL_PRODUCTS_META_NAME => [$this->productIds[1]],
    ]);

    $block = array_merge($this->dpBlock, ['showCrossSells' => true]);
    $result = $this->block->render($automation, $block, false, $sendingQueue);
    $encodedResult = json_encode($result);
    verify($encodedResult)->stringNotContainsString('PRODUCT 1');
    verify($encodedResult)->stringContainsString('PRODUCT 2');
  }

  public function testItRendersOrderProductsWhenShowCrossSellsIsFalse() {
    $automation = $this->createNewsletter('Automation', NewsletterEntity::TYPE_AUTOMATION);
    $sendingQueue = new \MailPoet\Entities\SendingQueueEntity();
    $sendingQueue->setMeta([
      DynamicProductsBlock::ORDER_PRODUCTS_META_NAME => [$this->productIds[0]],
      DynamicProductsBlock::ORDER_CROSS_SELL_PRODUCTS_META_NAME => [$this->productIds[1]],
    ]);

    $block = array_merge($this->dpBlock, ['showCrossSells' => false]);
    $result = $this->block->render($automation, $block, false, $sendingQueue);
    $encodedResult = json_encode($result);
    verify($encodedResult)->stringContainsString('PRODUCT 1');
    verify($encodedResult)->stringNotContainsString('PRODUCT 2');
  }

  public function testItRendersAbandonedCartProducts() {
    $automation = $this->createNewsletter('Automation', NewsletterEntity::TYPE_AUTOMATION);
    $sendingQueue = new \MailPoet\Entities\SendingQueueEntity();
    $sendingQueue->setMeta([
      \MailPoet\AutomaticEmails\WooCommerce\Events\AbandonedCart::TASK_META_NAME => [$this->productIds[0], $this->productIds[1]],
    ]);

    $result = $this->block->render($automation, $this->dpBlock, false, $sendingQueue);
    $encodedResult = json_encode($result);
    verify($encodedResult)->stringContainsString('PRODUCT 1');
    verify($encodedResult)->stringContainsString('PRODUCT 2');
    verify($encodedResult)->stringNotContainsString('PRODUCT 3');
    verify($encodedResult)->stringNotContainsString('PRODUCT 4');
  }

  private function createNewsletter($subject, $type, $parent = null) {
    $newsletter = new NewsletterEntity();
    $newsletter->setSubject($subject);
    $newsletter->setType($type);
    $newsletter->setParent($parent);
    $this->newslettersRepository->persist($newsletter);
    $this->newslettersRepository->flush();
    return $newsletter;
  }
}
