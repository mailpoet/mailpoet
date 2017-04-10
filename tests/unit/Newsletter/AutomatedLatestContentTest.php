<?php
use MailPoet\Newsletter\AutomatedLatestContent;

class AutomatedLatestContentTest extends MailPoetTest {
  function __construct() {
    $this->alc = new AutomatedLatestContent();
  }

  function testItCategorizesTermsToTaxonomies() {
    $args = array(
      'terms' => array(
        array(
          'id' => 1,
          'taxonomy' => 'post_tag'
        ),
        array(
          'id' => 2,
          'taxonomy' => 'product_tag',
        ),
        array(
          'id' => 3,
          'taxonomy' => 'post_tag'
        )
      ),
      'inclusionType' => 'include',
    );

    expect($this->alc->constructTaxonomiesQuery($args))->equals(array(
      array(
        'taxonomy' => 'post_tag',
        'field' => 'id',
        'terms' => array(1, 3)
      ),
      array(
        'taxonomy' => 'product_tag',
        'field' => 'id',
        'terms' => array(2)
      ),
      'relation' => 'OR'
    ));
  }

  function testItCanExcludeTaxonomies() {
    $args = array(
      'terms' => array(
        array(
          'id' => 7,
          'taxonomy' => 'post_tag'
        ),
        array(
          'id' => 8,
          'taxonomy' => 'post_tag'
        )
      ),
      'inclusionType' => 'exclude',
    );

    $query = $this->alc->constructTaxonomiesQuery($args);

    expect($query[0]['operator'])->equals('NOT IN');
    expect($query['relation'])->equals('AND');
  }
}
