<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Analytics\Entities;

use MailPoet\API\REST\Request;
use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;

class Query {

  /** @var \DateTimeImmutable */
  private $primaryAfter;

  /** @var \DateTimeImmutable */
  private $primaryBefore;

  /** @var \DateTimeImmutable */
  private $secondaryAfter;

  /** @var \DateTimeImmutable */
  private $secondaryBefore;

  public function __construct(
    \DateTimeImmutable $primaryAfter,
    \DateTimeImmutable $primaryBefore,
    \DateTimeImmutable $secondaryAfter,
    \DateTimeImmutable $secondaryBefore
  ) {
    $this->primaryAfter = $primaryAfter;
    $this->primaryBefore = $primaryBefore;
    $this->secondaryAfter = $secondaryAfter;
    $this->secondaryBefore = $secondaryBefore;
  }

  public function getPrimaryAfter(): \DateTimeImmutable {
    return $this->primaryAfter;
  }

  public function getPrimaryBefore(): \DateTimeImmutable {
    return $this->primaryBefore;
  }

  public function getSecondaryAfter(): \DateTimeImmutable {
    return $this->secondaryAfter;
  }

  public function getSecondaryBefore(): \DateTimeImmutable {
    return $this->secondaryBefore;
  }

  public static function fromRequest(Request $request): self {

    $query = $request->getParam('query');
    if (!is_array($query)) {
      throw new UnexpectedValueException('Invalid query parameters');
    }
    $primary = $query['primary'] ?? null;
    $secondary = $query['secondary'] ?? null;
    if (!is_array($primary) || !is_array($secondary)) {
      throw new UnexpectedValueException('Invalid query parameters');
    }
    $primaryAfter = $primary['after'] ?? null;
    $primaryBefore = $primary['before'] ?? null;
    $secondaryAfter = $secondary['after'] ?? null;
    $secondaryBefore = $secondary['before'] ?? null;
    if (
      !is_string($primaryAfter) ||
      !is_string($primaryBefore) ||
      !is_string($secondaryAfter) ||
      !is_string($secondaryBefore)
    ) {
      throw new UnexpectedValueException('Invalid query parameters');
    }
    return new self(
      new \DateTimeImmutable($primaryAfter),
      new \DateTimeImmutable($primaryBefore),
      new \DateTimeImmutable($secondaryAfter),
      new \DateTimeImmutable($secondaryBefore)
    );
  }
}
