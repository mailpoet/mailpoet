<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Engine\Data;

use MailPoet\Automation\Engine\Data\AutomationStatistics;

class AutomationStatisticsTest extends \MailPoetUnitTest {
  public function testConstructorAndGetters() {
    $stats = new AutomationStatistics(
      123,
      50,
      10,
      456,
      30,
      25,
      15,
      5,
      299.99
    );

    $this->assertEquals(123, $stats->getAutomationId());
    $this->assertEquals(50, $stats->getEntered());
    $this->assertEquals(10, $stats->getInProgress());
    $this->assertEquals(40, $stats->getExited()); // entered - inProgress
    $this->assertEquals(456, $stats->getVersionId());
    $this->assertEquals(30, $stats->getEmailsSent());
    $this->assertEquals(25, $stats->getEmailsOpened());
    $this->assertEquals(15, $stats->getEmailsClicked());
    $this->assertEquals(5, $stats->getOrders());
    $this->assertEquals(299.99, $stats->getRevenue());
  }

  public function testConstructorWithDefaults() {
    $stats = new AutomationStatistics(123);

    $this->assertEquals(123, $stats->getAutomationId());
    $this->assertEquals(0, $stats->getEntered());
    $this->assertEquals(0, $stats->getInProgress());
    $this->assertEquals(0, $stats->getExited());
    $this->assertNull($stats->getVersionId());
    $this->assertEquals(0, $stats->getEmailsSent());
    $this->assertEquals(0, $stats->getEmailsOpened());
    $this->assertEquals(0, $stats->getEmailsClicked());
    $this->assertEquals(0, $stats->getOrders());
    $this->assertEquals(0.0, $stats->getRevenue());
  }

  public function testToArray() {
    $stats = new AutomationStatistics(
      123,
      50,
      10,
      456,
      30,
      25,
      15,
      5,
      299.99
    );

    $expected = [
      'automation_id' => 123,
      'totals' => [
        'entered' => 50,
        'in_progress' => 10,
        'exited' => 40,
      ],
      'emails' => [
        'sent' => 30,
        'opened' => 25,
        'clicked' => 15,
        'orders' => 5,
        'revenue' => 299.99,
      ],
    ];

    $this->assertEquals($expected, $stats->toArray());
  }

  public function testToArrayWithDefaults() {
    $stats = new AutomationStatistics(123);

    $expected = [
      'automation_id' => 123,
      'totals' => [
        'entered' => 0,
        'in_progress' => 0,
        'exited' => 0,
      ],
      'emails' => [
        'sent' => 0,
        'opened' => 0,
        'clicked' => 0,
        'orders' => 0,
        'revenue' => 0.0,
      ],
    ];

    $this->assertEquals($expected, $stats->toArray());
  }

  public function testExitedCalculation() {
    // Test that exited is correctly calculated as entered - inProgress
    $stats = new AutomationStatistics(123, 100, 30);
    $this->assertEquals(70, $stats->getExited());

    // Test edge case where inProgress equals entered
    $stats = new AutomationStatistics(123, 50, 50);
    $this->assertEquals(0, $stats->getExited());

    // Test edge case where inProgress is 0
    $stats = new AutomationStatistics(123, 25, 0);
    $this->assertEquals(25, $stats->getExited());
  }
}
