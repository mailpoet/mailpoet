<?php declare(strict_types = 1);

namespace MailPoet\Listing;

class HandlerTest extends \MailPoetUnitTest {

  /** @var Handler */
  private $handler;

  private $listingData = [
    'params' => [
      0 => 'page[1]/sort_by[sent_at]/sort_order[desc]/group[sent]',
      'type' => 'standard',
    ],
    'offset' => '0',
    'limit' => '20',
    'group' => 'sent',
    'search' => 'abcd',
    'sort_by' => 'sent_at',
    'sort_order' => 'desc',
    'selection' => ['1','2'],
  ];

  public function _before() {
    parent::_before();
    $this->handler = new Handler();
  }

  public function testItCreatesListingDefinition() {
    $definition = $this->handler->getListingDefinition($this->listingData);
    expect($definition->getSearch())->equals('abcd');
    expect($definition->getGroup())->equals('sent');
    expect($definition->getOffset())->equals(0);
    expect($definition->getLimit())->equals(20);
    expect($definition->getSortBy())->equals('sent_at');
    expect($definition->getSortOrder())->equals('desc');
    expect($definition->getParameters())->equals([
      0 => 'page[1]/sort_by[sent_at]/sort_order[desc]/group[sent]',
      'type' => 'standard',
    ]);
    expect($definition->getSelection())->equals([1, 2]);
  }
}
