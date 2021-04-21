<?php

namespace MailPoet\Test\DataFactories;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Segments\DynamicSegments\SegmentSaveController;

class DynamicSegment extends Segment {

  private $filterData = [];

  /** @var SegmentSaveController */
  protected $saveController;

  public function __construct() {
    parent::__construct();
    $this->saveController = ContainerWrapper::getInstance()->get(SegmentSaveController::class);
  }

  public function withUserRoleFilter($role) {
    $this->filterData['segmentType'] = 'userRole';
    $this->filterData['wordpressRole'] = $role;
    return $this;
  }

  public function withWooCommerceProductFilter($productId) {
    $this->filterData['segmentType'] = 'woocommerce';
    $this->filterData['action'] = 'purchasedProduct';
    $this->filterData['product_id'] = $productId;
    return $this;
  }

  public function withWooCommerceCategoryFilter($categoryId) {
    $this->filterData['segmentType'] = 'woocommerce';
    $this->filterData['action'] = 'purchasedCategory';
    $this->filterData['category_id'] = $categoryId;
    return $this;
  }

  public function withWooCommerceNumberOfOrdersFilter() {
    $this->filterData['segmentType'] = 'woocommerce';
    $this->filterData['action'] = 'numberOfOrders';
    $this->filterData['number_of_orders_type'] = '=';
    $this->filterData['number_of_orders_count'] = '1';
    $this->filterData['number_of_orders_days'] = '1';
    return $this;
  }

  public function withWooCommerceTotalSpentFilter(float $amount = 9, int $days = 1) {
    $this->filterData['segmentType'] = 'woocommerce';
    $this->filterData['action'] = 'totalSpent';
    $this->filterData['total_spent_type'] = '>';
    $this->filterData['total_spent_amount'] = $amount;
    $this->filterData['total_spent_days'] = $days;
    return $this;
  }

  public function create(): SegmentEntity {
    if (empty($this->filterData['segmentType'])) {
      $this->withUserRoleFilter('editor');
    }
    $segment = $this->saveController->save(array_merge($this->data, $this->filterData));
    if (($this->data['deleted_at'] ?? null) instanceof \DateTimeInterface) {
      $segment->setDeletedAt($this->data['deleted_at']);
      $this->entityManager->flush();
    }
    return $segment;
  }
}
