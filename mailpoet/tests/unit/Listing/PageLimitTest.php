<?php declare(strict_types = 1);

namespace MailPoet\Listing;

use MailPoet\WP\Functions as WPFunctions;

class PageLimitTest extends \MailPoetUnitTest {
  public function testItReturnsDefaultWithoutModel(): void {
    $pageLimit = new PageLimit($this->createMock(WPFunctions::class));
    verify($pageLimit->getLimitPerPage())->equals(PageLimit::DEFAULT_LIMIT_PER_PAGE);
  }

  /**
   * @dataProvider storedLimitProvider
   * @param mixed $storedValue
   */
  public function testItReturnsLimitWithinListingBounds($storedValue, int $expectedLimit): void {
    $wp = $this->createMock(WPFunctions::class);
    $wp->method('getCurrentUserId')->willReturn(1);
    $wp->expects($this->once())
      ->method('getUserMeta')
      ->with(1, 'mailpoet_subscribers_per_page', true)
      ->willReturn($storedValue);

    $pageLimit = new PageLimit($wp);
    verify($pageLimit->getLimitPerPage('subscribers'))->equals($expectedLimit);
  }

  public function storedLimitProvider(): array {
    return [
      'not set' => ['', 20],
      'valid' => ['50', 50],
      'lowest' => ['1', 1],
      'highest' => ['100', 100],
      'over the maximum' => ['200', 100],
      'zero' => ['0', 20],
      'negative' => ['-5', 20],
      'not a number' => ['abc', 20],
    ];
  }
}
