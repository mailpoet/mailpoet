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
      'name' => 'PRODUCT 1',
      'date_created' => date('Y-m-d H:i:s', strtotime('-1 days')),
      'price' => '10.00',
    ])->get_id();

    $this->productIds[] = $this->tester->createWooCommerceProduct([
      'name' => 'PRODUCT 2',
      'date_created' => date('Y-m-d H:i:s', strtotime('-2 days')),
      'price' => '20.00',
    ])->get_id();

    $this->productIds[] = $this->tester->createWooCommerceProduct([
      'name' => 'PRODUCT 3',
      'date_created' => date('Y-m-d H:i:s', strtotime('-3 day')),
      'price' => '30.00',
    ])->get_id();

    $this->productIds[] = $this->tester->createWooCommerceProduct([
      'name' => 'PRODUCT 4',
      'date_created' => date('Y-m-d H:i:s', strtotime('-4 days')),
      'price' => '40.00',
    ])->get_id();

    $this->productIds[] = $this->tester->createWooCommerceProduct([
      'name' => 'PRODUCT 5',
      'date_created' => date('Y-m-d H:i:s', strtotime('-5 days')),
      'price' => '50.00',
    ])->get_id();

    $this->productIds[] = $this->tester->createWooCommerceProduct([
      'name' => 'PRODUCT 6',
      'date_created' => date('Y-m-d H:i:s', strtotime('-6 days')),
      'price' => '60.00',
    ])->get_id();
  }

  public function testItRendersLatestProductsInDP() {
    $automation = $this->createNewsletter('Automation', NewsletterEntity::TYPE_AUTOMATION);
    $result = $this->block->render($automation, $this->dpBlock);
    $encodedResult = json_encode($result);
    verify($encodedResult)->stringContainsString('PRODUCT 1');
    verify($encodedResult)->stringContainsString('PRODUCT 2');
    verify($encodedResult)->stringNotContainsString('PRODUCT 3');
    verify($encodedResult)->stringNotContainsString('PRODUCT 4');
    verify($encodedResult)->stringNotContainsString('PRODUCT 5');
    verify($encodedResult)->stringNotContainsString('PRODUCT 6');
  }

  public function testItRendersProductOnlyOncePerEmail() {
    $automation = $this->createNewsletter('Automation', NewsletterEntity::TYPE_AUTOMATION);
    $result = $this->block->render($automation, $this->dpBlock);
    $encodedResult = json_encode($result);
    verify($encodedResult)->stringContainsString('PRODUCT 1');
    verify($encodedResult)->stringContainsString('PRODUCT 2');
    verify($encodedResult)->stringNotContainsString('PRODUCT 3');
    verify($encodedResult)->stringNotContainsString('PRODUCT 4');
    verify($encodedResult)->stringNotContainsString('PRODUCT 5');
    verify($encodedResult)->stringNotContainsString('PRODUCT 6');
    $result = $this->block->render($automation, $this->dpBlock);
    $encodedResult = json_encode($result);
    verify($encodedResult)->stringNotContainsString('PRODUCT 1');
    verify($encodedResult)->stringNotContainsString('PRODUCT 2');
    verify($encodedResult)->stringContainsString('PRODUCT 3');
    verify($encodedResult)->stringContainsString('PRODUCT 4');
    verify($encodedResult)->stringNotContainsString('PRODUCT 5');
    verify($encodedResult)->stringNotContainsString('PRODUCT 6');
  }

  public function testItCanRenderSameProductsForDifferentAutomations() {
    $automation1 = $this->createNewsletter('Automation 1', NewsletterEntity::TYPE_AUTOMATION);
    $result = $this->block->render($automation1, $this->dpBlock);
    $encodedResult = json_encode($result);
    verify($encodedResult)->stringContainsString('PRODUCT 1');
    verify($encodedResult)->stringContainsString('PRODUCT 2');
    verify($encodedResult)->stringNotContainsString('PRODUCT 3');
    verify($encodedResult)->stringNotContainsString('PRODUCT 4');
    verify($encodedResult)->stringNotContainsString('PRODUCT 5');
    verify($encodedResult)->stringNotContainsString('PRODUCT 6');
    $automation2 = $this->createNewsletter('Automation 2', NewsletterEntity::TYPE_AUTOMATION);
    $result = $this->block->render($automation2, $this->dpBlock);
    $encodedResult = json_encode($result);
    verify($encodedResult)->stringContainsString('PRODUCT 1');
    verify($encodedResult)->stringContainsString('PRODUCT 2');
    verify($encodedResult)->stringNotContainsString('PRODUCT 3');
    verify($encodedResult)->stringNotContainsString('PRODUCT 4');
    verify($encodedResult)->stringNotContainsString('PRODUCT 5');
    verify($encodedResult)->stringNotContainsString('PRODUCT 6');
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
    verify($encodedResult)->stringContainsString('PRODUCT 1');
    verify($encodedResult)->stringContainsString('PRODUCT 2');
    verify($encodedResult)->stringNotContainsString('PRODUCT 3');
    verify($encodedResult)->stringNotContainsString('PRODUCT 4');
    verify($encodedResult)->stringNotContainsString('PRODUCT 5');
    verify($encodedResult)->stringNotContainsString('PRODUCT 6');
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
    verify($encodedResult)->stringNotContainsString('PRODUCT 1');
    verify($encodedResult)->stringNotContainsString('PRODUCT 2');
    verify($encodedResult)->stringContainsString('PRODUCT 3');
    verify($encodedResult)->stringContainsString('PRODUCT 4');
    verify($encodedResult)->stringNotContainsString('PRODUCT 5');
    verify($encodedResult)->stringNotContainsString('PRODUCT 6');
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
    verify($encodedResult)->stringNotContainsString('PRODUCT 1');
    verify($encodedResult)->stringNotContainsString('PRODUCT 2');
    verify($encodedResult)->stringNotContainsString('PRODUCT 3');
    verify($encodedResult)->stringNotContainsString('PRODUCT 4');
    verify($encodedResult)->stringContainsString('PRODUCT 5');
    verify($encodedResult)->stringContainsString('PRODUCT 6');
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
