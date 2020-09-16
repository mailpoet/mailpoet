<?php

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

class EmailAction implements Filter {
  const ACTION_OPENED = 'opened';
  const ACTION_NOT_OPENED = 'notOpened';
  const ACTION_CLICKED = 'clicked';
  const ACTION_NOT_CLICKED = 'notClicked';

  /** @var string */
  private $action;

  /** @var int */
  private $newsletterId;

  /** @var int|null */
  private $linkId;

  public function __construct(string $action, int $newsletterId, int $linkId = null) {
    $this->action = $action;
    $this->newsletterId = $newsletterId;
    $this->linkId = $linkId;
  }

  public function apply(QueryBuilder $queryBuilder): QueryBuilder {
    return $queryBuilder;
  }
}
