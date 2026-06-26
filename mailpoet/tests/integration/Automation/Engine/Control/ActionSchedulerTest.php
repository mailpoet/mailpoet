<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Engine\Control;

use MailPoet\Automation\Engine\Control\ActionScheduler;

class ActionSchedulerTest extends \MailPoetTest {

  private const TEST_HOOK = 'mailpoet/test/action-scheduler-wrapper';

  /** @var ActionScheduler */
  private $actionScheduler;

  public function _before(): void {
    parent::_before();
    $this->actionScheduler = $this->diContainer->get(ActionScheduler::class);
    $this->deleteTestActions();
  }

  public function _after(): void {
    $this->deleteTestActions();
    parent::_after();
  }

  public function testHasPendingScheduledActionReturnsFalseWhenNothingScheduled(): void {
    $this->assertFalse($this->actionScheduler->hasPendingScheduledAction(self::TEST_HOOK));
  }

  public function testHasPendingScheduledActionDetectsPendingAction(): void {
    $this->actionScheduler->schedule(time() + 3600, self::TEST_HOOK);
    $this->assertTrue($this->actionScheduler->hasPendingScheduledAction(self::TEST_HOOK));
  }

  /**
   * Regression for STOMAIL-8208: a hook that reschedules itself from within its
   * own handler must not see its own action. While the handler runs, that action
   * has status RUNNING. hasScheduledAction() matches RUNNING (so the next run was
   * never queued and the chain died after one run); hasPendingScheduledAction()
   * must ignore RUNNING so the next run can be scheduled.
   */
  public function testHasPendingScheduledActionIgnoresRunningAction(): void {
    $actionId = $this->actionScheduler->schedule(time() + 3600, self::TEST_HOOK);
    \ActionScheduler::store()->log_execution($actionId);

    $this->assertTrue($this->actionScheduler->hasScheduledAction(self::TEST_HOOK));
    $this->assertFalse($this->actionScheduler->hasPendingScheduledAction(self::TEST_HOOK));
  }

  private function deleteTestActions(): void {
    global $wpdb;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->delete($wpdb->prefix . 'actionscheduler_actions', ['hook' => self::TEST_HOOK]);
  }
}
