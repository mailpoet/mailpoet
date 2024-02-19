<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\Core\Filters;

use MailPoet\Automation\Engine\Data\Filter;
use MailPoet\Automation\Integrations\Core\Filters\EnumArrayFilter;
use MailPoetUnitTest;
use stdClass;

class EnumArrayFilterTest extends MailPoetUnitTest {
  public function testItReturnsCorrectConfiguration(): void {
    $filter = new EnumArrayFilter();
    $this->assertSame('enum_array', $filter->getFieldType());

    $this->assertSame([
      'matches-any-of' => 'matches any of',
      'matches-all-of' => 'matches all of',
      'matches-none-of' => 'matches none of',
    ], $filter->getConditions());

    $paramsSchema = [
      'type' => 'object',
      'properties' => [
        'in_the_last' => [
          'type' => 'object',
          'properties' => [
            'number' => ['type' => 'integer', 'required' => true, 'minimum' => 1],
            'unit' => ['type' => 'string', 'required' => true, 'pattern' => '^(days)$', 'default' => 'days'],
          ],
        ],
      ],
    ];

    $argsSchema = [
      'type' => 'object',
      'properties' => [
        'value' => [
          'oneOf' => [
            ['type' => 'array', 'items' => ['type' => 'string'], 'minItems' => 1],
            ['type' => 'array', 'items' => ['type' => 'integer'], 'minItems' => 1],
          ],
          'required' => true,
        ],
        'params' => $paramsSchema,
      ],
    ];

    $this->assertSame($argsSchema, $filter->getArgsSchema('matches-any-of')->toArray());
    $this->assertSame($argsSchema, $filter->getArgsSchema('matches-all-of')->toArray());
    $this->assertSame($argsSchema, $filter->getArgsSchema('matches-none-of')->toArray());
  }

  public function testInvalidValues(): void {
    $this->assertNotMatches('is', [], null);
    $this->assertNotMatches('is', [], 123);
    $this->assertNotMatches('is', [], new stdClass());
    $this->assertNotMatches('is', [], true);
    $this->assertNotMatches('is', [], false);
    $this->assertNotMatches('is', [1], 1);

    $this->assertNotMatches('is', null, []);
    $this->assertNotMatches('is', 123, []);
    $this->assertNotMatches('is', new stdClass(), []);
    $this->assertNotMatches('is', true, []);
    $this->assertNotMatches('is', false, []);
    $this->assertNotMatches('is', 1, [1]);
  }

  public function testMatchesAnyCondition(): void {
    $this->assertMatches('matches-any-of', [1, 2, 3], [1]);
    $this->assertMatches('matches-any-of', [1, 2, 3], [1, 3, 9]);
    $this->assertMatches('matches-any-of', [1], [1, 1, 1]);
    $this->assertMatches('matches-any-of', [1, 1, 1], [1]);
    $this->assertNotMatches('matches-any-of', [], []);
    $this->assertNotMatches('matches-any-of', [], [1]);
    $this->assertNotMatches('matches-any-of', [1, 2, 3], []);
    $this->assertNotMatches('matches-any-of', [1, 2, 3], [7, 8, 9]);
  }

  public function testMatchesAllCondition(): void {
    $this->assertMatches('matches-all-of', [1], [1]);
    $this->assertMatches('matches-all-of', [1, 2], [2, 1]);
    $this->assertMatches('matches-all-of', [1, 2], [2, 1, 3]);
    $this->assertMatches('matches-all-of', [1], [1, 1, 1]);
    $this->assertMatches('matches-all-of', [1, 1, 1], [1]);
    $this->assertNotMatches('matches-all-of', [], []);
    $this->assertNotMatches('matches-all-of', [], [1]);
    $this->assertNotMatches('matches-all-of', [1, 2, 3], []);
    $this->assertNotMatches('matches-all-of', [1, 2, 3], [2, 3, 4]);
  }

  public function testMatchesNoneCondition(): void {
    $this->assertMatches('matches-none-of', [], []);
    $this->assertMatches('matches-none-of', [], [1]);
    $this->assertMatches('matches-none-of', [1], []);
    $this->assertMatches('matches-none-of', [1], [2]);
    $this->assertMatches('matches-none-of', [1, 2, 3], []);
    $this->assertMatches('matches-none-of', [1, 2, 3], [4, 5, 6]);
    $this->assertMatches('matches-none-of', [1], [2, 2, 2]);
    $this->assertMatches('matches-none-of', [1, 1, 1], [2]);
    $this->assertNotMatches('matches-none-of', [1], [1]);
    $this->assertNotMatches('matches-none-of', [1, 2, 3], [2]);
    $this->assertNotMatches('matches-none-of', [1, 2, 3], [3, 4, 5]);
    $this->assertNotMatches('matches-none-of', [1], [1, 1, 1]);
    $this->assertNotMatches('matches-none-of', [1, 1, 1], [1]);
  }

  public function testUnknownCondition(): void {
    $this->assertNotMatches('unknown', [1], [1]);
    $this->assertNotMatches('unknown', [1, 2, 3], [1]);
  }

  public function testFieldParams(): void {
    if (!defined('DAY_IN_SECONDS')) {
      define('DAY_IN_SECONDS', 24 * 60 * 60);
    }

    $filter = new EnumArrayFilter();
    $params = ['in_the_last' => ['number' => 123, 'unit' => 'days']];
    $this->assertSame(
      ['in_the_last' => 123 * DAY_IN_SECONDS],
      $filter->getFieldParams(new Filter('f', 'integer', '', 'equals', ['params' => $params]))
    );
  }

  private function assertMatches(string $condition, $filterValue, $value): void {
    $this->assertTrue($this->matchesFilter($condition, $filterValue, $value));
  }

  private function assertNotMatches(string $condition, $filterValue, $value): void {
    $this->assertFalse($this->matchesFilter($condition, $filterValue, $value));
  }

  private function matchesFilter(string $condition, $filterValue, $value): bool {
    $filter = new EnumArrayFilter();
    return $filter->matches(new Filter('f1', 'enum_array', '', $condition, ['value' => $filterValue]), $value);
  }
}
