<?php declare(strict_types = 1);

namespace MailPoet\Test\Entities;

use MailPoet\Entities\ScheduledTaskEntity;

class ScheduledTaskEntityTest extends \MailPoetUnitTest {
  public function testPauseReasonIsNullWhenMetaIsNull(): void {
    $task = new ScheduledTaskEntity();
    verify($task->getPauseReason())->null();
  }

  public function testItStoresPauseReasonAndKeepsOtherMetaKeys(): void {
    $task = new ScheduledTaskEntity();
    $task->setMeta(['other' => 'value']);
    $task->setPauseReason(ScheduledTaskEntity::PAUSE_REASON_SENDER_DOMAIN);
    verify($task->getPauseReason())->same('sender_domain');
    verify($task->getMeta())->same(['other' => 'value', 'pause_reason' => 'sender_domain']);
  }

  public function testItStoresPauseReasonWhenMetaIsNull(): void {
    $task = new ScheduledTaskEntity();
    $task->setPauseReason(ScheduledTaskEntity::PAUSE_REASON_SENDER_DOMAIN);
    verify($task->getMeta())->same(['pause_reason' => 'sender_domain']);
  }

  public function testClearingPauseReasonKeepsOtherMetaKeys(): void {
    $task = new ScheduledTaskEntity();
    $task->setMeta(['other' => 'value', 'pause_reason' => 'sender_domain']);
    $task->setPauseReason(null);
    verify($task->getPauseReason())->null();
    verify($task->getMeta())->same(['other' => 'value']);
  }

  public function testClearingPauseReasonStoresNullMetaWhenNothingElseIsLeft(): void {
    $task = new ScheduledTaskEntity();
    $task->setPauseReason(ScheduledTaskEntity::PAUSE_REASON_SENDER_DOMAIN);
    $task->setPauseReason(null);
    verify($task->getMeta())->null();
  }

  public function testClearingPauseReasonWithNullMetaKeepsNullMeta(): void {
    $task = new ScheduledTaskEntity();
    $task->setPauseReason(null);
    verify($task->getMeta())->null();
  }

  public function testPausedStatusKeepsPauseReason(): void {
    $task = new ScheduledTaskEntity();
    $task->setPauseReason(ScheduledTaskEntity::PAUSE_REASON_SENDER_DOMAIN);
    $task->setStatus(ScheduledTaskEntity::STATUS_PAUSED);
    verify($task->getPauseReason())->same('sender_domain');
  }

  /**
   * @dataProvider nonPausedStatuses
   * @param string|null $status
   */
  public function testNonPausedStatusClearsPauseReason($status): void {
    $task = new ScheduledTaskEntity();
    $task->setMeta(['other' => 'value']);
    $task->setPauseReason(ScheduledTaskEntity::PAUSE_REASON_SENDER_DOMAIN);
    $task->setStatus($status);
    verify($task->getPauseReason())->null();
    verify($task->getMeta())->same(['other' => 'value']);
  }

  public function testOverdueSendConfirmedDefaultsToFalse(): void {
    $task = new ScheduledTaskEntity();
    verify($task->isOverdueSendConfirmed())->false();
  }

  public function testItStoresOverdueSendConfirmedAndKeepsOtherMetaKeys(): void {
    $task = new ScheduledTaskEntity();
    $task->setMeta(['other' => 'value']);
    $task->setOverdueSendConfirmed(true);
    verify($task->isOverdueSendConfirmed())->true();
    verify($task->getMeta())->same(['other' => 'value', 'overdue_send_confirmed' => true]);
  }

  public function testItRemovesOverdueSendConfirmedAndStoresNullMetaWhenNothingIsLeft(): void {
    $task = new ScheduledTaskEntity();
    $task->setOverdueSendConfirmed(true);
    $task->setOverdueSendConfirmed(false);
    verify($task->isOverdueSendConfirmed())->false();
    verify($task->getMeta())->null();
  }

  public function testOverdueSendConfirmedSurvivesLeavingThePausedStatusWhileReasonIsCleared(): void {
    $task = new ScheduledTaskEntity();
    $task->setStatus(ScheduledTaskEntity::STATUS_PAUSED);
    $task->setPauseReason(ScheduledTaskEntity::PAUSE_REASON_OVERDUE);
    $task->setOverdueSendConfirmed(true);
    $task->setStatus(null);
    verify($task->getPauseReason())->null();
    verify($task->isOverdueSendConfirmed())->true();
    verify($task->getMeta())->same(['overdue_send_confirmed' => true]);
  }

  public function nonPausedStatuses(): array {
    return [
      'running (null)' => [null],
      'virtual running' => [ScheduledTaskEntity::VIRTUAL_STATUS_RUNNING],
      'scheduled' => [ScheduledTaskEntity::STATUS_SCHEDULED],
      'completed' => [ScheduledTaskEntity::STATUS_COMPLETED],
    ];
  }
}
