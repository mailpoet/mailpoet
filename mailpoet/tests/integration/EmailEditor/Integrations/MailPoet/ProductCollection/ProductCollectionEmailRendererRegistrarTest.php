<?php declare(strict_types = 1);

namespace MailPoet\Test\EmailEditor\Integrations\MailPoet\ProductCollection;

use Automattic\WooCommerce\Blocks\BlockTypesController as WooCommerceBlockTypesController;
use MailPoet\EmailEditor\Integrations\MailPoet\ProductCollection\ProductCollectionEmailRendererRegistrar;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Newsletter\Renderer\Renderer;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;

/**
 * @group woo
 */
class ProductCollectionEmailRendererRegistrarTest extends \MailPoetTest {
  private const PRODUCT_NAME = 'Registrar Test Shirt';

  /** @var \WP_Block_Type[] */
  private $originalWooCommerceBlocks = [];

  /** @var bool|null */
  private $originalRegisterBlocksHasRun;

  /** @var int[] */
  private $postIds = [];

  private int $productId;

  public function _before(): void {
    parent::_before();
    $this->originalWooCommerceBlocks = $this->getRegisteredWooCommerceBlocks();
    $this->originalRegisterBlocksHasRun = $this->canSkipWooCommerceBlockRegistration()
      ? (bool)$this->getRegisterBlocksHasRunProperty()->getValue()
      : null;
    $this->productId = $this->tester->createWooCommerceProduct(['name' => self::PRODUCT_NAME, 'price' => '10'])->get_id();
  }

  public function _after(): void {
    $registry = \WP_Block_Type_Registry::get_instance();
    foreach (array_keys($this->getRegisteredWooCommerceBlocks()) as $blockName) {
      $registry->unregister($blockName);
    }
    foreach ($this->originalWooCommerceBlocks as $blockType) {
      $registry->register($blockType);
    }
    if ($this->originalRegisterBlocksHasRun !== null) {
      $this->getRegisterBlocksHasRunProperty()->setValue(null, $this->originalRegisterBlocksHasRun);
    }
    foreach ($this->postIds as $postId) {
      wp_delete_post($postId, true);
    }
    parent::_after();
  }

  public function testItRendersProductsWhenWooCommerceSkippedBlockRegistration(): void {
    // WooCommerce before 11.1 always registers its blocks, so there is no skipped state to recreate.
    if ($this->canSkipWooCommerceBlockRegistration()) {
      $this->simulateSkippedWooCommerceBlockRegistration();
    }

    $html = $this->render($this->createProductCollectionContent());

    $this->assertStringContainsString(self::PRODUCT_NAME, $html);
    $this->assertStringNotContainsString('data-block-name', $html);
  }

  public function testItDoesNotRegisterWooCommerceBlocksAgainWhenAlreadyRegistered(): void {
    $doingItWrongCount = did_action('doing_it_wrong_run');

    $this->render($this->createProductCollectionContent());

    $this->assertSame($doingItWrongCount, did_action('doing_it_wrong_run'));
    $this->assertSame(array_keys($this->originalWooCommerceBlocks), array_keys($this->getRegisteredWooCommerceBlocks()));
  }

  public function testItRendersWhenWooCommerceBlockRegistrationThrows(): void {
    $this->simulateSkippedWooCommerceBlockRegistration();
    $throwOnWooCommerceBlock = function (array $args, string $blockName): array {
      if (strpos($blockName, 'woocommerce/') === 0) {
        throw new \Error('Block registration failed');
      }
      return $args;
    };
    add_filter('register_block_type_args', $throwOnWooCommerceBlock, 10, 2);

    $html = $this->render($this->createProductCollectionContent());

    $this->assertStringContainsString('Product collection email', $html);
  }

  public function testItDoesNotRegisterWooCommerceBlocksForOtherEmails(): void {
    $this->simulateSkippedWooCommerceBlockRegistration();
    $originalPost = $GLOBALS['post'] ?? null;
    $postId = wp_insert_post(['post_type' => 'post', 'post_title' => 'WooCommerce email', 'post_status' => 'publish']);
    $this->assertIsInt($postId);
    $this->postIds[] = $postId;
    $GLOBALS['post'] = get_post($postId);

    try {
      $this->diContainer->get(ProductCollectionEmailRendererRegistrar::class)->registerEmailRenderers();
    } finally {
      $GLOBALS['post'] = $originalPost;
    }

    $this->assertFalse(\WP_Block_Type_Registry::get_instance()->is_registered('woocommerce/product-collection'));
  }

  private function render(string $postContent): string {
    $postId = wp_insert_post([
      'post_type' => 'mailpoet_email',
      'post_status' => 'publish',
      'post_title' => 'Product collection email',
      'post_content' => $postContent,
    ]);
    $this->assertIsInt($postId);
    $this->postIds[] = $postId;

    $newsletter = (new NewsletterFactory())
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->withStatus(NewsletterEntity::STATUS_ACTIVE)
      ->withSubject('Product collection email')
      ->withWpPostId($postId)
      ->withScheduledQueue(['count_processed' => 0, 'count_total' => 1])
      ->withSubscriber((new SubscriberFactory())->withEmail('product-collection@example.com')->create())
      ->create();
    $queue = $newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);

    return $this->diContainer->get(Renderer::class)->render($newsletter, $queue)['html'];
  }

  private function createProductCollectionContent(): string {
    $attributes = [
      'query' => [
        'perPage' => 10,
        'pages' => 1,
        'offset' => 0,
        'postType' => 'product',
        'order' => 'asc',
        'orderBy' => 'title',
        'search' => '',
        'exclude' => [],
        'inherit' => false,
        'taxQuery' => [],
        'isProductCollectionBlock' => true,
        'woocommerceOnSale' => false,
        'woocommerceStockStatus' => ['instock', 'outofstock', 'onbackorder'],
        'woocommerceAttributes' => [],
        'woocommerceHandPickedProducts' => [(string)$this->productId],
      ],
      'tagName' => 'div',
      'displayLayout' => ['type' => 'flex', 'columns' => 1],
      'collection' => 'woocommerce/product-collection/hand-picked',
    ];
    return '<!-- wp:woocommerce/product-collection ' . wp_json_encode($attributes) . ' -->'
      . '<div class="wp-block-woocommerce-product-collection"><!-- wp:woocommerce/product-template -->'
      . '<!-- wp:post-title {"isLink":true,"__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->'
      . '<!-- /wp:woocommerce/product-template --></div>'
      . '<!-- /wp:woocommerce/product-collection -->';
  }

  /**
   * Recreates the state of a cron request on WooCommerce 11.1+: no WooCommerce
   * block is registered and the block types controller has not run. On older
   * WooCommerce only the blocks are unregistered.
   */
  private function simulateSkippedWooCommerceBlockRegistration(): void {
    $registry = \WP_Block_Type_Registry::get_instance();
    foreach (array_keys($this->originalWooCommerceBlocks) as $blockName) {
      $registry->unregister($blockName);
    }
    if ($this->canSkipWooCommerceBlockRegistration()) {
      $this->getRegisterBlocksHasRunProperty()->setValue(null, false);
    }
  }

  private function canSkipWooCommerceBlockRegistration(): bool {
    return property_exists(WooCommerceBlockTypesController::class, 'register_blocks_has_run');
  }

  private function getRegisterBlocksHasRunProperty(): \ReflectionProperty {
    $property = new \ReflectionProperty(WooCommerceBlockTypesController::class, 'register_blocks_has_run');
    $property->setAccessible(true);
    return $property;
  }

  /**
   * @return \WP_Block_Type[]
   */
  private function getRegisteredWooCommerceBlocks(): array {
    return array_filter(
      \WP_Block_Type_Registry::get_instance()->get_all_registered(),
      function (string $blockName): bool {
        return strpos($blockName, 'woocommerce/') === 0;
      },
      ARRAY_FILTER_USE_KEY
    );
  }
}
