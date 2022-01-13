<?php

namespace MailPoet\Test\Newsletter;

use MailPoet\Newsletter\AutomatedLatestContent;

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

    expect($this->alc->constructTaxonomiesQuery($args))->equals([
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

    $query = $this->alc->constructTaxonomiesQuery($args);

    expect($query[0][0]['operator'])->equals('NOT IN');
    expect($query[0]['relation'])->equals('AND');
  }
}
