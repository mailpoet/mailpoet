<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\CliCommands;

use InvalidArgumentException;
use MailPoet\Cron\CliCommands\ExecutionLimitOverride;
use MailPoet\Cron\CronHelper;
use RuntimeException;

class ExecutionLimitOverrideTest extends \MailPoetTest {
  /** @var ExecutionLimitOverride */
  private $override;

  /** @var CronHelper */
  private $cronHelper;

  public function _before() {
    parent::_before();
    $this->override = $this->diContainer->get(ExecutionLimitOverride::class);
    $this->cronHelper = $this->diContainer->get(CronHelper::class);
  }

  public function testItLiftsTheExecutionLimitInsideTheCallback() {
    $defaultLimit = $this->cronHelper->getDaemonExecutionLimit();
    verify($defaultLimit)->equals(CronHelper::DAEMON_EXECUTION_LIMIT);

    $insideLimit = $this->override->overrideDuring(null, function () {
      return $this->cronHelper->getDaemonExecutionLimit();
    });

    verify($insideLimit)->equals(PHP_INT_MAX);
  }

  public function testItCapsTheExecutionLimitWhenSecondsGiven() {
    $insideLimit = $this->override->overrideDuring(3, function () {
      return $this->cronHelper->getDaemonExecutionLimit();
    });

    verify($insideLimit)->equals(3);
  }

  public function testItRestoresTheLimitAfterTheCallback() {
    $this->override->overrideDuring(1, function () {
      return null;
    });

    verify($this->cronHelper->getDaemonExecutionLimit())->equals(CronHelper::DAEMON_EXECUTION_LIMIT);
  }

  public function testItRestoresTheLimitEvenWhenTheCallbackThrows() {
    $caught = null;
    try {
      $this->override->overrideDuring(1, function (): void {
        throw new RuntimeException('boom');
      });
    } catch (RuntimeException $e) {
      $caught = $e;
    }

    verify($caught)->notNull();
    verify($caught->getMessage())->equals('boom');
    verify($this->cronHelper->getDaemonExecutionLimit())->equals(CronHelper::DAEMON_EXECUTION_LIMIT);
  }

  public function testItRejectsANegativeLimitWithoutTouchingTheFilter() {
    $callbackRan = false;
    try {
      $this->override->overrideDuring(-1, function () use (&$callbackRan) {
        $callbackRan = true;
        return null;
      });
      $this->fail('Expected an InvalidArgumentException.');
    } catch (InvalidArgumentException $e) {
      verify($e->getMessage())->stringContainsString('non-negative');
    }

    // The guard fires before the callback runs and before the filter is touched.
    verify($callbackRan)->false();
    verify($this->cronHelper->getDaemonExecutionLimit())->equals(CronHelper::DAEMON_EXECUTION_LIMIT);
  }

  public function testItAllowsAZeroLimit() {
    $insideLimit = $this->override->overrideDuring(0, function () {
      return $this->cronHelper->getDaemonExecutionLimit();
    });

    verify($insideLimit)->equals(0);
  }

  public function testItReturnsTheCallbackResult() {
    $result = $this->override->overrideDuring(null, function () {
      return 'value';
    });

    verify($result)->equals('value');
  }
}
