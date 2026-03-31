<?php declare(strict_types = 1);

namespace MailPoet\Test\Twig;

use MailPoet\Twig\Functions;

class FunctionsTest extends \MailPoetUnitTest {

  /** @var Functions */
  private $functions;

  public function _before() {
    parent::_before();
    $this->functions = new Functions();
  }

  // clickedStatsTextGarden

  public function testClickedStatsTextGardenReturnsPoorForZero() {
    verify($this->functions->clickedStatsTextGarden(0))->equals('Poor');
  }

  public function testClickedStatsTextGardenReturnsPoorForNegative() {
    verify($this->functions->clickedStatsTextGarden(-1))->equals('Poor');
  }

  public function testClickedStatsTextGardenReturnsAverageForAboveZero() {
    verify($this->functions->clickedStatsTextGarden(0.1))->equals('Average');
  }

  public function testClickedStatsTextGardenReturnsAverageAtOneBoundary() {
    verify($this->functions->clickedStatsTextGarden(1))->equals('Average');
  }

  public function testClickedStatsTextGardenReturnsGoodAboveOne() {
    verify($this->functions->clickedStatsTextGarden(1.1))->equals('Good');
  }

  public function testClickedStatsTextGardenReturnsGoodAtThreeBoundary() {
    verify($this->functions->clickedStatsTextGarden(3))->equals('Good');
  }

  public function testClickedStatsTextGardenReturnsExcellentAboveThree() {
    verify($this->functions->clickedStatsTextGarden(3.1))->equals('Excellent');
  }

  // clickedStatsBadgeColor

  public function testClickedStatsBadgeColorReturnsPoorColorForZero() {
    verify($this->functions->clickedStatsBadgeColor(0))->equals('#F5E6AB');
  }

  public function testClickedStatsBadgeColorReturnsPoorColorForNegative() {
    verify($this->functions->clickedStatsBadgeColor(-5))->equals('#F5E6AB');
  }

  public function testClickedStatsBadgeColorReturnsAverageColorForAboveZero() {
    verify($this->functions->clickedStatsBadgeColor(0.5))->equals('#DCDCDE');
  }

  public function testClickedStatsBadgeColorReturnsAverageColorAtOneBoundary() {
    verify($this->functions->clickedStatsBadgeColor(1))->equals('#DCDCDE');
  }

  public function testClickedStatsBadgeColorReturnsGoodColorAboveOne() {
    verify($this->functions->clickedStatsBadgeColor(2))->equals('#B5D4EF');
  }

  public function testClickedStatsBadgeColorReturnsGoodColorAtThreeBoundary() {
    verify($this->functions->clickedStatsBadgeColor(3))->equals('#B5D4EF');
  }

  public function testClickedStatsBadgeColorReturnsExcellentColorAboveThree() {
    verify($this->functions->clickedStatsBadgeColor(4))->equals('#C6E1C6');
  }
}
