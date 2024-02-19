<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\Core\Filters;

use MailPoet\Automation\Engine\Data\Filter;
use MailPoet\Automation\Integrations\Core\Filters\NumberFilter;
use MailPoetUnitTest;
use stdClass;

class NumberFilterTest extends MailPoetUnitTest {
  public function testItReturnsCorrectConfiguration(): void {
    $filter = new NumberFilter();
    $this->assertSame('number', $filter->getFieldType());
    $this->assertSame([
      'equals' => 'equals',
      'not-equal' => 'not equal',
      'greater-than' => 'greater than',
      'less-than' => 'less than',
      'between' => 'between',
      'not-between' => 'not between',
      'is-multiple-of' => 'is multiple of',
      'is-not-multiple-of' => 'is not multiple of',
      'is-set' => 'is set',
      'is-not-set' => 'is not set',
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

    $singleValueArgsSchema = [
      'type' => 'object',
      'properties' => [
        'value' => [
          'type' => 'number',
          'required' => true,
        ],
        'params' => $paramsSchema,
      ],
    ];

    $rangeValueArgsSchema = [
      'type' => 'object',
      'properties' => [
        'value' => [
          'type' => 'array',
          'items' => ['type' => 'number'],
          'minItems' => 2,
          'maxItems' => 2,
          'required' => true,
        ],
        'params' => $paramsSchema,
      ],
    ];

    $emptyArgsSchema = [
      'type' => 'object',
      'properties' => [
        'params' => $paramsSchema,
      ],
    ];

    $this->assertSame($singleValueArgsSchema, $filter->getArgsSchema('equals')->toArray());
    $this->assertSame($singleValueArgsSchema, $filter->getArgsSchema('not-equals')->toArray());
    $this->assertSame($singleValueArgsSchema, $filter->getArgsSchema('greater-than')->toArray());
    $this->assertSame($singleValueArgsSchema, $filter->getArgsSchema('less-than')->toArray());
    $this->assertSame($rangeValueArgsSchema, $filter->getArgsSchema('between')->toArray());
    $this->assertSame($rangeValueArgsSchema, $filter->getArgsSchema('not-between')->toArray());
    $this->assertSame($singleValueArgsSchema, $filter->getArgsSchema('is-multiple-of')->toArray());
    $this->assertSame($singleValueArgsSchema, $filter->getArgsSchema('is-not-multiple-of')->toArray());
    $this->assertSame($emptyArgsSchema, $filter->getArgsSchema('is-set')->toArray());
    $this->assertSame($emptyArgsSchema, $filter->getArgsSchema('is-not-set')->toArray());
  }

  public function testInvalidValues(): void {
    $this->assertNotMatches('equals', 1, null);
    $this->assertNotMatches('equals', 1, 'abc');
    $this->assertNotMatches('equals', 1, true);
    $this->assertNotMatches('equals', 1, []);
    $this->assertNotMatches('equals', 1, [1, 2, 3, 'a', 'b', 'c']);
    $this->assertNotMatches('equals', 1, new stdClass());

    $this->assertNotMatches('equals', null, 1);
    $this->assertNotMatches('equals', 'abc', 1);
    $this->assertNotMatches('equals', true, 1);
    $this->assertNotMatches('equals', [], 1);
    $this->assertNotMatches('equals', [1, 2, 3, 'a', 'b', 'c'], 1);
    $this->assertNotMatches('equals', new stdClass(), 1);
  }

  public function testEqualsCondition(): void {
    $this->assertMatches('equals', 0, 0);
    $this->assertMatches('equals', 1, 1);
    $this->assertMatches('equals', 1.123, 1.123);
    $this->assertMatches('equals', 1.0, 1);
    $this->assertMatches('equals', 1, 1.0);
    $this->assertMatches('equals', 0, -0);
    $this->assertMatches('equals', -0, 0);
    $this->assertMatches('equals', -1, -1);
    $this->assertMatches('equals', -1.0, -1);
    $this->assertMatches('equals', -1, -1.0);
    $this->assertMatches('equals', -1.123, -1.123);

    $this->assertNotMatches('equals', 0, 1);
    $this->assertNotMatches('equals', 1, 0);
    $this->assertNotMatches('equals', 1.123, 1.124);
    $this->assertNotMatches('equals', 1.0, 1.00000000000001);
    $this->assertNotMatches('equals', -1, 1);
  }

  public function testNotEqualCondition(): void {
    $this->assertMatches('not-equal', 0, 1);
    $this->assertMatches('not-equal', 1, 0);
    $this->assertMatches('not-equal', 1.123, 1.124);
    $this->assertMatches('not-equal', 1.0, 1.00000000000001);
    $this->assertMatches('not-equal', -1, 1);

    $this->assertNotMatches('not-equal', 0, 0);
    $this->assertNotMatches('not-equal', 1, 1);
    $this->assertNotMatches('not-equal', 1.123, 1.123);
    $this->assertNotMatches('not-equal', 1.0, 1);
    $this->assertNotMatches('not-equal', 1, 1.0);
    $this->assertNotMatches('not-equal', 0, -0);
    $this->assertNotMatches('not-equal', -0, 0);
    $this->assertNotMatches('not-equal', -1, -1);
    $this->assertNotMatches('not-equal', -1.0, -1);
    $this->assertNotMatches('not-equal', -1, -1.0);
    $this->assertNotMatches('not-equal', -1.123, -1.123);
  }

  public function testGreaterThan(): void {
    $this->assertMatches('greater-than', 0, 1);
    $this->assertMatches('greater-than', 1, 2);
    $this->assertMatches('greater-than', 1.123, 1.124);
    $this->assertMatches('greater-than', 1.0, 1.00000000000001);
    $this->assertMatches('greater-than', -1, 0);
    $this->assertMatches('greater-than', -1, 1);
    $this->assertMatches('greater-than', -1.123, -1.122);

    $this->assertNotMatches('greater-than', 0, 0);
    $this->assertNotMatches('greater-than', 1, 1);
    $this->assertNotMatches('greater-than', 1.123, 1.123);
    $this->assertNotMatches('greater-than', 1.0, 1);
    $this->assertNotMatches('greater-than', 1, 1.0);
    $this->assertNotMatches('greater-than', 1, 0);
    $this->assertNotMatches('greater-than', 1.0, 0.99999999999999);
    $this->assertNotMatches('greater-than', 0, -0);
    $this->assertNotMatches('greater-than', -0, 0);
    $this->assertNotMatches('greater-than', -1, -1);
  }

  public function testLessThan(): void {
    $this->assertMatches('less-than', 1, 0);
    $this->assertMatches('less-than', 2, 1);
    $this->assertMatches('less-than', 1.124, 1.123);
    $this->assertMatches('less-than', 1.00000000000001, 1.0);
    $this->assertMatches('less-than', 0, -1);
    $this->assertMatches('less-than', 1, -1);
    $this->assertMatches('less-than', -1.122, -1.123);

    $this->assertNotMatches('less-than', 0, 0);
    $this->assertNotMatches('less-than', 1, 1);
    $this->assertNotMatches('less-than', 1.123, 1.123);
    $this->assertNotMatches('less-than', 1.0, 1);
    $this->assertNotMatches('less-than', 1, 1.0);
    $this->assertNotMatches('less-than', 1, 2);
    $this->assertNotMatches('less-than', 1.0, 1.00000000000001);
    $this->assertNotMatches('less-than', 0, -0);
    $this->assertNotMatches('less-than', -0, 0);
    $this->assertNotMatches('less-than', -1, -1);
  }

  public function testBetween(): void {
    $this->assertMatches('between', [0, 1], 0.5);
    $this->assertMatches('between', [-1, 1], 0);
    $this->assertMatches('between', [-1, 0], -0.5);

    $this->assertNotMatches('between', [0, 1], 0);
    $this->assertNotMatches('between', [0, 1], -0);
    $this->assertNotMatches('between', [0, 1], 0.0);
    $this->assertNotMatches('between', [0, 1], 2);
    $this->assertNotMatches('between', [0, 1], -1);
    $this->assertNotMatches('between', [-1, 1], -1);
    $this->assertNotMatches('between', [-1, 1], 1);
    $this->assertNotMatches('between', [-1, 1], -2);
    $this->assertNotMatches('between', [-1, 1], 2);
  }

  public function testNotBetween(): void {
    $this->assertMatches('not-between', [0, 1], 0);
    $this->assertMatches('not-between', [0, 1], -0);
    $this->assertMatches('not-between', [0, 1], 0.0);
    $this->assertMatches('not-between', [0, 1], 2);
    $this->assertMatches('not-between', [0, 1], -1);
    $this->assertMatches('not-between', [-1, 1], -1);
    $this->assertMatches('not-between', [-1, 1], 1);
    $this->assertMatches('not-between', [-1, 1], -2);
    $this->assertMatches('not-between', [-1, 1], 2);

    $this->assertNotMatches('not-between', [0, 1], 0.5);
    $this->assertNotMatches('not-between', [-1, 1], 0);
    $this->assertNotMatches('not-between', [-1, 0], -0.5);
  }

  public function testIsMultipleOf(): void {
    $this->assertMatches('is-multiple-of', 1, 0);
    $this->assertMatches('is-multiple-of', 1, 1);
    $this->assertMatches('is-multiple-of', 1, 2);
    $this->assertMatches('is-multiple-of', 1, 12345);
    $this->assertMatches('is-multiple-of', 2, 2);
    $this->assertMatches('is-multiple-of', 2, 4);
    $this->assertMatches('is-multiple-of', 2, 100);
    $this->assertMatches('is-multiple-of', 2, -2);
    $this->assertMatches('is-multiple-of', 2, -4);
    $this->assertMatches('is-multiple-of', 2, -100);
    $this->assertMatches('is-multiple-of', -2, 0);
    $this->assertMatches('is-multiple-of', -2, -2);
    $this->assertMatches('is-multiple-of', -2, -4);
    $this->assertMatches('is-multiple-of', -2, 2);
    $this->assertMatches('is-multiple-of', -2, 4);
    $this->assertMatches('is-multiple-of', 0.25, 0);
    $this->assertMatches('is-multiple-of', 0.25, 0.25);
    $this->assertMatches('is-multiple-of', 0.25, 0.5);
    $this->assertMatches('is-multiple-of', 0.25, 1);
    $this->assertMatches('is-multiple-of', 0.25, -0.5);
    $this->assertMatches('is-multiple-of', -0.25, 0.5);
    $this->assertMatches('is-multiple-of', -0.25, -0.5);

    $this->assertNotMatches('is-multiple-of', 0, 0);
    $this->assertNotMatches('is-multiple-of', 1, 0.5);
    $this->assertNotMatches('is-multiple-of', 2, 1);
    $this->assertNotMatches('is-multiple-of', 2, 123);
    $this->assertNotMatches('is-multiple-of', 2, -0.5);
    $this->assertNotMatches('is-multiple-of', 2, -1);
    $this->assertNotMatches('is-multiple-of', 2, -123);
    $this->assertNotMatches('is-multiple-of', -2, 1);
    $this->assertNotMatches('is-multiple-of', -2, -1);
  }

  public function testIsNotMultipleOf(): void {
    $this->assertMatches('is-not-multiple-of', 0, 0);
    $this->assertMatches('is-not-multiple-of', 1, 0.5);
    $this->assertMatches('is-not-multiple-of', 2, 1);
    $this->assertMatches('is-not-multiple-of', 2, 123);
    $this->assertMatches('is-not-multiple-of', 2, -0.5);
    $this->assertMatches('is-not-multiple-of', 2, -1);
    $this->assertMatches('is-not-multiple-of', 2, -123);
    $this->assertMatches('is-not-multiple-of', -2, 1);
    $this->assertMatches('is-not-multiple-of', -2, -1);

    $this->assertNotMatches('is-not-multiple-of', 1, 0);
    $this->assertNotMatches('is-not-multiple-of', 1, 1);
    $this->assertNotMatches('is-not-multiple-of', 1, 2);
    $this->assertNotMatches('is-not-multiple-of', 1, 12345);
    $this->assertNotMatches('is-not-multiple-of', 2, 2);
    $this->assertNotMatches('is-not-multiple-of', 2, 4);
    $this->assertNotMatches('is-not-multiple-of', 2, 100);
    $this->assertNotMatches('is-not-multiple-of', 2, -2);
    $this->assertNotMatches('is-not-multiple-of', 2, -4);
    $this->assertNotMatches('is-not-multiple-of', 2, -100);
    $this->assertNotMatches('is-not-multiple-of', -2, 0);
    $this->assertNotMatches('is-not-multiple-of', -2, -2);
    $this->assertNotMatches('is-not-multiple-of', -2, -4);
    $this->assertNotMatches('is-not-multiple-of', -2, 2);
    $this->assertNotMatches('is-not-multiple-of', -2, 4);
    $this->assertNotMatches('is-not-multiple-of', 0.25, 0);
  }

  public function testIsSet(): void {
    $this->assertMatches('is-set', null, 0);
    $this->assertMatches('is-set', null, 0.5);
    $this->assertMatches('is-set', null, -0.5);
    $this->assertMatches('is-set', null, 1);
    $this->assertMatches('is-set', null, -1);

    $this->assertNotMatches('is-set', null, null);
  }

  public function testIsNotSet(): void {
    $this->assertMatches('is-not-set', null, null);

    $this->assertNotMatches('is-not-set', null, 0);
    $this->assertNotMatches('is-not-set', null, 0.5);
    $this->assertNotMatches('is-not-set', null, -0.5);
    $this->assertNotMatches('is-not-set', null, 1);
    $this->assertNotMatches('is-not-set', null, -1);
  }

  public function testFieldParams(): void {
    if (!defined('DAY_IN_SECONDS')) {
      define('DAY_IN_SECONDS', 24 * 60 * 60);
    }

    $filter = new NumberFilter();
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
    $filter = new NumberFilter();
    return $filter->matches(new Filter('f1', 'number', '', $condition, ['value' => $filterValue]), $value);
  }
}
