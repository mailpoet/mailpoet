<?php

namespace MailPoet\DynamicSegments\Filters;

class WooCommerceCategoryTest extends \MailPoetTest {
  public function testToArray() {
    $filter = new WooCommerceCategory(5);
    $data = $filter->toArray();
    expect($data)->notEmpty();
    expect($data['segmentType'])->same('woocommerce');
    expect($data['action'])->same('purchasedCategory');
    expect($data['category_id'])->same(5);
  }
}
