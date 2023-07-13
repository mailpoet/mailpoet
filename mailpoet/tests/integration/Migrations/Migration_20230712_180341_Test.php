<?php declare(strict_types = 1);

namespace integration\Migrations;

use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Migrations\Migration_20230712_180341;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceAverageSpent;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceNumberOfOrders;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceSingleOrderValue;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceTotalSpent;

//phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class Migration_20230712_180341_Test extends \MailPoetTest {
  /** @var Migration_20230712_180341 */
  private $migration;

  public function _before() {
    parent::_before();
    $this->migration = new Migration_20230712_180341($this->diContainer);
  }

  public function testItMigratesWooCommerceNumberOfOrdersFilterCorrectly() {
    $oldFilterData = [
      'connect' => 'and',
      'number_of_orders_type' => '=',
      'number_of_orders_count' => '1',
      'number_of_orders_days' => '300',
    ];

    $this->testGivenFilterActionMigratesCorrectly(WooCommerceNumberOfOrders::ACTION_NUMBER_OF_ORDERS, $oldFilterData, 'number_of_orders_days');
  }

  public function testItMigratesWooCommerceTotalSpentFilterCorrectly() {
    $oldFilterData = [
      'connect' => 'and',
      'total_spent_type' => '=',
      'total_spent_amount' => '10',
      'total_spent_days' => '20',
    ];

    $this->testGivenFilterActionMigratesCorrectly(WooCommerceTotalSpent::ACTION_TOTAL_SPENT, $oldFilterData, 'total_spent_days');
  }

  public function testItMigratesWooCommerceSingleOrderValueFilterCorrectly() {
    $oldFilterData = [
      'connect' => 'and',
      'single_order_value_type' => '=',
      'single_order_value_amount' => '10',
      'single_order_value_days' => '20',
    ];

    $this->testGivenFilterActionMigratesCorrectly(WooCommerceSingleOrderValue::ACTION_SINGLE_ORDER_VALUE, $oldFilterData, 'single_order_value_days');
  }

  public function testItMigratesWooCommerceAverageSpentFilterCorrectly() {
    $oldFilterData = [
      'connect' => 'and',
      'average_spent_type' => '=',
      'average_spent_amount' => '10',
      'average_spent_days' => '20',
    ];

    $this->testGivenFilterActionMigratesCorrectly(WooCommerceAverageSpent::ACTION, $oldFilterData, 'average_spent_days');
  }

  private function testGivenFilterActionMigratesCorrectly(string $filterAction, array $oldFilterData, string $keyToBeMigrated): void {
    $migratedFilterData = $oldFilterData;
    $migratedFilterData['days'] = $oldFilterData[$keyToBeMigrated];

    $id = $this->createSegmentFilter($filterAction, $oldFilterData, 'any_type');

    $this->migration->run();

    $this->entityManager->clear();
    $filter = $this->entityManager->find(DynamicSegmentFilterEntity::class, $id);
    $this->assertInstanceOf(DynamicSegmentFilterEntity::class, $filter);
    $this->assertSame($filterAction, $filter->getFilterData()->getAction());
    $this->assertSame($migratedFilterData, $filter->getFilterData()->getData());
  }

  private function createSegmentFilter(string $action, array $data, string $type, $segmentId = 1): int {
    $filterTable = $this->entityManager->getClassMetadata(DynamicSegmentFilterEntity::class)->getTableName();
    $this->entityManager->getConnection()->executeQuery(
      "INSERT into $filterTable (segment_id, filter_data, action, filter_type)
     VALUES (:segment, :filter_data, :action, :filter_type)",
      [
        'segment' => $segmentId,
        'action' => $action,
        'filter_data' => serialize($data),
        'filter_type' => $type,
      ]
    );
    return (int)$this->entityManager->getConnection()->lastInsertId();
  }
}
