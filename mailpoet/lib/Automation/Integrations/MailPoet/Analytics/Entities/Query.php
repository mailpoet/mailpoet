<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Analytics\Entities;

use MailPoet\API\REST\Request;
use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
use MailPoet\Validator\Builder;
use MailPoet\Validator\Schema;

class Query {

  /** @var \DateTimeImmutable */
  private $primaryAfter;

  /** @var \DateTimeImmutable */
  private $primaryBefore;

  /** @var int */
  private $limit;

  /** @var string */
  private $orderBy;

  /** @var string */
  private $orderDirection;

  /** @var int */
  private $page;

  /** @var array */
  private $filter;

  /** @var string | null */
  private $search;

  /** @var int | null */
  private $versionId;

  public function __construct(
    \DateTimeImmutable $primaryAfter,
    \DateTimeImmutable $primaryBefore,
    int $limit = 25,
    string $orderBy = '',
    string $orderDirection = 'asc',
    int $page = 1,
    array $filter = [],
    ?string $search = null,
    ?int $versionId = null
  ) {
    $this->primaryAfter = $primaryAfter;
    $this->primaryBefore = $primaryBefore;
    $this->limit = $limit;
    $this->orderBy = $orderBy;
    $this->orderDirection = $orderDirection;
    $this->page = $page;
    $this->filter = $filter;
    $this->search = $search;
    $this->versionId = $versionId;
  }

  public function getAfter(): \DateTimeImmutable {
    return $this->primaryAfter;
  }

  public function getBefore(): \DateTimeImmutable {
    return $this->primaryBefore;
  }

  public function getLimit(): int {
    return $this->limit;
  }

  public function getOrderBy(): string {
    return $this->orderBy;
  }

  public function getOrderDirection(): string {
    return $this->orderDirection;
  }

  public function getPage(): int {
    return $this->page;
  }

  public function getFilter(): array {
    return $this->filter;
  }

  public function getSearch(): ?string {
    return $this->search;
  }

  public function getVersionId(): ?int {
    return $this->versionId;
  }

  /**
   * @param Request $request
   * @return Query
   * @throws UnexpectedValueException
   */
  public static function fromRequest(Request $request) {
    $query = $request->getParam('query');
    if (!is_array($query)) {
      throw new UnexpectedValueException('Invalid query parameters');
    }
    $primary = $query['primary'] ?? null;
    if (!is_array($primary)) {
      throw new UnexpectedValueException('Invalid query parameters');
    }
    $primaryAfter = $primary['after'] ?? null;
    $primaryBefore = $primary['before'] ?? null;
    if (
      !is_string($primaryAfter) ||
      !is_string($primaryBefore)
    ) {
      throw new UnexpectedValueException('Invalid query parameters');
    }

    $limit = is_int($query['limit'] ?? null) ? $query['limit'] : 25;
    $orderBy = is_string($query['order_by'] ?? null) ? $query['order_by'] : '';
    $order = $query['order'] ?? null;
    $orderDirection = is_string($order) && strtolower($order) === 'asc' ? 'asc' : 'desc';
    $page = is_int($query['page'] ?? null) ? $query['page'] : 1;
    $filter = is_array($query['filter'] ?? null) ? $query['filter'] : [];
    $search = isset($query['search']) && is_string($query['search']) ? $query['search'] : null;
    $versionId = is_int($query['version_id'] ?? null) ? $query['version_id'] : null;

    return new self(
      new \DateTimeImmutable($primaryAfter),
      new \DateTimeImmutable($primaryBefore),
      $limit,
      $orderBy,
      $orderDirection,
      $page,
      $filter,
      $search,
      $versionId
    );
  }

  public static function getRequestSchema(): Schema {
    return Builder::object(
      [
        'primary' => Builder::object(
          [
            'after' => Builder::string()->formatDateTime()->required(),
            'before' => Builder::string()->formatDateTime()->required(),
          ]
        ),
        'limit' => Builder::integer()->minimum(1)->maximum(100),
        'order_by' => Builder::string(),
        'order' => Builder::string(),
        'page' => Builder::integer()->minimum(1),
        'filter' => Builder::object([]),
        'search' => Builder::string()->nullable(),
        'version_id' => Builder::integer()->minimum(1)->nullable(),
      ]
    );
  }
}
