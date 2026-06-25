<?php declare(strict_types = 1);

namespace unit\EmailEditor\Integrations\MailPoet\ProductCollection;

use MailPoet\AutomaticEmails\WooCommerce\Events\AbandonedCart;
use MailPoet\EmailEditor\Integrations\MailPoet\ProductCollection\OrderProductCollectionProcessor;
use MailPoet\Entities\SendingQueueEntity;

class OrderProductCollectionProcessorTest extends \MailPoetUnitTest {
  /** @var OrderProductCollectionProcessor */
  private $processor;

  public function _before(): void {
    parent::_before();
    $this->processor = new OrderProductCollectionProcessor();
  }

  public function testCreateBlocksFilterReturnsNullWithoutAnOrder(): void {
    $this->assertNull($this->processor->createBlocksFilter([]));
    $this->assertNull($this->processor->createBlocksFilter(['order' => null]));
    $this->assertNull($this->processor->createBlocksFilter(['order' => new \stdClass()]));
  }

  public function testCreateAbandonedCartPersistentCartFilterReturnsNullWithoutUserId(): void {
    $queue = $this->makeQueue([AbandonedCart::TASK_META_NAME => [11]]);

    $this->assertNull($this->processor->createAbandonedCartPersistentCartFilter([], $queue));
    $this->assertNull($this->processor->createAbandonedCartPersistentCartFilter(['user_id' => 0], $queue));
  }

  public function testCreateAbandonedCartPersistentCartFilterReturnsNullWithoutProducts(): void {
    $this->assertNull($this->processor->createAbandonedCartPersistentCartFilter(['user_id' => 123], null));
    $this->assertNull($this->processor->createAbandonedCartPersistentCartFilter(
      ['user_id' => 123],
      $this->makeQueue([])
    ));
    $this->assertNull($this->processor->createAbandonedCartPersistentCartFilter(
      ['user_id' => 123],
      $this->makeQueue([AbandonedCart::TASK_META_NAME => []])
    ));
  }

  public function testAbandonedCartPersistentCartFilterExposesQueueProductIdsForMatchingUser(): void {
    $filter = $this->processor->createAbandonedCartPersistentCartFilter(
      ['user_id' => 123],
      $this->makeQueue([AbandonedCart::TASK_META_NAME => ['11', 12, 'invalid', 0, 11]])
    );
    $this->assertIsCallable($filter);

    $result = $filter(null, 123, '_woocommerce_persistent_cart_1', true);

    // The filter returns a single-element value list. WooCommerce reads the
    // persistent cart with $single = true, so get_metadata_raw() unwraps this
    // to the cart via its $check[0] branch.
    $this->assertSame([
      [
        'cart' => [
          'mailpoet_abandoned_cart_0' => ['product_id' => 11],
          'mailpoet_abandoned_cart_1' => ['product_id' => 12],
        ],
      ],
    ], $result);
  }

  public function testAbandonedCartPersistentCartFilterLeavesUnrelatedMetadataUntouched(): void {
    $filter = $this->processor->createAbandonedCartPersistentCartFilter(
      ['user_id' => 123],
      $this->makeQueue([AbandonedCart::TASK_META_NAME => [11]])
    );
    $this->assertIsCallable($filter);

    $this->assertSame(
      'existing',
      $filter('existing', 456, '_woocommerce_persistent_cart_1', true)
    );
    $this->assertSame('existing', $filter('existing', 123, 'first_name', true));
  }

  /**
   * @param array<string, mixed> $meta
   */
  private function makeQueue(array $meta): SendingQueueEntity {
    return $this->make(SendingQueueEntity::class, [
      'getMeta' => $meta,
    ]);
  }
}
