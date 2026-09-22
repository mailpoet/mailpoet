<?php declare(strict_types = 1);

namespace MailPoet\Test\EmailEditor\Integrations\MailPoet\ProductCollection;

use Automattic\WooCommerce\Blocks\BlockTypesController as WooCommerceBlockTypesController;
use MailPoet\EmailEditor\Integrations\MailPoet\ProductCollection\ProductCollectionEmailRendererRegistrar;

/**
 * @group woo
 */
class ProductCollectionEmailRendererRegistrarTest extends \MailPoetTest {
  /** @var ProductCollectionEmailRendererRegistrar */
  private $registrar;

  /** @var \WP_Block_Type[] */
  private $originalWooCommerceBlocks = [];

  public function _before(): void {
    parent::_before();
    $this->registrar = $this->diContainer->get(ProductCollectionEmailRendererRegistrar::class);
    $this->originalWooCommerceBlocks = $this->getRegisteredWooCommerceBlocks();
  }

  public function _after(): void {
    $registry = \WP_Block_Type_Registry::get_instance();
    foreach (array_keys($this->getRegisteredWooCommerceBlocks()) as $blockName) {
      $registry->unregister($blockName);
    }
    foreach ($this->originalWooCommerceBlocks as $blockType) {
      $registry->register($blockType);
    }
    if ($this->canSkipWooCommerceBlockRegistration()) {
      $this->setRegisterBlocksHasRun(true);
    }
    parent::_after();
  }

  public function testItRegistersWooCommerceBlocksSkippedOnCronRequests(): void {
    if (!$this->canSkipWooCommerceBlockRegistration()) {
      $this->markTestSkipped('WooCommerce before 11.1 always registers its blocks.');
    }
    $this->simulateSkippedWooCommerceBlockRegistration();

    $this->registrar->registerEmailRenderers();

    $blockType = \WP_Block_Type_Registry::get_instance()->get_registered('woocommerce/product-collection');
    $this->assertInstanceOf(\WP_Block_Type::class, $blockType);
    $this->assertNotEmpty(get_object_vars($blockType)['render_email_callback'] ?? null);
  }

  public function testItDoesNotRegisterWooCommerceBlocksAgainWhenAlreadyRegistered(): void {
    $doingItWrongCount = did_action('doing_it_wrong_run');

    $this->registrar->registerEmailRenderers();

    $this->assertSame($doingItWrongCount, did_action('doing_it_wrong_run'));
    $this->assertSame(array_keys($this->originalWooCommerceBlocks), array_keys($this->getRegisteredWooCommerceBlocks()));
  }

  /**
   * Recreates the state of a cron request on WooCommerce 11.1+: no WooCommerce
   * block is registered and the block types controller has not run.
   */
  private function simulateSkippedWooCommerceBlockRegistration(): void {
    $registry = \WP_Block_Type_Registry::get_instance();
    foreach (array_keys($this->originalWooCommerceBlocks) as $blockName) {
      $registry->unregister($blockName);
    }
    $this->setRegisterBlocksHasRun(false);
  }

  private function canSkipWooCommerceBlockRegistration(): bool {
    return property_exists(WooCommerceBlockTypesController::class, 'register_blocks_has_run');
  }

  private function setRegisterBlocksHasRun(bool $hasRun): void {
    $property = new \ReflectionProperty(WooCommerceBlockTypesController::class, 'register_blocks_has_run');
    $property->setAccessible(true);
    $property->setValue(null, $hasRun);
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
