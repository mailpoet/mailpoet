<?php declare(strict_types = 1);

namespace MailPoet\Test\Newsletter;

use MailPoet\Newsletter\BlockPostQuery;
use MailPoet\Newsletter\DynamicProducts;

/**
 * @group woo
 */
class DynamicProductsTest extends \MailPoetTest {
  /** @var DynamicProducts */
  public $dynamicProducts;

  public function _before() {
    parent::_before();
    $this->dynamicProducts = $this->diContainer->get(DynamicProducts::class);
  }

  public function testItCategorizesTermsToTaxonomies() {
    $args = [
      'terms' => [
        [
          'id' => 1,
          'taxonomy' => 'product_cat',
        ],
        [
          'id' => 2,
          'taxonomy' => 'product_tag',
        ],
        [
          'id' => 3,
          'taxonomy' => 'product_cat',
        ],
      ],
      'inclusionType' => 'include',
    ];

    $query = new BlockPostQuery(['args' => $args]);
    verify($query->getQueryParams()['tax_query'])->equals([
      [
        [
          'taxonomy' => 'product_cat',
          'field' => 'id',
          'terms' => [1, 3],
        ],
        [
          'taxonomy' => 'product_tag',
          'field' => 'id',
          'terms' => [2],
        ],
        'relation' => 'OR',
      ],
    ]);
  }

  public function testItCanExcludeTaxonomies() {
    $args = [
      'terms' => [
        [
          'id' => 7,
          'taxonomy' => 'product_cat',
        ],
        [
          'id' => 8,
          'taxonomy' => 'product_tag',
        ],
      ],
      'inclusionType' => 'exclude',
    ];

    $query = (new BlockPostQuery(['args' => $args]))->getQueryParams()['tax_query'];

    verify($query[0][0]['operator'])->equals('NOT IN');
    verify($query[0]['relation'])->equals('AND');
  }
}
