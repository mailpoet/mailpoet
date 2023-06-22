<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\Core\Filters;

use MailPoet\Automation\Engine\Data\Filter;
use MailPoet\Automation\Integrations\Core\Filters\BooleanFilter;
use MailPoetUnitTest;
use stdClass;

class BooleanFilterTest extends MailPoetUnitTest {
  public function testItReturnsCorrectConfiguration(): void {
    $filter = new BooleanFilter();
    $this->assertSame('boolean', $filter->getFieldType());
    $this->assertSame(['is' => 'is'], $filter->getConditions());

    $this->assertSame([
      'type' => 'object',
      'properties' => [
        'value' => [
          'type' => 'boolean',
          'required' => true,
        ],
      ],
    ], $filter->getArgsSchema('is')->toArray());
  }

  public function testInvalidValues(): void {
    $this->assertNotMatches('is', true, null);
    $this->assertNotMatches('is', true, 123);
    $this->assertNotMatches('is', true, 'abc');
    $this->assertNotMatches('is', true, []);
    $this->assertNotMatches('is', true, [1, 2, 3, 'a', 'b', 'c']);
    $this->assertNotMatches('is', true, new stdClass());

    $this->assertNotMatches('is', null, true);
    $this->assertNotMatches('is', 123, true);
    $this->assertNotMatches('is', 'abc', true);
    $this->assertNotMatches('is', [], true);
    $this->assertNotMatches('is', [1, 2, 3, 'a', 'b', 'c'], true);
    $this->assertNotMatches('is', new stdClass(), true);
  }

  public function testIsCondition(): void {
    $this->assertMatches('is', true, true);
    $this->assertMatches('is', false, false);
    $this->assertNotMatches('is', true, false);
    $this->assertNotMatches('is', false, true);
  }

  public function testUnknownCondition(): void {
    $this->assertNotMatches('unknown', true, true);
    $this->assertNotMatches('unknown', false, false);
    $this->assertNotMatches('unknown', true, false);
    $this->assertNotMatches('unknown', false, true);
  }

  private function assertMatches(string $condition, $filterValue, $value): void {
    $this->assertTrue($this->matchesFilter($condition, $filterValue, $value));
  }

  private function assertNotMatches(string $condition, $filterValue, $value): void {
    $this->assertFalse($this->matchesFilter($condition, $filterValue, $value));
  }

  private function matchesFilter(string $condition, $filterValue, $value): bool {
    $filter = new BooleanFilter();
    return $filter->matches(new Filter('f1', 'boolean', '', $condition, ['value' => $filterValue]), $value);
  }
}
