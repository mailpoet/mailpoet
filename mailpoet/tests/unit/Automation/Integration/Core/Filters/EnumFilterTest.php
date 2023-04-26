<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\Core\Filters;

use MailPoet\Automation\Engine\Data\Filter;
use MailPoet\Automation\Integrations\Core\Filters\EnumFilter;
use MailPoetUnitTest;
use stdClass;

class EnumFilterTest extends MailPoetUnitTest {
  public function testItReturnsCorrectConfiguration(): void {
    $filter = new EnumFilter();
    $this->assertSame('enum', $filter->getFieldType());

    $this->assertSame([
      'is-any-of' => 'is any of',
      'is-none-of' => 'is none of',
    ], $filter->getConditions());

    $this->assertSame([
      'type' => 'object',
      'properties' => [
        'value' => [
          'oneOf' => [
            ['type' => 'array', 'items' => ['type' => 'string'], 'minItems' => 1],
            ['type' => 'array', 'items' => ['type' => 'integer'], 'minItems' => 1],
          ],
          'required' => true,
        ],
      ],
    ], $filter->getArgsSchema()->toArray());
  }

  public function testInvalidValues(): void {
    $this->assertNotMatches('is-any-of', [], null);
    $this->assertNotMatches('is-any-of', [], 123);
    $this->assertNotMatches('is-any-of', [], 'abc');
    $this->assertNotMatches('is-any-of', [], new stdClass());
    $this->assertNotMatches('is-any-of', [], true);
    $this->assertNotMatches('is-any-of', [], false);
    $this->assertNotMatches('is-any-of', [1], [1]);

    $this->assertNotMatches('is-any-of', null, 1);
    $this->assertNotMatches('is-any-of', 123, 1);
    $this->assertNotMatches('is-any-of', 'abc', 1);
    $this->assertNotMatches('is-any-of', new stdClass(), 1);
    $this->assertNotMatches('is-any-of', true, 1);
    $this->assertNotMatches('is-any-of', false, 1);
    $this->assertNotMatches('is-any-of', 1, 1);
  }

  public function testMatchesAnyCondition(): void {
    $this->assertMatches('is-any-of', [1], 1);
    $this->assertMatches('is-any-of', [1, 1, 1], 1);
    $this->assertMatches('is-any-of', [1, 2, 3], 1);
    $this->assertMatches('is-any-of', [1, 2, 3], 3);
    $this->assertMatches('is-any-of', ['abc', 'def'], 'abc');
    $this->assertNotMatches('is-any-of', [], 1);
    $this->assertNotMatches('is-any-of', [1], 0);
    $this->assertNotMatches('is-any-of', [1, 2, 3], 7);
    $this->assertNotMatches('is-any-of', ['abc', 'def'], 'xyz');
  }

  public function testMatchesNoneCondition(): void {
    $this->assertMatches('is-none-of', [], 1);
    $this->assertMatches('is-none-of', [1], 2);
    $this->assertMatches('is-none-of', [1, 2, 3], 7);
    $this->assertMatches('is-none-of', [1, 1, 1], 2);
    $this->assertMatches('is-none-of', ['abc', 'def'], 'xyz');
    $this->assertNotMatches('is-none-of', [1], 1);
    $this->assertNotMatches('is-none-of', [1, 2, 3], 2);
    $this->assertNotMatches('is-none-of', [1, 1, 1], 1);
    $this->assertNotMatches('is-none-of', ['abc', 'def'], 'def');
  }

  public function testUnknownCondition(): void {
    $this->assertNotMatches('unknown', [1], 1);
    $this->assertNotMatches('unknown', [1, 2, 3], 1);
  }

  private function assertMatches(string $condition, $filterValue, $value): void {
    $this->assertTrue($this->matchesFilter($condition, $filterValue, $value));
  }

  private function assertNotMatches(string $condition, $filterValue, $value): void {
    $this->assertFalse($this->matchesFilter($condition, $filterValue, $value));
  }

  private function matchesFilter(string $condition, $filterValue, $value): bool {
    $filter = new EnumFilter();
    return $filter->matches(new Filter('f1', 'enum', '', $condition, ['value' => $filterValue]), $value);
  }
}
