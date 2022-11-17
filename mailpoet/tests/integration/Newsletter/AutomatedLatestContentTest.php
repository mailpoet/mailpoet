<?php declare(strict_types = 1);

namespace MailPoet\Test\Newsletter;

use MailPoet\Newsletter\AutomatedLatestContent;
use MailPoet\Newsletter\BlockPostQuery;

class AutomatedLatestContentTest extends \MailPoetTest {
  /** @var AutomatedLatestContent */
  public $alc;

  public function _before() {
    parent::_before();
    $this->alc = $this->diContainer->get(AutomatedLatestContent::class);
  }

  public function testItCategorizesTermsToTaxonomies() {
    $args = [
      'terms' => [
        [
          'id' => 1,
          'taxonomy' => 'post_tag',
        ],
        [
          'id' => 2,
          'taxonomy' => 'product_tag',
        ],
        [
          'id' => 3,
          'taxonomy' => 'post_tag',
        ],
      ],
      'inclusionType' => 'include',
    ];

    $query = new BlockPostQuery(['args' => $args]);
    expect($query->getQueryParams()['tax_query'])->equals([
      [
        [
          'taxonomy' => 'post_tag',
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
          'taxonomy' => 'post_tag',
        ],
        [
          'id' => 8,
          'taxonomy' => 'post_tag',
        ],
      ],
      'inclusionType' => 'exclude',
    ];

    $query = (new BlockPostQuery(['args' => $args]))->getQueryParams()['tax_query'];

    expect($query[0][0]['operator'])->equals('NOT IN');
    expect($query[0]['relation'])->equals('AND');
  }
}
