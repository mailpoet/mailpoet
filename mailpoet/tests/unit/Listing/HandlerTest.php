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
    verify($definition->getSearch())->equals('abcd');
    verify($definition->getGroup())->equals('sent');
    verify($definition->getOffset())->equals(0);
    verify($definition->getLimit())->equals(20);
    verify($definition->getSortBy())->equals('sent_at');
    verify($definition->getSortOrder())->equals('desc');
    verify($definition->getParameters())->equals([
      0 => 'page[1]/sort_by[sent_at]/sort_order[desc]/group[sent]',
      'type' => 'standard',
    ]);
    verify($definition->getSelection())->equals([1, 2]);
  }
}
