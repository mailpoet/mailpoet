<?php

namespace MailPoet\Test\DataFactories;

use MailPoet\DynamicSegments\Mappers\FormDataMapper;
use MailPoet\DynamicSegments\Persistence\Saver;
use MailPoet\Models\DynamicSegment as DynamicSegmentModel;

class DynamicSegment extends Segment {

  private $filterData = [];

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

  /** @return DynamicSegmentModel */
  public function create() {
    $segment = DynamicSegmentModel::createOrUpdate($this->data);
    if (!empty($this->filterData['segmentType'])) {
      $segment = $this->createFilter($segment, $this->filterData);
    }
    return $segment;
  }

  private function createFilter(DynamicSegmentModel $segment, array $filterData) {
    $data = array_merge($segment->asArray(), $filterData);
    $mapper = new FormDataMapper();
    $saver = new Saver();
    $dynamicSegment = $mapper->mapDataToDB($data);
    $saver->save($dynamicSegment);
    return $dynamicSegment;
  }
}
